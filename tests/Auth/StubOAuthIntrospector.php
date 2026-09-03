<?php

/**
 * @file StubOAuthIntrospector.php
 * @path tests/Auth/StubOAuthIntrospector.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines a deterministic OAuth introspection test double that performs no I/O.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Auth;

use Bluewater\Auth\OAuthIntrospector;

/** Returns one deterministic OAuth introspection result without performing I/O. */
final readonly class StubOAuthIntrospector implements OAuthIntrospector
{
    /** @param array<string, mixed>|null $claims Result returned for every token. */
    public function __construct(private ?array $claims)
    {
    }

    /** @inheritDoc */
    public function introspect(string $token): ?array
    {
        return $this->claims;
    }
}
