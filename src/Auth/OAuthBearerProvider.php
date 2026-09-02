<?php

/**
 * @file OAuthBearerProvider.php
 * @path src/Auth/OAuthBearerProvider.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Authenticates OAuth bearer tokens through an injected introspection boundary.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

/**
 * Authenticates opaque OAuth bearer tokens through a configured introspector.
 *
 * The provider fails closed unless introspection returns literal active=true
 * and a non-blank subject or client identifier. It forwards the raw token only
 * to the introspector and never copies it into the resulting identity.
 */
final class OAuthBearerProvider implements AuthenticationProvider
{
    /** Retains the introspector without invoking it during construction. */
    public function __construct(private readonly OAuthIntrospector $introspector)
    {
    }

    /**
     * Introspects a bearer token and maps accepted claims to an identity.
     *
     * The introspector is called exactly once when a non-blank bearer token is
     * present. Operational exceptions from that collaborator may escape.
     *
     * @return Identity|null Validated OAuth identity, or null for expected denial.
     */
    public function authenticate(Request $request): ?Identity
    {
        $authorization = $request->header('Authorization');
        if ($authorization === null || preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches) !== 1) {
            return null;
        }

        $claims = $this->introspector->introspect($matches[1]);
        if (!is_array($claims) || ($claims['active'] ?? false) !== true) {
            return null;
        }

        $subject = $claims['sub'] ?? $claims['client_id'] ?? null;
        if (!is_string($subject) || trim($subject) === '') {
            return null;
        }

        $scopes = $claims['scope'] ?? $claims['scopes'] ?? [];
        if (is_string($scopes)) {
            $scopes = preg_split('/\s+/', trim($scopes)) ?: [];
        }

        return new Identity($subject, $claims, is_array($scopes) ? $scopes : []);
    }
}
