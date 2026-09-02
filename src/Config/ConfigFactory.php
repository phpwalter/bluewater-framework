<?php

/**
 * @file ConfigFactory.php
 * @path src/Config/ConfigFactory.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Loads, validates, resolves, merges, fingerprints, and atomically caches framework and application configuration.
 */

declare(strict_types=1);

namespace Bluewater\Config;

use RuntimeException;

/**
 * Builds immutable application configuration from guarded framework and app files.
 *
 * Sources are loaded in lexical filename order. Application values override
 * framework values recursively only when their types agree, and locked keys
 * cannot change. References are resolved after merge with cycle, unknown-key,
 * and ambiguity detection. The resolved tree is cached atomically and reused
 * only while the ordered source fingerprint is unchanged.
 */
final class ConfigFactory
{
    /** @var non-empty-list<non-empty-string> Keys applications cannot override. */
    private const LOCKED_KEYS = ['BW_VER'];

    /**
     * Creates a factory without reading sources or cache state.
     *
     * @param non-empty-string $coreDir Framework configuration directory.
     * @param non-empty-string $appDir Application override directory.
     * @param non-empty-string $cacheDir Writable compiled-cache directory.
     * @param array<string, scalar|null> $runtime Authoritative runtime symbols
     *     available to reference resolution but absent from serialized output.
     */
    public function __construct(
        private readonly string $coreDir,
        private readonly string $appDir,
        private readonly string $cacheDir,
        private readonly array $runtime = [],
        private readonly IniConfigParser $parser = new IniConfigParser(),
    ) {
    }

