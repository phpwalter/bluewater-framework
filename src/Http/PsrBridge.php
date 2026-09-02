<?php

/**
 * @file PsrBridge.php
 * @path src/Http/PsrBridge.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Converts requests and responses between Bluewater value objects and PSR-7 implementations.
 */

declare(strict_types=1);

namespace Bluewater\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Converts between PSR-7 messages and immutable Bluewater HTTP value objects.
 *
 * Conversion allocates new values and preserves header ordering supplied by the
 * source implementation. It does not authenticate, validate, or authorize data.
 */
final class PsrBridge
{
    /**
     * Snapshots a PSR-7 server request as a Bluewater request.
     *
     * JSON bodies are decoded to associative values with non-throwing semantics;
     * other bodies remain raw strings. Reading consumes the stream's remaining bytes.
     */
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

    /**
     * Converts a Bluewater response using caller-supplied PSR-17 factories.
     *
     * Response factories and stream factories are invoked synchronously and may
     * raise their implementation-specific exceptions.
     */
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

    /**
     * Snapshots a PSR-7 response as a Bluewater response.
     *
     * Repeated header values are joined with comma-space in provider order.
     */
    public static function responseFromPsr7(ResponseInterface $response): Response
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        return new Response($response->getStatusCode(), $headers, (string) $response->getBody());
    }
}
