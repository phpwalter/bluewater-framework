<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use RuntimeException;

final class AuthManager
{
    /** @var array<string, AuthenticationProvider> */
    private array $providers = [];

    public function register(string $name, AuthenticationProvider $provider): self
    {
        $this->providers[strtolower($name)] = $provider;
        return $this;
    }

    public function authenticate(string $name, Request $request): ?Identity
    {
        $provider = $this->providers[strtolower($name)] ?? null;
        if ($provider === null) {
            throw new RuntimeException("Authentication provider '{$name}' is not registered.");
        }
        return $provider->authenticate($request);
    }
}
