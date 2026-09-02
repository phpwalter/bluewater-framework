<?php

/**
 * @file UseMiddleware.php
 * @path src/Middleware/UseMiddleware.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the repeatable attribute used to attach middleware classes to endpoint types and methods.
 */

declare(strict_types=1);

namespace Bluewater\Middleware;

use Attribute;
use InvalidArgumentException;

/**
 * Attaches one middleware class to an endpoint type or handler method.
 *
 * Multiple attributes retain declaration order. Router combines directory,
 * class, and method middleware in that precedence order and Pipeline resolves
 * each class at request time.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class UseMiddleware
{
    /**
     * Creates a middleware declaration without resolving the class.
     *
     * @param class-string<Middleware> $middleware Non-blank middleware class name.
     *
     * @throws InvalidArgumentException When the class name is blank.
     */
    public function __construct(public string $middleware)
    {
        if (trim($this->middleware) === '') {
            throw new InvalidArgumentException('A middleware class name must not be blank.');
        }
    }
}
