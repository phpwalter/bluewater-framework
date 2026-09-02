<?php

/**
 * @file ApiKeyProvider.php
 * @path src/Auth/ApiKeyProvider.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Authenticates requests against configured API keys and creates immutable caller identities.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use InvalidArgumentException;

/**
 * Authenticates an API key supplied in one configured request header.
 *
 * Configured keys are validated eagerly and retained only in memory. Incoming
 * keys are compared with hash_equals(), and all configured candidates are
 * checked to avoid leaking the matching key's position. Missing, unknown, or
 * ambiguous credentials are denied. This provider authenticates identities;
 * it does not authorize scopes.
 */
final class ApiKeyProvider implements AuthenticationProvider
{
    /**
     * Creates a provider from an API-key-to-identity map.
     *
     * @param array<non-empty-string, non-empty-string|array{
     *     id?: non-empty-string,
     *     claims?: array<string, mixed>,
     *     scopes?: list<string>
     * }> $keys Raw API keys mapped to identity definitions. Keys must never be
     *     logged or serialized.
     * @param non-empty-string $header Case-insensitive request header name.
     *
     * @throws InvalidArgumentException When no key is configured or a key,
     *     header name, or resolved identity identifier is blank.
     */
    public function __construct(
        private readonly array $keys,
        private readonly string $header = 'X-API-Key',
    ) {
        if ($this->keys === []) {
            throw new InvalidArgumentException('At least one API key must be configured.');
        }
        if (trim($this->header) === '') {
            throw new InvalidArgumentException('The API-key header name must not be blank.');
        }

        foreach ($this->keys as $key => $definition) {
            if (!is_string($key) || $key === '') {
                throw new InvalidArgumentException('Configured API keys must be non-empty strings.');
            }
            if (is_string($definition) && trim($definition) === '') {
                throw new InvalidArgumentException('API-key identity identifiers must not be blank.');
            }
        }
    }

    /**
     * Authenticates the API key without exposing it in the result or failures.
     *
     * @return Identity|null Immutable configured identity, or null when the
     *     header is absent or no configured key matches.
     */
    public function authenticate(Request $request): ?Identity
    {
        $key = $request->header($this->header);
        if ($key === null || $key === '') {
            return null;
        }

        $definition = null;
        foreach ($this->keys as $candidate => $candidateDefinition) {
            if (hash_equals((string) $candidate, $key)) {
                $definition = $candidateDefinition;
            }
        }
        if ($definition === null) {
            return null;
        }
        if (is_string($definition)) {
            return new Identity($definition);
        }

        return new Identity(
            (string) ($definition['id'] ?? 'api-key'),
            (array) ($definition['claims'] ?? []),
            (array) ($definition['scopes'] ?? []),
        );
    }
}
