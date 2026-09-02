<?php

/**
 * @file Psr15Adapter.php
 * @path src/Middleware/Psr15Adapter.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Adapts PSR-15 middleware to Bluewater requests while preserving downstream request and response flow.
 */

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Http\PsrBridge;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Executes PSR-15 middleware inside a Bluewater middleware pipeline.
 *
 * Each request is converted through PSR-17 factories, passed to the retained
 * PSR-15 middleware, then converted back. The anonymous request handler bridges
 * downstream Bluewater execution. No converted object identity is preserved.
 */
final class Psr15Adapter implements Middleware
{
    /** Retains PSR collaborators without invoking them during construction. */
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly ServerRequestFactoryInterface $requests,
        private readonly ResponseFactoryInterface $responses,
        private readonly StreamFactoryInterface $streams,
    ) {
    }

    /**
     * Converts and executes one request through the retained PSR-15 middleware.
     *
     * @param callable(Request): Response $next Synchronous downstream handler;
     *     the PSR middleware decides whether and how often it is invoked.
     *
     * @throws \JsonException When a structured request body cannot be encoded.
     */
    public function process(Request $request, callable $next): Response
    {
        $psrRequest = $this->requests->createServerRequest($request->method, $request->path, $request->server)
            ->withQueryParams($request->query);
        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader((string) $name, $value);
        }
        foreach ($request->attributes as $name => $value) {
            $psrRequest = $psrRequest->withAttribute((string) $name, $value);
        }
        if (is_string($request->body)) {
            $psrRequest = $psrRequest->withBody($this->streams->createStream($request->body));
        } elseif ($request->body !== null) {
            $psrRequest = $psrRequest->withBody(
                $this->streams->createStream(json_encode($request->body, JSON_THROW_ON_ERROR)),
            );
        }

        $handler = new class($next, $this->responses, $this->streams) implements RequestHandlerInterface {
            /**
             * Creates a PSR handler around one Bluewater continuation.
             *
             * @param callable(Request): Response $next Downstream handler.
             */
            public function __construct(
                private readonly mixed $next,
                private readonly ResponseFactoryInterface $responses,
                private readonly StreamFactoryInterface $streams,
            ) {
            }

            /** Converts the request, invokes downstream once, and converts its response. */
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = ($this->next)(PsrBridge::requestFromPsr7($request));
                return PsrBridge::responseToPsr7($response, $this->responses, $this->streams);
            }
        };

        return PsrBridge::responseFromPsr7($this->middleware->process($psrRequest, $handler));
    }
}
