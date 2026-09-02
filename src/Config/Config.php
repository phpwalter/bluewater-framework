<?php

/**
 * @file Config.php
 * @path src/Config/Config.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines an immutable configuration view with exact, qualified, and unambiguous leaf-key lookup.
 */

declare(strict_types=1);

namespace Bluewater\Config;

use ArrayAccess;
use LogicException;
use RuntimeException;

/**
 * Exposes resolved configuration as an immutable hierarchical value map.
 *
 * Exact top-level and dot-qualified lookups take precedence. Unqualified leaf
 * lookup searches recursively and rejects ambiguity instead of selecting by
 * traversal order. The class never reads files or environment state.
 *
 * @implements ArrayAccess<array-key, mixed>
 */
final class Config implements ArrayAccess
{
    /**
     * Creates a configuration view over a copy-on-write PHP array.
     *
     * @param array<string, mixed> $values Resolved configuration tree.
     */
    public function __construct(private readonly array $values)
    {
    }

    /** @return array<string, mixed> Complete configuration tree. */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Resolves a key or returns the caller-provided default.
     *
     * Dot-qualified keys traverse exact segments. Unqualified keys search leaf
     * names recursively and are valid only when exactly one match exists.
     *
     * @param non-empty-string $key Exact, dot-qualified, or unique leaf key.
     * @param mixed $default Value returned only when no matching key exists.
     *
     * @throws RuntimeException When an unqualified key matches multiple leaves.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        if (str_contains($key, '.')) {
            $cursor = $this->values;
            foreach (explode('.', $key) as $part) {
                if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                    return $default;
                }

                $cursor = $cursor[$part];
            }

            return $cursor;
        }

        $matches = [];
        $this->findLeaf($this->values, $key, $matches);
        if (count($matches) > 1) {
            throw new RuntimeException("Configuration key '{$key}' is ambiguous; use a section-qualified key.");
        }
        return $matches[0] ?? $default;
    }

    /**
     * Returns a configured string after validating the resolved runtime value.
     *
     * @param non-empty-string $key Exact, qualified, or unique leaf key.
     *
     * @throws RuntimeException When the resolved value is not a string.
     */
    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        if (!is_string($value)) {
            throw new RuntimeException("Configuration key '{$key}' must be a string.");
        }

        return $value;
    }

    /**
     * Returns a configured non-empty string after validating the runtime value.
     *
     * @param non-empty-string $key Exact, qualified, or unique leaf key.
     * @param non-empty-string|null $default Optional non-empty fallback.
     *
     * @return non-empty-string
     *
     * @throws RuntimeException When the resolved value is missing, not a string, or blank.
     */
    public function nonEmptyString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Configuration key '{$key}' must be a non-empty string.");
        }

        return $value;
    }

    /**
     * Returns a configured boolean after validating the resolved runtime value.
     *
     * @param non-empty-string $key Exact, qualified, or unique leaf key.
     *
     * @throws RuntimeException When the resolved value is not a boolean.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (!is_bool($value)) {
            throw new RuntimeException("Configuration key '{$key}' must be a boolean.");
        }

        return $value;
    }

    /**
     * Reports whether a key resolves, including keys whose value is null.
     *
     * @throws RuntimeException When an unqualified key is ambiguous.
     */
    public function has(string $key): bool
    {
        if ($key === '') {
            return false;
        }
        $sentinel = new \stdClass();
        return $this->get($key, $sentinel) !== $sentinel;
    }

    /** Returns true when a string offset resolves to a configured value. */
    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $offset !== '' && $this->has($offset);
    }

    /** Returns the configured value, or null for a non-string or missing offset. */
    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) && $offset !== '' ? $this->get($offset) : null;
    }

    /**
     * Rejects all attempted configuration mutation.
     *
     * @throws LogicException Always; Config is immutable.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Configuration is immutable.');
    }

    /**
     * Rejects all attempted configuration removal.
     *
     * @throws LogicException Always; Config is immutable.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Configuration is immutable.');
    }

    /**
     * Collects recursively matched leaf values in deterministic traversal order.
     *
     * @param array<string, mixed> $values Configuration subtree.
     * @param list<mixed> $matches Accumulator mutated by reference.
     */
    private function findLeaf(array $values, string $key, array &$matches): void
    {
        foreach ($values as $candidate => $value) {
            if ($candidate === $key) {
                $matches[] = $value;
            }
            if (is_array($value)) {
                $this->findLeaf($this->normalizeMap($value), $key, $matches);
            }
        }
    }

    /**
     * @param array<array-key, mixed> $values Untrusted nested configuration map.
     *
     * @return array<string, mixed> Runtime-validated string-keyed map.
     */
    private function normalizeMap(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Configuration keys must be strings.');
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
