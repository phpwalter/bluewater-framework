<?php

/**
 * @file OAuthIntrospector.php
 * @path src/Auth/OAuthIntrospector.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the boundary for validating opaque OAuth bearer tokens without coupling the framework to a provider.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

/**
 * Defines opaque OAuth token validation at the provider integration boundary.
 *
 * Implementations own network I/O, provider authentication, timeout policy,
 * and response validation. A null result denotes expected token denial.
 */
interface OAuthIntrospector
{
    /**
     * Validates one raw bearer token with the configured authorization server.
     *
     * @param non-empty-string $token Untrusted credential. Implementations must
     *     not retain, log, or include it in exception messages.
     *
     * @return array<string, mixed>|null Validated introspection claims, or null
     *     when the authorization server denies the token.
     */
    public function introspect(string $token): ?array;
}
