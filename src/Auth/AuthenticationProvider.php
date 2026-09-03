<?php

/**
 * @file AuthenticationProvider.php
 * @path src/Auth/AuthenticationProvider.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the contract for converting untrusted HTTP authentication data into an authenticated identity or denial.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

/**
 * Authenticates untrusted request credentials without performing authorization.
 *
 * Implementations return null for expected credential denial and an Identity
 * only after every check owned by that provider succeeds. They must never place
 * raw credentials in an identity, exception, log entry, or serialized result.
 */
interface AuthenticationProvider
{
    /**
     * Attempts to authenticate credentials carried by the request.
     *
     * @return Identity|null Validated identity, or null when credentials are
     *     missing, malformed, inactive, expired, or otherwise denied.
     */
    public function authenticate(Request $request): ?Identity;
}
