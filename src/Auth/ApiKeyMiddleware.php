<?php

/**
 * @file ApiKeyMiddleware.php
 * @path src/Auth/ApiKeyMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Selects API-key authentication for endpoints protected by the shared authentication middleware.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

/**
 * Protects a route with the provider registered as `api_key`.
 *
 * Credential parsing and comparison are delegated to ApiKeyProvider.
 */
final class ApiKeyMiddleware extends AuthenticationMiddleware
{
    /** @return 'api_key' Canonical API-key strategy identifier. */
    protected function strategy(): string
    {
        return 'api_key';
    }
}
