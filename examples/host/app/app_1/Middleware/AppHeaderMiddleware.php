<?php

/**
 * @file AppHeaderMiddleware.php
 * @path examples/host/app/app_1/Middleware/AppHeaderMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example app header middleware middleware and its response transformation.
 */

declare(strict_types=1);

namespace Apps\App1\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Middleware;

/**
 * Adds the example application's stable identifier to successful responses.
 *
 * Downstream is invoked exactly once. The returned response is newly allocated,
 * and an existing X-Bluewater-App header is replaced deterministically.
 */
final class AppHeaderMiddleware implements Middleware
{
    /**
     * Invokes downstream and decorates its response.
     *
     * @param callable(Request): Response $next Synchronous downstream handler.
     */
    public function process(Request $request, callable $next): Response
    {
        $response = $next($request);
        return new Response(
            $response->status,
            [...$response->headers, 'X-Bluewater-App' => 'app_1'],
            $response->body,
        );
    }
}
