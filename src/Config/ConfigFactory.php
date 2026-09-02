<?php

declare(strict_types=1);

namespace Bluewater\Config;

use RuntimeException;

final class ConfigFactory
{
    private const LOCKED_KEYS = ['BW_VER'];

    public function __construct(
        private readonly string $coreDir,
        private readonly string $appDir,
        private readonly string $cacheDir,
        private readonly array $runtime = [],
        private readonly IniConfigParser $parser = new IniConfigParser(),
    ) {}

    public function create(): Config
    {
        $cacheFile = $this->cacheDir . '/config.php';
        $sources = [...$this->files($this->coreDir), ...$this->files($this->appDir)];
        $fingerprint = $this->fingerprint($sources);

        if (is_file($cacheFile)) {
            $compiled = require $cacheFile;
            if (is_array($compiled) && ($compiled['fingerprint'] ?? null) === $fingerprint) {
                return new Config((array) ($compiled['values'] ?? []));
            }
        }

        $core = $this->load($this->coreDir);
        $app = $this->load($this->appDir);
        $this->guardLockedKeys($core, $app);
        $this->validateOverrideTypes($core, $app);
        $merged = $this->mergeRecursive($core, $app);
        $resolved = $this->resolveReferences($merged);
        $this->compile($cacheFile, $fingerprint, $resolved);
        return new Config($resolved);
    }

    private function files(string $dir): array
    {
        if (!is_dir($dir)) { return []; }
        $files = [
            ...(glob(rtrim($dir, '/') . '/*.ini.php') ?: []),
            ...(glob(rtrim($dir, '/') . '/*.session.php') ?: []),
        ];
        $files = array_values(array_unique($files));
        sort($files);
        return $files;
    }

    private function load(string $dir): array
    {
        $result = [];
        foreach ($this->files($dir) as $file) {
            $result = $this->mergeRecursive($result, $this->parser->parse($file));
        }
        return $result;
    }

    private function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    private function guardLockedKeys(array $core, array $app): void
    {
        foreach (self::LOCKED_KEYS as $key) {
            $coreValue = $this->findKey($core, $key);
            $appValue = $this->findKey($app, $key);
            if ($appValue[0] && (!$coreValue[0] || $appValue[1] !== $coreValue[1])) {
                throw new RuntimeException("Application configuration may not override locked key {$key}.");
            }
        }
    }

    private function validateOverrideTypes(array $core, array $app, string $path = ''): void
    {
        foreach ($app as $key => $value) {
            if (!array_key_exists($key, $core)) { continue; }
            $currentPath = $path === '' ? (string) $key : $path . '.' . $key;
            if (is_array($value) && is_array($core[$key])) {
                $this->validateOverrideTypes($core[$key], $value, $currentPath);
                continue;
            }
            if ($core[$key] !== null && get_debug_type($core[$key]) !== get_debug_type($value)) {
                throw new RuntimeException("Configuration override type mismatch at {$currentPath}: expected " . get_debug_type($core[$key]) . ', got ' . get_debug_type($value) . '.');
            }
        }
    }

    private function findKey(array $values, string $needle): array
    {
        foreach ($values as $key => $value) {
            if ((string) $key === $needle) { return [true, $value]; }
            if (is_array($value)) {
                $found = $this->findKey($value, $needle);
                if ($found[0]) { return $found; }
            }
        }
        return [false, null];
    }

    private function resolveReferences(array $values): array
    {
        $flat = [];
        $this->flatten($values, $flat);
        foreach ($this->runtime as $key => $value) {
            $flat[(string) $key] = $value;
        }
        $resolved = [];
        $resolving = [];

        $resolve = function (string $key) use (&$resolve, &$flat, &$resolved, &$resolving): mixed {
            if (array_key_exists($key, $resolved)) { return $resolved[$key]; }
            if (isset($resolving[$key])) { throw new RuntimeException("Circular config reference detected at {$key}."); }
            if (!array_key_exists($key, $flat)) { throw new RuntimeException("Unknown config reference {{$key}}."); }
            $resolving[$key] = true;
            $value = $flat[$key];
            if (is_string($value)) {
                $value = preg_replace_callback('/\{([A-Za-z0-9_.-]+)\}/', function (array $m) use (&$resolve, &$flat): string {
                    $ref = $m[1];
                    if (!array_key_exists($ref, $flat)) {
                        $matches = [];
                        foreach ($flat as $candidate => $_) {
                            if (str_ends_with($candidate, '.' . $ref)) { $matches[] = $candidate; }
                        }
                        if (count($matches) === 1) { $ref = $matches[0]; }
                        elseif (count($matches) > 1) { throw new RuntimeException("Ambiguous config reference {{$ref}}; use a section-qualified reference."); }
                    }
                    $replacement = $resolve($ref);
                    return is_scalar($replacement) ? (string) $replacement : '';
                }, $value);
            }
            unset($resolving[$key]);
            return $resolved[$key] = $value;
        };

        foreach (array_keys($flat) as $key) { $resolve($key); }
        return $this->inflate($values, $resolved);
    }

    private function flatten(array $values, array &$flat, string $prefix = ''): void
    {
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) { $this->flatten($value, $flat, $path); }
            else { $flat[$path] = $value; }
        }
    }

    private function inflate(array $template, array $resolved, string $prefix = ''): array
    {
        $result = [];
        foreach ($template as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            $result[$key] = is_array($value) ? $this->inflate($value, $resolved, $path) : $resolved[$path];
        }
        return $result;
    }

    private function fingerprint(array $sources): string
    {
        $parts = [];
        foreach ($sources as $source) {
            $parts[] = $source . ':' . (filemtime($source) ?: 0) . ':' . (filesize($source) ?: 0);
        }
        sort($parts);
        return hash('sha256', implode('|', $parts));
    }

    private function compile(string $cacheFile, string $fingerprint, array $values): void
    {
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            throw new RuntimeException("Unable to create config cache directory: {$this->cacheDir}");
        }
        $tmp = $cacheFile . '.' . getmypid() . '.tmp';
        $payload = ['fingerprint' => $fingerprint, 'values' => $values];
        $php = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        if (file_put_contents($tmp, $php, LOCK_EX) === false || !rename($tmp, $cacheFile)) {
            @unlink($tmp);
            throw new RuntimeException("Unable to compile configuration cache: {$cacheFile}");
        }
    }
}
