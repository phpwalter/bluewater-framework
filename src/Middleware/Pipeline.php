<?php

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Container\Container;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use RuntimeException;

final class Pipeline
{
    private array $middleware = [];

    public function __construct(private readonly Container $container) {}

    public function add(string|Middleware $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

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
