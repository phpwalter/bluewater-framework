<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Middleware;

abstract class AuthenticationMiddleware implements Middleware
{
    public function __construct(private readonly AuthManager $auth) {}

    abstract protected function strategy(): string;

    public function process(Request $request, callable $next): Response
    {
        $identity = $this->auth->authenticate($this->strategy(), $request);
        if ($identity === null) {
            return Response::problem(401, 'Unauthorized');
        }
        return $next($request->withAttributes(['identity' => $identity]));
    }
}
