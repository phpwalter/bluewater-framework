<?php

/**
 * @file JwtMiddleware.php
 * @path src/Auth/JwtMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Selects JWT bearer authentication for endpoints protected by the shared authentication middleware.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

/**
 * Protects a route with the provider registered as `jwt`.
 *
 * Bearer-token parsing and cryptographic verification are delegated to JwtProvider.
 */
final class JwtMiddleware extends AuthenticationMiddleware
{
    /** @return 'jwt' Canonical JWT strategy identifier. */
    protected function strategy(): string
    {
        return 'jwt';
    }
}
