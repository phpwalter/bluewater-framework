<?php

/**
 * @file secure.php
 * @path examples/host/app/app_1/Endpoints/secure.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example secure HTTP endpoint and its serialized response contract.
 */

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Auth\Identity;
use Bluewater\Auth\JwtMiddleware;
use Bluewater\Endpoint\Endpoint;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
use Bluewater\OpenApi\Summary;

/**
 * Reports the identity established by JWT bearer authentication.
 *
 * The response excludes raw tokens and private claims. Middleware denies the
 * request before dispatch unless every configured JWT check succeeds.
 */
#[UseMiddleware(JwtMiddleware::class)]
final class Secure extends Endpoint
{
    /**
     * @return array{
     *     authenticated: bool,
     *     identity: non-empty-string|null,
     *     scopes: list<non-empty-string>
     * }
     */
    #[Summary('JWT protected identity endpoint')]
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
