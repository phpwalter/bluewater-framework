<?php

declare(strict_types=1);

namespace Apps\App1\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Middleware;

final class AppHeaderMiddleware implements Middleware
{
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
