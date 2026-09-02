<?php

declare(strict_types=1);

namespace Bluewater\Auth;

final class ApiKeyMiddleware extends AuthenticationMiddleware
{
    protected function strategy(): string { return 'api_key'; }
}
