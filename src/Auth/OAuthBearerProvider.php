<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

final class OAuthBearerProvider implements AuthenticationProvider
{
    public function __construct(private readonly OAuthIntrospector $introspector) {}

    public function authenticate(Request $request): ?Identity
    {
        $authorization = $request->header('Authorization');
        if ($authorization === null || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $m)) { return null; }
        $claims = $this->introspector->introspect($m[1]);
        if (!is_array($claims) || ($claims['active'] ?? true) !== true) { return null; }
        $scopes = $claims['scope'] ?? $claims['scopes'] ?? [];
        if (is_string($scopes)) { $scopes = preg_split('/\s+/', trim($scopes)) ?: []; }
        return new Identity((string) ($claims['sub'] ?? $claims['client_id'] ?? 'oauth'), $claims, (array) $scopes);
    }
}
