<?php

/**
 * @file OAuthMiddleware.php
 * @path src/Auth/OAuthMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Selects OAuth bearer authentication for endpoints protected by the shared authentication middleware.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

/**
 * Protects a route with the provider registered as `oauth`.
 *
 * Bearer-token validation is delegated through OAuthBearerProvider.
 */
final class OAuthMiddleware extends AuthenticationMiddleware
{
    /** @return 'oauth' Canonical OAuth strategy identifier. */
    protected function strategy(): string
    {
        return 'oauth';
    }
}
