<?php

declare(strict_types=1);

namespace Bluewater\Endpoint;

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

final class EndpointDispatcher
{
    public function __construct(
        private readonly Container $container,
        private readonly Validator $validator = new Validator(),
        private readonly SerializerRegistry $serializers = new SerializerRegistry(),
    ) {}

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
                    $arguments[] = $this->cast($route->parameters[$name], $type instanceof ReflectionNamedType ? $type->getName() : null);
                    continue;
                }

                if (array_key_exists($name, $request->query)) {
                    $arguments[] = $this->cast($request->query[$name], $type instanceof ReflectionNamedType ? $type->getName() : null);
                    continue;
                }

                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $class = $type->getName();
                    if (is_array($request->body) && class_exists($class) && !$this->container->has($class)) {
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
                throw new RuntimeException("Unable to bind endpoint parameter {$route->class}::{$route->method}(\${$name}).");
            }

            return $this->serializers->response($method->invokeArgs($endpoint, $arguments), $request);
        } catch (ValidationException $e) {
            return Response::json(['error' => 'validation_failed', 'fields' => $e->errors], 422);
        }
    }

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
        $this->validator->validate($object);
        return $object;
    }

    private function cast(mixed $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw new RuntimeException('Expected integer value.'),
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? throw new RuntimeException('Expected boolean value.'),
            'string' => (string) $value,
            default => $value,
        };
    }
}
