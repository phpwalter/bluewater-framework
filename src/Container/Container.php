<?php

/**
 * @file Container.php
 * @path src/Container/Container.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the dependency container, autowiring rules, instance cache, and not-found failure used by the framework.
 */

declare(strict_types=1);

namespace Bluewater\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Resolves explicitly registered services and autowires concrete classes.
 *
 * Explicit instances are reused. Bindings may be class names or synchronous
 * factories and replace earlier bindings for the same identifier. Factory and
 * autowired results are not cached unless callers register them with instance().
 * Constructor injection supports named, non-builtin types and default values;
 * scalar, union, and otherwise ambiguous parameters fail explicitly.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, class-string|callable(self): mixed> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Associates an identifier with a class or synchronous factory.
     *
     * This method mutates the container and replaces any earlier binding under
     * the same exact, case-sensitive identifier. It does not invoke the binding.
     *
     * @param non-empty-string $id Service identifier.
     * @param class-string|callable(self): mixed $concrete Resolution target.
     *
     * @return $this Mutated container for fluent bootstrap configuration.
     */
    public function bind(string $id, string|callable $concrete): self
    {
        $this->bindings[$id] = $concrete;

        return $this;
    }

    /**
     * Stores a reusable object under an exact service identifier.
     *
     * A stored instance takes precedence over a binding with the same identifier.
     *
     * @param non-empty-string $id Service identifier.
     *
     * @return $this Mutated container for fluent bootstrap configuration.
     */
    public function instance(string $id, object $instance): self
    {
        $this->instances[$id] = $instance;

        return $this;
    }

    /** Returns whether an explicit instance or binding has been registered. */
    public function registered(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->bindings[$id]);
    }

    /**
     * Resolves a service by exact identifier.
     *
     * @param non-empty-string $id Registered identifier or instantiable class name.
     *
     * @throws ContainerNotFound When no class or binding exists for the identifier.
     * @throws ContainerResolutionException When reflection cannot construct the
     *     service or a required constructor parameter cannot be autowired.
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $concrete = $this->bindings[$id] ?? $id;
        if (is_callable($concrete) && !is_string($concrete)) {
            return $concrete($this);
        }
        if (!is_string($concrete) || !class_exists($concrete)) {
            throw new ContainerNotFound("Service not found: {$id}");
        }

        $ref = new ReflectionClass($concrete);
        if (!$ref->isInstantiable()) {
            throw new ContainerResolutionException("Service is not instantiable: {$concrete}");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($ctor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerResolutionException("Cannot autowire {$concrete}::\${$parameter->getName()}");
        }

        return $ref->newInstanceArgs($args);
    }

    /**
     * Reports whether the identifier is registered or names an existing class.
     *
     * A true result does not guarantee that reflection can instantiate the class.
     */
    public function has(string $id): bool
    {
        return $this->registered($id) || class_exists($id);
    }
}

/** Indicates that a requested service identifier has no resolution target. */
final class ContainerNotFound extends RuntimeException implements NotFoundExceptionInterface
{
}

/** Indicates that a known service cannot be constructed by the container. */
final class ContainerResolutionException extends RuntimeException implements ContainerExceptionInterface
{
}
