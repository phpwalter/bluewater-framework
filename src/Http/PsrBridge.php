<?php

declare(strict_types=1);

namespace Bluewater\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PsrBridge
{
    public static function requestFromPsr7(ServerRequestInterface $request): Request
    {
        $bodyString = (string) $request->getBody();
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        $body = str_contains($contentType, 'application/json') && $bodyString !== ''
            ? json_decode($bodyString, true)
            : $bodyString;

        return new Request(
            strtoupper($request->getMethod()),
            $request->getUri()->getPath() ?: '/',
            $request->getHeaders(),
            $request->getQueryParams(),
            $body,
            $request->getServerParams(),
            $request->getAttributes(),
        );
    }

    public static function responseToPsr7(
        Response $response,
        ResponseFactoryInterface $responses,
        StreamFactoryInterface $streams,
    ): ResponseInterface {
        $psr = $responses->createResponse($response->status);
        foreach ($response->headers as $name => $value) {
            $psr = $psr->withHeader($name, $value);
        }
        return $psr->withBody($streams->createStream($response->body));
    }

    public static function responseFromPsr7(ResponseInterface $response): Response
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        return new Response($response->getStatusCode(), $headers, (string) $response->getBody());
    }
}
