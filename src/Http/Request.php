<?php

declare(strict_types=1);

namespace Bluewater\Http;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $path,
        public array $headers = [],
        public array $query = [],
        public mixed $body = null,
        public array $server = [],
        public array $attributes = [],
    ) {}

    public static function fromGlobals(): self
    {
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $raw = file_get_contents('php://input') ?: '';
        $type = strtolower((string) ($headers['Content-Type'] ?? $headers['content-type'] ?? ''));
        $body = str_contains($type, 'application/json') && $raw !== '' ? json_decode($raw, true) : $raw;
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            parse_url($uri, PHP_URL_PATH) ?: '/',
            $headers,
            $_GET,
            $body,
            $_SERVER,
        );
    }

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) { return is_array($value) ? implode(', ', $value) : (string) $value; }
        }
        return $default;
    }

    public function accepts(): array
    {
        $accept = $this->header('Accept', 'application/json') ?? 'application/json';
        return array_map(static fn (string $v): string => trim(explode(';', $v, 2)[0]), explode(',', $accept));
    }

    public function withAttributes(array $attributes): self
    {
        return new self($this->method, $this->path, $this->headers, $this->query, $this->body, $this->server, [...$this->attributes, ...$attributes]);
    }
}
