<?php

declare(strict_types=1);

namespace Bluewater\Auth;

final class OAuthMiddleware extends AuthenticationMiddleware
{
    protected function strategy(): string { return 'oauth'; }
}
