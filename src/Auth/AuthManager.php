<?php

/**
 * @file AuthManager.php
 * @path src/Auth/AuthManager.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Registers named authentication providers and resolves the provider required for each authentication attempt.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * Stores authentication providers under canonical strategy identifiers.
 *
 * Strategy names are trimmed and converted to lowercase ASCII before storage
 * and lookup. Duplicate registrations are rejected, provider order never
 * affects selection, and unknown strategies fail closed with an exception.
 * The manager delegates credential validation to the selected provider and
 * does not perform authorization.
 */
final class AuthManager
{
    /** @var array<non-empty-string, AuthenticationProvider> */
    private array $providers = [];

    /**
     * Registers one provider under a unique canonical strategy name.
     *
     * This method mutates the registry only after validation succeeds. It is
     * not idempotent: repeated registration of the same canonical name fails.
     *
     * @param non-empty-string $name Strategy name normalized with trim and strtolower.
     *
     * @return $this Mutated manager for fluent bootstrap configuration.
     *
     * @throws InvalidArgumentException When the normalized name is blank.
     * @throws LogicException When the canonical name is already registered.
     */
    public function register(string $name, AuthenticationProvider $provider): self
    {
        $name = $this->normalizeName($name);
        if (isset($this->providers[$name])) {
            throw new LogicException("Authentication provider '{$name}' is already registered.");
        }

        $this->providers[$name] = $provider;

        return $this;
    }

    /**
     * Authenticates a request with exactly one explicitly selected provider.
     *
     * No default or fallback provider is attempted. Expected credential denial
     * is returned as null; exceptions from the selected provider may escape.
     *
     * @param non-empty-string $name Strategy name normalized before exact lookup.
     *
     * @return Identity|null Authenticated identity, or null for expected denial.
     *
     * @throws InvalidArgumentException When the normalized name is blank.
     * @throws RuntimeException When no provider is registered for the strategy.
     */
    public function authenticate(string $name, Request $request): ?Identity
    {
        $name = $this->normalizeName($name);
        $provider = $this->providers[$name] ?? null;
        if ($provider === null) {
            throw new RuntimeException("Authentication provider '{$name}' is not registered.");
        }

        return $provider->authenticate($request);
    }

    /** @return non-empty-string Canonical lowercase strategy identifier. */
    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            throw new InvalidArgumentException('Authentication provider names must not be blank.');
        }

        return $name;
    }
}
