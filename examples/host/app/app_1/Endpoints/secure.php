<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Auth\Identity;
use Bluewater\Auth\JwtMiddleware;
use Bluewater\Endpoint\Endpoint;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
use Bluewater\OpenApi\Summary;

#[UseMiddleware(JwtMiddleware::class)]
final class Secure extends Endpoint
{
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
