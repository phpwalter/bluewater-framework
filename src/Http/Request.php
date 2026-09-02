<?php

/**
 * @file Request.php
 * @path src/Http/Request.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the immutable internal HTTP request representation and normalization helpers.
 */

declare(strict_types=1);

namespace Bluewater\Http;

/**
 * Carries one immutable HTTP request through framework middleware and dispatch.
 *
 * Callers provide the canonical uppercase method and path. Header lookup is
 * case-insensitive, attribute updates allocate a new request, and original
 * collections are never mutated. This value object stores data but does not
 * authenticate, authorize, validate, or sanitize it.
 */
final readonly class Request
{
    /**
     * Creates a request from already parsed transport values.
     *
     * @param non-empty-string $method Canonical uppercase HTTP method.
     * @param non-empty-string $path URI path beginning with `/`.
     * @param array<string, string|list<string>> $headers Untrusted request headers.
     * @param array<string, mixed> $query Untrusted parsed query values.
     * @param mixed $body Parsed body, raw body string, or null when absent.
     * @param array<string, mixed> $server Runtime server parameters.
     * @param array<string, mixed> $attributes Framework-owned request attributes.
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers = [],
        public array $query = [],
        public mixed $body = null,
        public array $server = [],
        public array $attributes = [],
    ) {
    }

    /**
     * Creates a request snapshot from PHP process globals and input bytes.
     *
     * This factory reads getallheaders(), php://input, $_GET, and $_SERVER once.
     * A non-empty JSON body is decoded as an associative value; malformed JSON
     * currently produces null under PHP's non-throwing decoder semantics.
     */
    public static function fromGlobals(): self
    {
        $globalHeaders = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $headers = [];
        foreach ($globalHeaders as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $headers[$name] = $value;
            }
        }
        $raw = file_get_contents('php://input') ?: '';
        $type = strtolower((string) ($headers['Content-Type'] ?? $headers['content-type'] ?? ''));
        $body = str_contains($type, 'application/json') && $raw !== '' ? json_decode($raw, true) : $raw;
        $uriValue = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = is_string($uriValue) ? $uriValue : '/';
        $methodValue = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $method = is_string($methodValue) && $methodValue !== '' ? strtoupper($methodValue) : 'GET';
        return new self(
            $method,
            parse_url($uri, PHP_URL_PATH) ?: '/',
            $headers,
            $_GET,
            $body,
            $_SERVER,
        );
    }

    /**
     * Returns a header using case-insensitive exact-name comparison.
     *
     * Multiple values are joined with comma-space in their stored order.
     *
     * @param non-empty-string $name Header field name.
     * @param string|null $default Value returned when the header is absent.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }
        return $default;
    }

    /**
     * Parses accepted media types in client-declared order.
     *
     * Quality values and other parameters are removed but not used for sorting.
     *
     * @return list<string> Trimmed media types; JSON is the absent-header default.
     */
    public function accepts(): array
    {
        $accept = $this->header('Accept', 'application/json') ?? 'application/json';
        return array_map(static fn (string $v): string => trim(explode(';', $v, 2)[0]), explode(',', $accept));
    }

    /**
     * Returns a new request with attributes merged over existing values.
     *
     * @param array<string, mixed> $attributes Values that replace equal keys.
     *
     * @return self Newly allocated request; the original remains unchanged.
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->headers,
            $this->query,
            $this->body,
            $this->server,
            [...$this->attributes, ...$attributes],
        );
    }
}
