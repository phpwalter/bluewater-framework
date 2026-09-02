<?php

/**
 * @file Path.php
 * @path src/Routing/Path.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the endpoint-method attribute used to append an explicit route path template.
 */

declare(strict_types=1);

namespace Bluewater\Routing;

use Attribute;
use InvalidArgumentException;

/**
 * Appends an explicit path template to an endpoint resource path.
 *
 * Router removes leading separators, collapses repeated separators, and validates
 * every `{parameter}` token against the attributed handler's parameters.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Path
{
    /**
     * Creates a non-blank route suffix without performing route discovery.
     *
     * @param non-empty-string $value Relative or slash-prefixed path template.
     *
     * @throws InvalidArgumentException When the template is blank.
     */
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException('A route path template must not be blank.');
        }
    }
}
