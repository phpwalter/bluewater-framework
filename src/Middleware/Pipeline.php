<?php

/**
 * @file Pipeline.php
 * @path src/Middleware/Pipeline.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Composes configured middleware and executes the resulting request pipeline.
 */

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Container\Container;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use RuntimeException;

/**
 * Composes global and per-route middleware into a synchronous call stack.
 *
 * Global entries retain insertion order and execute before additional entries.
 * Class-name entries resolve lazily for each handled request; instance entries
 * are reused. The pipeline does not catch middleware or destination failures.
 */
final class Pipeline
{
    /** @var list<class-string|Middleware> */
    private array $middleware = [];

    /** Retains the service container without resolving middleware. */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Appends global middleware to the ordered pipeline definition.
     *
     * @param class-string|Middleware $middleware Instance or resolvable class.
     *
     * @return $this Mutated pipeline for fluent configuration.
     */
    public function add(string|Middleware $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Executes the composed middleware stack around a destination.
     *
     * @param callable(Request): Response $destination Innermost handler.
     * @param list<class-string|Middleware> $additional Ordered route middleware.
     *
     * @throws RuntimeException When a resolved entry violates Middleware.
     */
    public function handle(Request $request, callable $destination, array $additional = []): Response
    {
        $stack = [...$this->middleware, ...$additional];
        $next = $destination;
        foreach (array_reverse($stack) as $entry) {
            $previous = $next;
            $next = function (Request $request) use ($entry, $previous): Response {
                $middleware = is_string($entry) ? $this->container->get($entry) : $entry;
                if (!$middleware instanceof Middleware) {
                    throw new RuntimeException('Middleware must implement ' . Middleware::class);
                }
                return $middleware->process($request, $previous);
            };
        }
        return $next($request);
    }
}
