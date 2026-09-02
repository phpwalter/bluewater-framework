<?php

declare(strict_types=1);

namespace Bluewater\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $id, string|callable $concrete): self { $this->bindings[$id] = $concrete; return $this; }
    public function instance(string $id, object $instance): self { $this->instances[$id] = $instance; return $this; }
    public function registered(string $id): bool { return isset($this->instances[$id]) || isset($this->bindings[$id]); }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) { return $this->instances[$id]; }
        $concrete = $this->bindings[$id] ?? $id;
        if (is_callable($concrete) && !is_string($concrete)) { return $concrete($this); }
        if (!is_string($concrete) || !class_exists($concrete)) { throw new ContainerNotFound("Service not found: {$id}"); }

        $ref = new ReflectionClass($concrete);
        if (!$ref->isInstantiable()) { throw new RuntimeException("Service is not instantiable: {$concrete}"); }
        $ctor = $ref->getConstructor();
        if ($ctor === null) { return $ref->newInstance(); }

        $args = [];
        foreach ($ctor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) { $args[] = $this->get($type->getName()); continue; }
            if ($parameter->isDefaultValueAvailable()) { $args[] = $parameter->getDefaultValue(); continue; }
            throw new RuntimeException("Cannot autowire {$concrete}::\${$parameter->getName()}");
        }
        return $ref->newInstanceArgs($args);
    }

    public function has(string $id): bool { return $this->registered($id) || class_exists($id); }
}

final class ContainerNotFound extends RuntimeException implements NotFoundExceptionInterface {}
