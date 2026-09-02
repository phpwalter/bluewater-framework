<?php

/**
 * @file oauth.php
 * @path examples/host/app/app_1/Endpoints/oauth.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the example oauth HTTP endpoint and its serialized response contract.
 */

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Auth\Identity;
use Bluewater\Auth\OAuthMiddleware;
use Bluewater\Endpoint\Endpoint;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
use Bluewater\OpenApi\Summary;

/**
 * Reports the identity established by OAuth bearer authentication.
 *
 * The response deliberately excludes raw tokens and provider claims. Middleware
 * denies the request before dispatch when introspection does not authenticate it.
 */
#[UseMiddleware(OAuthMiddleware::class)]
final class Oauth extends Endpoint
{
    /**
     * Returns the authenticated public identity and normalized scopes.
     *
     * @return array{
     *     authenticated: bool,
     *     identity: non-empty-string|null,
     *     scopes: list<non-empty-string>
     * }
     */
    #[Summary('OAuth protected identity endpoint')]
    public function get(Request $request): array
    {
        $identity = $request->attributes['identity'] ?? null;
        return [
            'authenticated' => $identity instanceof Identity,
            'identity' => $identity instanceof Identity ? $identity->id : null,
            'scopes' => $identity instanceof Identity ? $identity->scopes : [],
        ];
    }
}
