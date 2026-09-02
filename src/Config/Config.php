<?php

declare(strict_types=1);

namespace Bluewater\Config;

use ArrayAccess;
use LogicException;

final class Config implements ArrayAccess
{
    public function __construct(private readonly array $values) {}

    public function all(): array { return $this->values; }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->values)) { return $this->values[$key]; }
        $cursor = $this->values;
        foreach (explode('.', $key) as $part) {
            if (!is_array($cursor) || !array_key_exists($part, $cursor)) { return $default; }
            $cursor = $cursor[$part];
        }
        return $cursor;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();
        return $this->get($key, $sentinel) !== $sentinel;
    }

    public function offsetExists(mixed $offset): bool { return is_string($offset) && $this->has($offset); }
    public function offsetGet(mixed $offset): mixed { return is_string($offset) ? $this->get($offset) : null; }
    public function offsetSet(mixed $offset, mixed $value): void { throw new LogicException('Configuration is immutable.'); }
    public function offsetUnset(mixed $offset): void { throw new LogicException('Configuration is immutable.'); }
}
