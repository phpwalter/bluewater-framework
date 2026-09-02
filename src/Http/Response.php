<?php

declare(strict_types=1);

namespace Bluewater\Http;

final readonly class Response
{
    public function __construct(
        public int $status = 200,
        public array $headers = [],
        public string $body = '',
    ) {}

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8', ...$headers], json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, ['Content-Type' => 'text/plain; charset=utf-8', ...$headers], $body);
    }

    public static function noContent(): self { return new self(204); }

    public static function problem(int $status, string $title, ?string $detail = null): self
    {
        $payload = ['type' => 'about:blank', 'title' => $title, 'status' => $status];
        if ($detail !== null) { $payload['detail'] = $detail; }
        return new self($status, ['Content-Type' => 'application/problem+json; charset=utf-8'], json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
