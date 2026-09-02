<?php

declare(strict_types=1);

namespace Bluewater\Runtime;

use Bluewater\Http\Request;
use Bluewater\Http\Response;

interface RuntimeAdapter
{
    public function request(): Request;
    public function emit(Response $response): void;
}
