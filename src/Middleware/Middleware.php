<?php

declare(strict_types=1);

namespace Bluewater\Middleware;

use Bluewater\Http\Request;
use Bluewater\Http\Response;

interface Middleware
{
    public function process(Request $request, callable $next): Response;
}
