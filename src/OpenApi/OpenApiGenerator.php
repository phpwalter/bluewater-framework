<?php

/**
 * @file OpenApiGenerator.php
 * @path src/OpenApi/OpenApiGenerator.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Generates an OpenAPI document from discovered routes, handler signatures, validation attributes, and DTO metadata.
 */

declare(strict_types=1);

namespace Bluewater\OpenApi;

use Bluewater\ApplicationDefinition;
use Bluewater\Http\Request;
use Bluewater\Routing\Router;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Derives an OpenAPI 3.1 document from the router's discovered endpoint contract.
 *
 * Generation is deterministic for a fixed ordered route list and reflected
 * source. Path and query parameters come from handler signatures, DTO request
 * bodies become component schemas, and Summary supplies operation text. The
 * generator performs no route discovery, endpoint execution, or file writes.
 */
final class OpenApiGenerator
{
    /** Retains route and application metadata without generating a document. */
    public function __construct(
        private readonly Router $router,
        private readonly ApplicationDefinition $app,
    ) {
    }

    /**
     * Generates a newly allocated OpenAPI document in route iteration order.
     *
     * @return array{
     *     openapi: '3.1.0',
     *     info: array{title: string, version: '1.0.0'},
     *     paths: array<string, array<string, mixed>>,
     *     components: array{schemas: array<string, mixed>}
     * }
     */
    public function generate(): array
    {
        $paths = [];
        $schemas = [];
        foreach ($this->router->routes() as $route) {
            require_once $route->file;
            $method = new ReflectionMethod($route->class, $route->method);
            $parameters = [];
            $requestBody = null;

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();
                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }
                if ($type->getName() === Request::class) {
                    continue;
                }

                if (in_array($parameter->getName(), $route->parameters, true)) {
                    $parameters[] = [
                        'name' => $parameter->getName(),
                        'in' => 'path',
                        'required' => true,
                        'schema' => $this->scalarSchema($type->getName()),
                    ];
                    continue;
                }

                if ($type->isBuiltin()) {
                    $parameters[] = [
                        'name' => $parameter->getName(),
                        'in' => 'query',
                        'required' => !$parameter->isOptional(),
                        'schema' => $this->scalarSchema($type->getName()),
                    ];
                    continue;
                }

                if (str_contains($type->getName(), '\\DTO\\')) {
                    $schemaName = $this->schema($type->getName(), $schemas);
                    $requestBody = [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/' . $schemaName],
                            ],
                        ],
                    ];
                }
            }

            $summaryAttr = $method->getAttributes(Summary::class)[0] ?? null;
            $operation = [
                'operationId' => str_replace('\\', '.', $route->class) . '.' . $route->method,
                'summary' => $summaryAttr?->newInstance()->value ?? $route->method,
                'parameters' => $parameters,
                'responses' => [
                    '200' => ['description' => 'Successful response'],
                    '422' => ['description' => 'Validation failed'],
                ],
            ];
            if ($requestBody !== null) {
                $operation['requestBody'] = $requestBody;
            }

            $returnType = $method->getReturnType();
            if (
                $returnType instanceof ReflectionNamedType
                && !$returnType->isBuiltin()
                && class_exists($returnType->getName())
            ) {
                $schemaName = $this->schema($returnType->getName(), $schemas);
                $operation['responses']['200']['content']['application/json']['schema'] = [
                    '$ref' => '#/components/schemas/' . $schemaName,
                ];
            }

            $paths[$route->path][strtolower($route->httpMethod)] = $operation;
        }

        return [
            'openapi' => '3.1.0',
            'info' => ['title' => $this->app->name, 'version' => '1.0.0'],
            'paths' => $paths,
            'components' => ['schemas' => $schemas],
        ];
    }

    /**
     * Maps a reflected builtin type to its OpenAPI scalar schema.
     *
     * @return array{type: 'integer'|'number'|'boolean'|'string'}
     */
    private function scalarSchema(string $type): array
    {
        return match ($type) {
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            default => ['type' => 'string'],
        };
    }

    /**
     * Adds one reflected DTO schema unless its short name already exists.
     *
     * Schema identity uses only the short class name; the first class with that
     * name wins. Property declaration order and required-field order are retained.
     *
     * @param class-string $class DTO class to reflect.
     * @param array<string, mixed> $schemas Component map mutated by reference.
     *
     * @return non-empty-string Component schema name.
     */
    private function schema(string $class, array &$schemas): string
    {
        $ref = new ReflectionClass($class);
        $name = $ref->getShortName();
        if (isset($schemas[$name])) {
            return $name;
        }
        $properties = [];
        $required = [];

        foreach ($ref->getProperties() as $property) {
            $type = $property->getType();
            $properties[$property->getName()] = $type instanceof ReflectionNamedType
                ? $this->scalarSchema($type->getName())
                : [];
            if ($type instanceof ReflectionNamedType && !$type->allowsNull()) {
                $required[] = $property->getName();
            }
        }
        $schemas[$name] = ['type' => 'object', 'properties' => $properties];
        if ($required !== []) {
            $schemas[$name]['required'] = $required;
        }

        return $name;
    }
}
