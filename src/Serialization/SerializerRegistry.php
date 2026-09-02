<?php

/**
 * @file SerializerRegistry.php
 * @path src/Serialization/SerializerRegistry.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Selects and executes response serializers by negotiated media type with deterministic fallback behavior.
 */

declare(strict_types=1);

namespace Bluewater\Serialization;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use SimpleXMLElement;

/**
 * Negotiates and serializes endpoint results into immutable HTTP responses.
 *
 * Custom media types use lowercase exact matching and later registration
 * replaces an earlier serializer. Built-in fallback precedence follows the
 * request's declared Accept order: wildcard or JSON, XML, CSV, then text. If no
 * type matches, JSON is used. Object normalization exposes public properties;
 * callers must ensure result objects contain no secrets or internal diagnostics.
 */
final class SerializerRegistry
{
    /** @var array<non-empty-string, callable(mixed, Request): mixed> */
    private array $serializers = [];

    /**
     * Registers or replaces a custom serializer under a lowercase media type.
     *
     * @param non-empty-string $mediaType Exact media type without parameters.
     * @param callable(mixed, Request): mixed $serializer Synchronous serializer.
     *
     * @return $this Mutated registry for fluent configuration.
     */
    public function register(string $mediaType, callable $serializer): self
    {
        $this->serializers[strtolower($mediaType)] = $serializer;
        return $this;
    }

    /**
     * Converts an endpoint value into the first acceptable response format.
     *
     * Existing Response values pass through unchanged. Custom serializers may
     * return a Response or a value castable to string. Serializer and encoding
     * exceptions escape to the application error boundary.
     */
    public function response(mixed $value, Request $request): Response
    {
        if ($value instanceof Response) {
            return $value;
        }

        $normalized = $this->normalize($value);

        foreach ($request->accepts() as $accept) {
            $accept = strtolower($accept);
            if (isset($this->serializers[$accept])) {
                $serialized = ($this->serializers[$accept])($normalized, $request);
                return $serialized instanceof Response
                    ? $serialized
                    : new Response(200, ['Content-Type' => $accept], (string) $serialized);
            }
            if ($accept === '*/*' || str_contains($accept, 'json')) {
                return Response::json($normalized);
            }
            if (str_contains($accept, 'xml')) {
                return new Response(
                    200,
                    ['Content-Type' => 'application/xml; charset=utf-8'],
                    $this->xml($normalized),
                );
            }
            if ($accept === 'text/csv') {
                return new Response(
                    200,
                    ['Content-Type' => 'text/csv; charset=utf-8'],
                    $this->csv($normalized),
                );
            }
            if (str_starts_with($accept, 'text/')) {
                return Response::text(
                    is_scalar($normalized)
                        ? (string) $normalized
                        : json_encode($normalized, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                );
            }
        }

        return Response::json($normalized);
    }

    /**
     * Recursively converts objects to public-property arrays.
     *
     * Array keys and ordering are preserved. The result is newly allocated for
     * objects and arrays; scalars and null pass through unchanged.
     */
    public function normalize(mixed $value): mixed
    {
        if (is_object($value)) {
            return $this->normalize(get_object_vars($value));
        }
        if (is_array($value)) {
            return array_map(fn (mixed $v): mixed => $this->normalize($v), $value);
        }

        return $value;
    }

    /** Returns a UTF-8 XML document rooted at `response`. */
    private function xml(mixed $value): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $append = function (SimpleXMLElement $node, mixed $item, string $key = 'item') use (&$append): void {
            if (is_array($item)) {
                foreach ($item as $k => $v) {
                    $append($node, $v, is_string($k) ? $k : 'item');
                }

                return;
            }
            $node->addChild(
                preg_replace('/[^A-Za-z0-9_-]/', '_', $key) ?: 'item',
                htmlspecialchars((string) $item, ENT_XML1),
            );
        };
        $append($xml, $value);
        return $xml->asXML() ?: '';
    }

    /**
     * Serializes a list or scalar as CSV using an in-memory temporary stream.
     *
     * When the first row is an array, its keys form the header in insertion order.
     */
    private function csv(mixed $value): string
    {
        $rows = is_array($value) && array_is_list($value) ? $value : [$value];
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        $first = $rows[0] ?? [];
        if (is_array($first)) {
            fputcsv($stream, array_keys($first));
        }
        foreach ($rows as $row) {
            fputcsv($stream, is_array($row) ? array_values($row) : [(string) $row]);
        }

        rewind($stream);
        return stream_get_contents($stream) ?: '';
    }
}
