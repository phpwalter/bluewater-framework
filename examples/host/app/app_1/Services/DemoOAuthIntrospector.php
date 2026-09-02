<?php

declare(strict_types=1);

namespace Apps\App1\Services;

use Bluewater\Auth\OAuthIntrospector;

final class DemoOAuthIntrospector implements OAuthIntrospector
{
    public function introspect(string $token): ?array
    {
        if ($token !== 'demo-oauth-token') { return null; }
        return [
            'active' => true,
            'sub' => 'oauth-demo-user',
            'scope' => 'users.read users.write',
        ];
    }
}
