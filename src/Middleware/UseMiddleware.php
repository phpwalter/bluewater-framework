<?php

declare(strict_types=1);

namespace Bluewater\Middleware;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class UseMiddleware
{
    public function __construct(public string $middleware) {}
}