    /**
     * Returns current resolved configuration, compiling it when cache is stale.
     *
     * Source files and cache state are read. A stale result is validated fully
     * before an atomic temporary-file rename replaces the cache, so validation
     * failure leaves the prior cache untouched.
     *
     * @throws RuntimeException When sources, overrides, references, cache paths,
     *     or cache writes violate the configuration contract.
     */
    public function create(): Config
    {
        $cacheFile = $this->cacheDir . '/config.php';
        $sources = [...$this->files($this->coreDir), ...$this->files($this->appDir)];
        $fingerprint = $this->fingerprint($sources);

        if (is_file($cacheFile)) {
            $compiled = require $cacheFile;
            if (is_array($compiled) && ($compiled['fingerprint'] ?? null) === $fingerprint) {
                return new Config($this->normalizeMap($compiled['values'] ?? []));
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

    /**
     * Lists guarded configuration sources in stable lexical order.
     *
     * @return list<non-empty-string> Unique file paths; empty when absent.
     */
    private function files(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = [
            ...(glob(rtrim($dir, '/') . '/*.ini.php') ?: []),
            ...(glob(rtrim($dir, '/') . '/*.session.php') ?: []),
        ];
        $files = array_values(array_filter(array_unique($files), static fn (string $file): bool => $file !== ''));
        sort($files);
        return $files;
    }

    /** @return array<string, mixed> Recursively merged values from one directory. */
    private function load(string $dir): array
    {
        $result = [];
        foreach ($this->files($dir) as $file) {
            $result = $this->mergeRecursive($result, $this->normalizeMap($this->parser->parse($file)));
        }
        return $result;
    }

    /**
     * Merges override leaves recursively while preserving base-only values.
     *
     * @param array<string, mixed> $base Lower-precedence values.
     * @param array<string, mixed> $override Higher-precedence values.
     *
     * @return array<string, mixed> Newly composed value tree.
     */
    private function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->mergeRecursive(
                    $this->normalizeMap($base[$key]),
                    $this->normalizeMap($value),
                );
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Rejects application changes to framework-owned locked keys.
     *
     * @param array<string, mixed> $core Framework values.
     * @param array<string, mixed> $app Application values.
     *
     * @throws RuntimeException When an application adds or changes a locked key.
     */
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

    /**
     * Rejects overrides whose leaf type differs from the framework value.
     *
     * @param array<string, mixed> $core Framework subtree.
     * @param array<string, mixed> $app Application subtree.
     * @param string $path Dot-qualified diagnostic path accumulated recursively.
     *
     * @throws RuntimeException At the first non-null leaf type mismatch.
     */
    private function validateOverrideTypes(array $core, array $app, string $path = ''): void
    {
        foreach ($app as $key => $value) {
            if (!array_key_exists($key, $core)) {
                continue;
            }
            $currentPath = $path === '' ? (string) $key : $path . '.' . $key;
            if (is_array($value) && is_array($core[$key])) {
                $this->validateOverrideTypes(
                    $this->normalizeMap($core[$key]),
                    $this->normalizeMap($value),
                    $currentPath,
                );
                continue;
            }
            if ($core[$key] !== null && get_debug_type($core[$key]) !== get_debug_type($value)) {
                throw new RuntimeException(
                    "Configuration override type mismatch at {$currentPath}: expected "
                    . get_debug_type($core[$key])
                    . ', got '
                    . get_debug_type($value)
                    . '.',
                );
            }
        }
    }

    /**
     * Finds the first exact key using depth-first insertion order.
     *
     * @param array<string, mixed> $values Configuration tree.
     * @param non-empty-string $needle Exact key to locate.
     *
     * @return array{0: bool, 1: mixed} Presence flag and matched value.
     */
    private function findKey(array $values, string $needle): array
    {
        foreach ($values as $key => $value) {
            if ((string) $key === $needle) {
                return [true, $value];
            }
            if (is_array($value)) {
                $found = $this->findKey($this->normalizeMap($value), $needle);
                if ($found[0]) {
                    return $found;
                }
            }
        }
        return [false, null];
    }

    /**
     * Resolves `{key}` references against merged values and runtime symbols.
     *
     * Exact flattened keys take precedence; a leaf-name reference is accepted
     * only when unique. Circular, unknown, and ambiguous references fail.
     *
     * @param array<string, mixed> $values Merged configuration tree.
     *
     * @return array<string, mixed> Tree with scalar references substituted.
     *
     * @throws RuntimeException On circular, unknown, or ambiguous references.
     */
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
            if (array_key_exists($key, $resolved)) {
                return $resolved[$key];
            }
            if (isset($resolving[$key])) {
                throw new RuntimeException("Circular config reference detected at {$key}.");
            }
            if (!array_key_exists($key, $flat)) {
                throw new RuntimeException("Unknown config reference {{$key}}.");
            }
            $resolving[$key] = true;
            $value = $flat[$key];
            if (is_string($value)) {
                $value = preg_replace_callback(
                    '/\{([A-Za-z0-9_.-]+)\}/',
                    function (array $m) use (&$resolve, &$flat): string {
                        $ref = $m[1];
                        if (!array_key_exists($ref, $flat)) {
                            $matches = [];
                            foreach ($flat as $candidate => $_) {
                                if (str_ends_with($candidate, '.' . $ref)) {
                                    $matches[] = $candidate;
                                }
                            }
                            if (count($matches) === 1) {
                                $ref = $matches[0];
                            } elseif (count($matches) > 1) {
                                throw new RuntimeException(
                                    "Ambiguous config reference {{$ref}}; use a section-qualified reference.",
                                );
                            }
                        }
                        $replacement = $resolve($ref);
                        return is_scalar($replacement) ? (string) $replacement : '';
                    },
                    $value,
                );
            }
            unset($resolving[$key]);
            return $resolved[$key] = $value;
        };

        foreach (array_keys($flat) as $key) {
            $resolve($key);
        }
        return $this->inflate($values, $resolved);
    }

    /**
     * Flattens leaf values into dot-qualified keys.
     *
     * @param array<string, mixed> $values Source tree.
     * @param array<string, mixed> $flat Accumulator mutated by reference.
     */
    private function flatten(array $values, array &$flat, string $prefix = ''): void
    {
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $this->flatten($this->normalizeMap($value), $flat, $path);
            } else {
                $flat[$path] = $value;
            }
        }
    }

    /**
     * Reconstructs the original tree shape from resolved flattened values.
     *
     * @param array<string, mixed> $template Shape authority.
     * @param array<string, mixed> $resolved Dot-qualified leaf values.
     *
     * @return array<string, mixed> Resolved hierarchical tree.
     */
    private function inflate(array $template, array $resolved, string $prefix = ''): array
    {
        $result = [];
        foreach ($template as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            $result[$key] = is_array($value)
                ? $this->inflate($this->normalizeMap($value), $resolved, $path)
                : $resolved[$path];
        }
        return $result;
    }

    /**
     * Recursively validates configuration maps and normalizes their nested shape.
     *
     * @return array<string, mixed> String-keyed configuration map.
     *
     * @throws RuntimeException When any configuration map contains a numeric key.
     */
    private function normalizeMap(mixed $value): array
    {
        if (!is_array($value)) {
            throw new RuntimeException('Configuration data must be an array.');
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new RuntimeException('Configuration keys must be strings.');
            }
            $normalized[$key] = is_array($item) ? $this->normalizeMap($item) : $item;
        }

        return $normalized;
    }

    /**
     * Hashes ordered source paths, modification times, and byte sizes.
     *
     * @param list<non-empty-string> $sources Existing guarded source files.
     *
     * @return non-empty-string Lowercase SHA-256 fingerprint.
     */
    private function fingerprint(array $sources): string
    {
        $parts = [];
        foreach ($sources as $source) {
            $parts[] = $source . ':' . (filemtime($source) ?: 0) . ':' . (filesize($source) ?: 0);
        }
        sort($parts);
        return hash('sha256', implode('|', $parts));
    }

    /**
     * Atomically replaces the compiled PHP cache after serializing validated data.
     *
     * @param non-empty-string $cacheFile Target cache path.
     * @param non-empty-string $fingerprint Source fingerprint stored with values.
     * @param array<string, mixed> $values Fully resolved configuration tree.
     *
     * @throws RuntimeException When the directory or atomic write cannot complete.
     */
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
