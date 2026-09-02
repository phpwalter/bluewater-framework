<?php

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;

interface AuthenticationProvider
{
    public function authenticate(Request $request): ?Identity;
}
