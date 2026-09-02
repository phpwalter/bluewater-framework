<?php

declare(strict_types=1);

namespace Bluewater\Serialization;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use SimpleXMLElement;

final class SerializerRegistry
{
    public function response(mixed $value, Request $request): Response
    {
        if ($value instanceof Response) { return $value; }
        $accepts = $request->accepts();
        foreach ($accepts as $accept) {
            if ($accept === '*/*' || str_contains($accept, 'json')) { return Response::json($this->normalize($value)); }
            if (str_contains($accept, 'xml')) { return new Response(200, ['Content-Type' => 'application/xml; charset=utf-8'], $this->xml($this->normalize($value))); }
            if ($accept === 'text/csv') { return new Response(200, ['Content-Type' => 'text/csv; charset=utf-8'], $this->csv($this->normalize($value))); }
            if (str_starts_with($accept, 'text/')) { return Response::text(is_scalar($value) ? (string) $value : json_encode($this->normalize($value), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)); }
        }
        return Response::json($this->normalize($value));
    }

    private function normalize(mixed $value): mixed
    {
        if (is_object($value)) { return get_object_vars($value); }
        if (is_array($value)) { return array_map(fn(mixed $v): mixed => $this->normalize($v), $value); }
        return $value;
    }

    private function xml(mixed $value): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $append = function(SimpleXMLElement $node, mixed $item, string $key = 'item') use (&$append): void {
            if (is_array($item)) {
                foreach ($item as $k => $v) { $append($node, $v, is_string($k) ? $k : 'item'); }
                return;
            }
            $node->addChild(preg_replace('/[^A-Za-z0-9_-]/', '_', $key) ?: 'item', htmlspecialchars((string) $item, ENT_XML1));
        };
        $append($xml, $value);
        return $xml->asXML() ?: '';
    }

    private function csv(mixed $value): string
    {
        $rows = is_array($value) && array_is_list($value) ? $value : [$value];
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) { return ''; }
        $first = $rows[0] ?? [];
        if (is_array($first)) { fputcsv($stream, array_keys($first)); }
        foreach ($rows as $row) { fputcsv($stream, is_array($row) ? array_values($row) : [(string) $row]); }
        rewind($stream);
        return stream_get_contents($stream) ?: '';
    }
}
