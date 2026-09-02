<?php

/**
 * @file RequestLoggingMiddleware.php
 * @path src/Middleware/RequestLoggingMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Records request completion metadata after downstream middleware and endpoint execution.
 */

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Psr\Log\LoggerInterface;

/**
 * Logs method, path, response status, and elapsed time after successful handling.
 *
 * The middleware does not log headers, query values, bodies, identities, or
 * credentials. If downstream handling throws, no completion entry is written
 * and the same throwable escapes.
 */
final class RequestLoggingMiddleware implements Middleware
{
    /** Retains the logger without writing during construction. */
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * Measures one downstream invocation and writes one informational log entry.
     *
     * @param callable(Request): Response $next Synchronous downstream handler.
     */
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
