<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

final class ApiKeyProvider implements AuthenticationProvider
{
    /** @param array<string, string|array> $keys */
    public function __construct(
        private readonly array $keys,
        private readonly string $header = 'X-API-Key',
    ) {}

    public function authenticate(Request $request): ?Identity
    {
        $key = $request->header($this->header);
        if ($key === null || !array_key_exists($key, $this->keys)) { return null; }
        $definition = $this->keys[$key];
        if (is_string($definition)) { return new Identity($definition); }
        return new Identity(
            (string) ($definition['id'] ?? 'api-key'),
            (array) ($definition['claims'] ?? []),
            (array) ($definition['scopes'] ?? []),
        );
    }
}
