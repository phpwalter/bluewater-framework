<?php

/**
 * @file AuthenticationMiddleware.php
 * @path src/Auth/AuthenticationMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines fail-closed middleware behavior shared by concrete authentication strategies.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Middleware;

/**
 * Enforces one configured authentication strategy before invoking a handler.
 *
 * Missing or invalid credentials return an RFC 7807-style 401 response and do
 * not invoke downstream middleware. Accepted identities are attached to a new
 * immutable request under the `identity` attribute. Authorization remains the
 * responsibility of later middleware or the endpoint.
 */
abstract class AuthenticationMiddleware implements Middleware
{
    /** Retains the authentication registry without performing I/O. */
    public function __construct(private readonly AuthManager $auth)
    {
    }

    /** @return non-empty-string Canonical registered authentication strategy. */
    abstract protected function strategy(): string;

    /**
     * Authenticates the request and invokes the downstream handler on success.
     *
     * @param callable(Request): Response $next Synchronous downstream handler;
     *     invoked exactly once on success and never on denial.
     *
     * @return Response Downstream response or a credential-free 401 problem.
     */
    public function process(Request $request, callable $next): Response
    {
        $identity = $this->auth->authenticate($this->strategy(), $request);
        if ($identity === null) {
            return Response::problem(401, 'Unauthorized');
        }
        return $next($request->withAttributes(['identity' => $identity]));
    }
}
