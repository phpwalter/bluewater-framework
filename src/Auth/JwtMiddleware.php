<?php

declare(strict_types=1);

namespace Bluewater\Auth;

final class JwtMiddleware extends AuthenticationMiddleware
{
    protected function strategy(): string { return 'jwt'; }
}
