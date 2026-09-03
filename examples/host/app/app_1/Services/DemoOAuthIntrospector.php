<?php

/**
 * @file DemoOAuthIntrospector.php
 * @path examples/host/app/app_1/Services/DemoOAuthIntrospector.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example demo oauth introspector application service and its domain boundary.
 */

declare(strict_types=1);

namespace Apps\App1\Services;

use Bluewater\Auth\OAuthIntrospector;

/**
 * Demonstrates OAuth introspection with one fixed, local example credential.
 *
 * This implementation performs no network I/O and is intentionally unsuitable
 * for production. It compares the token in constant time and never exposes it.
 */
final class DemoOAuthIntrospector implements OAuthIntrospector
{
    /** @inheritDoc */
    public function introspect(string $token): ?array
    {
        if (!hash_equals('demo-oauth-token', $token)) {
            return null;
        }

        return [
            'active' => true,
            'sub' => 'oauth-demo-user',
            'scope' => 'users.read users.write',
        ];
    }
}
