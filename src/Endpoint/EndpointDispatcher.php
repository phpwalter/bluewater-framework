<?php

/**
 * @file EndpointDispatcher.php
 * @path src/Endpoint/EndpointDispatcher.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Binds request data and services to endpoint methods, validates DTOs, invokes handlers, and serializes results.
 */

declare(strict_types=1);

namespace Bluewater\Endpoint;

use Bluewater\Config\Config;
use Bluewater\Container\Container;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Routing\Route;
use Bluewater\Serialization\SerializerRegistry;
use Bluewater\Validation\ValidationException;
use Bluewater\Validation\Validator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

/**
 * Invokes a compiled endpoint route using deterministic parameter precedence.
 *
 * Request objects bind first, followed by route parameters, query parameters,
 * DTO hydration, container services, and declared defaults. DTO bodies ignore
 * unknown keys and are validated when the feature is enabled. Validation denial
 * becomes a 422 response; binding, reflection, handler, and serialization
 * failures escape for Application to convert to a 500 response.
 */
final class EndpointDispatcher
{
    /** Retains dispatch collaborators without resolving endpoint services. */
    public function __construct(
        private readonly Container $container,
        private readonly Validator $validator = new Validator(),
        private readonly SerializerRegistry $serializers = new SerializerRegistry(),
    ) {
    }

    /**
     * Dispatches one route and serializes its handler result.
     *
     * The endpoint file is included once and the endpoint instance is resolved
     * from the container. The method may perform arbitrary endpoint I/O.
     *
     * @throws RuntimeException When the endpoint contract or parameter binding is invalid.
     */
    public function dispatch(Route $route, Request $request): Response
    {
        require_once $route->file;
        $endpoint = $this->container->get($route->class);
        if (!$endpoint instanceof Endpoint) {
            throw new RuntimeException("Endpoint {$route->class} must extend " . Endpoint::class);
        }

        $method = new ReflectionMethod($route->class, $route->method);
        $arguments = [];
        try {
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                $name = $parameter->getName();

                if ($type instanceof ReflectionNamedType && $type->getName() === Request::class) {
                    $arguments[] = $request->withAttributes($route->parameters);
                    continue;
                }
                if (array_key_exists($name, $route->parameters)) {
                    $arguments[] = $this->cast(
                        $route->parameters[$name],
                        $type instanceof ReflectionNamedType ? $type->getName() : null,
                    );
                    continue;
                }
                if (array_key_exists($name, $request->query)) {
                    $arguments[] = $this->cast(
                        $request->query[$name],
                        $type instanceof ReflectionNamedType ? $type->getName() : null,
                    );
                    continue;
                }
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $class = $type->getName();
                    if (is_array($request->body) && str_contains($class, '\\DTO\\')) {
                        $arguments[] = $this->hydrate($class, $request->body);
                        continue;
                    }
                    $arguments[] = $this->container->get($class);
                    continue;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new RuntimeException(
                    "Unable to bind endpoint parameter {$route->class}::{$route->method}(\${$name}).",
                );
            }

            $serializers = $this->container->registered(SerializerRegistry::class)
                ? $this->container->get(SerializerRegistry::class)
                : $this->serializers;
            return $serializers->response($method->invokeArgs($endpoint, $arguments), $request);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'fields' => $e->errors], 422);
        }
    }

    /**
     * Constructs and optionally validates a DTO from an untrusted request body.
     *
     * Unknown body keys are ignored. Missing required constructor arguments
     * produce a ValidationException before object construction.
     *
     * @param class-string $class DTO class selected from the handler signature.
     * @param array<string, mixed> $body Parsed request body.
     *
     * @throws ValidationException When required or attributed fields are invalid.
     */
    private function hydrate(string $class, array $body): object
    {
        $ref = new ReflectionClass($class);
        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            $object = $ref->newInstance();
        } else {
            $args = [];
            foreach ($ctor->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $body)) {
                    $type = $parameter->getType();
                    $args[] = $this->cast($body[$name], $type instanceof ReflectionNamedType ? $type->getName() : null);
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new ValidationException([$name => ['This value is required.']]);
                }
            }
            $object = $ref->newInstanceArgs($args);
        }

        /** @var Config $config */
        $config = $this->container->get(Config::class);
        if ((bool) $config->get('features.VALIDATION', true)) {
            $this->validator->validate($object);
        }
        return $object;
    }

    /**
     * Converts a request value to one supported builtin parameter type.
     *
     * @param class-string|non-empty-string|null $type Reflected type name.
     *
     * @throws RuntimeException When integer, float, or boolean conversion fails.
     */
    private function cast(mixed $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : throw new RuntimeException('Expected integer value.'),
            'float' => is_numeric($value) ? (float) $value : throw new RuntimeException('Expected numeric value.'),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                ?? throw new RuntimeException('Expected boolean value.'),
            'string' => (string) $value,
            default => $value,
        };
    }
}
