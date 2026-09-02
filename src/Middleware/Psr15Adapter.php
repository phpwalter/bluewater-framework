<?php

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

final class Psr15Adapter implements Middleware
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly ServerRequestFactoryInterface $requests,
        private readonly ResponseFactoryInterface $responses,
        private readonly StreamFactoryInterface $streams,
    ) {}

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
            $psrRequest = $psrRequest->withBody($this->streams->createStream(json_encode($request->body, JSON_THROW_ON_ERROR)));
        }

        $handler = new class($next, $this->responses, $this->streams) implements RequestHandlerInterface {
            public function __construct(
                private readonly mixed $next,
                private readonly ResponseFactoryInterface $responses,
                private readonly StreamFactoryInterface $streams,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = ($this->next)(PsrBridge::requestFromPsr7($request));
                return PsrBridge::responseToPsr7($response, $this->responses, $this->streams);
            }
        };

        return PsrBridge::responseFromPsr7($this->middleware->process($psrRequest, $handler));
    }
}
