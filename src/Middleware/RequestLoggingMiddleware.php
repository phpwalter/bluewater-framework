<?php

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Psr\Log\LoggerInterface;

final class RequestLoggingMiddleware implements Middleware
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function process(Request $request, callable $next): Response
    {
        $started = hrtime(true);
        $response = $next($request);
        $durationMs = (hrtime(true) - $started) / 1_000_000;
        $this->logger->info('HTTP {method} {path} -> {status} in {duration}ms', [
            'method' => $request->method,
            'path' => $request->path,
            'status' => $response->status,
            'duration' => round($durationMs, 3),
        ]);
        return $response;
    }
}
