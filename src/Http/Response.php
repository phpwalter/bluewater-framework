<?php

/**
 * @file Response.php
 * @path src/Http/Response.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines immutable HTTP responses and factories for JSON, text, empty, and problem responses.
 */

declare(strict_types=1);

namespace Bluewater\Http;

/**
 * Represents an immutable HTTP status, header map, and encoded body.
 *
 * The value object does not emit transport output. Factory methods establish
 * stable content types and serialize data immediately; callers remain responsible
 * for avoiding sensitive content in response payloads.
 */
final readonly class Response
{
    /**
     * Creates a response from transport-ready values without further validation.
     *
     * @param int<100, 599> $status HTTP status code.
     * @param array<string, string> $headers Response headers by field name.
     * @param string $body Encoded response bytes.
     */
    public function __construct(
        public int $status = 200,
        public array $headers = [],
        public string $body = '',
    ) {
    }

    /**
     * Serializes a value as UTF-8 JSON with unescaped path separators.
     *
     * @param int<100, 599> $status HTTP status code.
     * @param array<string, string> $headers Headers overriding the default by key.
     *
     * @throws \JsonException When the value cannot be represented as JSON.
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self(
            $status,
            ['Content-Type' => 'application/json; charset=utf-8', ...$headers],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Creates a UTF-8 plain-text response without transforming its body.
     *
     * @param int<100, 599> $status HTTP status code.
     * @param array<string, string> $headers Headers overriding the default by key.
     */
    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, ['Content-Type' => 'text/plain; charset=utf-8', ...$headers], $body);
    }

    /** Returns a new 204 response with no headers or body. */
    public static function noContent(): self
    {
        return new self(204);
    }

    /**
     * Creates an RFC 7807-compatible problem document using `about:blank`.
     *
     * @param int<400, 599> $status HTTP error status copied into body and status line.
     * @param non-empty-string $title Stable human-readable problem title.
     * @param string|null $detail Optional diagnostic detail; null omits the key.
     *
     * @return self JSON problem with keys `type`, `title`, `status`, and optional `detail`.
     *
     * @throws \JsonException When payload serialization unexpectedly fails.
     */
    public static function problem(int $status, string $title, ?string $detail = null): self
    {
        $payload = ['type' => 'about:blank', 'title' => $title, 'status' => $status];
        if ($detail !== null) {
            $payload['detail'] = $detail;
        }

        return new self(
            $status,
            ['Content-Type' => 'application/problem+json; charset=utf-8'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
