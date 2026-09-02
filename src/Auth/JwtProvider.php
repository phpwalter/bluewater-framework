<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

final class JwtProvider implements AuthenticationProvider
{
    public function __construct(
        private readonly string $secret,
        private readonly string $algorithm = 'HS256',
    ) {}

    public function authenticate(Request $request): ?Identity
    {
        $authorization = $request->header('Authorization');
        if ($authorization === null || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $m)) { return null; }
        if ($this->algorithm !== 'HS256') { return null; }

        $parts = explode('.', $m[1]);
        if (count($parts) !== 3) { return null; }
        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode($this->decode($encodedHeader), true);
        $claims = json_decode($this->decode($encodedPayload), true);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? null) !== 'HS256') { return null; }

        $expected = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);
        if (!hash_equals($expected, $this->decode($encodedSignature))) { return null; }
        if (isset($claims['exp']) && (int) $claims['exp'] < time()) { return null; }
        if (isset($claims['nbf']) && (int) $claims['nbf'] > time()) { return null; }

        $scopes = $claims['scope'] ?? $claims['scopes'] ?? [];
        if (is_string($scopes)) { $scopes = preg_split('/\s+/', trim($scopes)) ?: []; }
        return new Identity((string) ($claims['sub'] ?? 'jwt'), $claims, (array) $scopes);
    }

    private function decode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding !== 0) { $value .= str_repeat('=', 4 - $padding); }
        return base64_decode($value, true) ?: '';
    }
}
