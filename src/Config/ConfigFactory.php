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
        private readonly IniConfigParser $parser = new IniConfigParser(),
    ) {}

    public function create(): Config
    {
        $cacheFile = $this->cacheDir . '/config.php';
        $sources = [...$this->files($this->coreDir), ...$this->files($this->appDir)];
        if ($this->cacheIsFresh($cacheFile, $sources)) {
            /** @var array $values */
            $values = require $cacheFile;
            return new Config($values);
        }

        $core = $this->load($this->coreDir);
        $app = $this->load($this->appDir);
        $this->guardLockedKeys($core, $app);
        $merged = $this->mergeRecursive($core, $app);
        $resolved = $this->resolveReferences($merged);
        $this->compile($cacheFile, $resolved);
        return new Config($resolved);
    }

    private function files(string $dir): array
    {
        if (!is_dir($dir)) { return []; }
        $files = glob(rtrim($dir, '/') . '/*.ini.php') ?: [];
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
                        foreach ($flat as $candidate => $_) {
                            if (str_ends_with($candidate, '.' . $ref) || $candidate === $ref) { $ref = $candidate; break; }
                        }
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

    private function cacheIsFresh(string $cacheFile, array $sources): bool
    {
        if (!is_file($cacheFile)) { return false; }
        $cacheTime = filemtime($cacheFile) ?: 0;
        foreach ($sources as $source) {
            if ((filemtime($source) ?: PHP_INT_MAX) > $cacheTime) { return false; }
        }
        return true;
    }

    private function compile(string $cacheFile, array $values): void
    {
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0775, true) && !is_dir($this->cacheDir)) {
            throw new RuntimeException("Unable to create config cache directory: {$this->cacheDir}");
        }
        $tmp = $cacheFile . '.' . getmypid() . '.tmp';
        $php = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($values, true) . ";\n";
        if (file_put_contents($tmp, $php, LOCK_EX) === false || !rename($tmp, $cacheFile)) {
            @unlink($tmp);
            throw new RuntimeException("Unable to compile configuration cache: {$cacheFile}");
        }
    }
}
