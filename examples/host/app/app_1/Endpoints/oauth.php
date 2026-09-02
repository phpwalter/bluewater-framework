<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Auth\Identity;
use Bluewater\Auth\OAuthMiddleware;
use Bluewater\Endpoint\Endpoint;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
use Bluewater\OpenApi\Summary;

#[UseMiddleware(OAuthMiddleware::class)]
final class Oauth extends Endpoint
{
    #[Summary('OAuth protected identity endpoint')]
    public function get(Request $request): array
    {
        $identity = $request->attributes['identity'] ?? null;
        return [
            'authenticated' => $identity instanceof Identity,
            'identity' => $identity instanceof Identity ? $identity->id : null,
            'claims' => $identity instanceof Identity ? $identity->claims : [],
        ];
    }
}
