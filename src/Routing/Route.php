<?php

/**
 * @file Route.php
 * @path src/Routing/Route.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the immutable compiled route contract consumed by matching and endpoint dispatch.
 */

declare(strict_types=1);

namespace Bluewater\Routing;

/**
 * Carries one immutable route compiled during endpoint discovery.
 *
 * Discovered routes contain parameter names and middleware class names. A route
 * returned by Router::match() preserves the compiled contract but replaces the
 * parameter-name list with captured values keyed by name.
 */
final readonly class Route
{
    /**
     * Creates a route from already validated discovery metadata.
     *
     * @param non-empty-string $httpMethod Canonical uppercase HTTP method.
     * @param non-empty-string $path Public route template beginning with `/`.
     * @param non-empty-string $regex Anchored regular expression used for matching.
     * @param non-empty-string $file Endpoint source file path.
     * @param class-string $class Endpoint class name.
     * @param non-empty-string $method Public endpoint method name.
     * @param list<non-empty-string>|array<non-empty-string, string> $parameters
     *     Declared names before matching or captured values after matching.
     * @param list<class-string> $middleware Middleware classes in execution order.
     */
    public function __construct(
        public string $httpMethod,
        public string $path,
        public string $regex,
        public string $file,
        public string $class,
        public string $method,
        public array $parameters = [],
        public array $middleware = [],
    ) {
    }
}
