<?php

/**
 * @file MinLength.php
 * @path src/Validation/MinLength.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the property attribute that enforces a minimum string length.
 */

declare(strict_types=1);

namespace Bluewater\Validation;

use Attribute;
use InvalidArgumentException;

/**
 * Requires a string to contain at least a configured number of characters.
 *
 * Validator measures Unicode characters with mb_strlen() and does not trim or
 * otherwise normalize the value before comparison.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class MinLength
{
    /**
     * Creates a positive minimum-length constraint.
     *
     * @param positive-int $value Minimum Unicode character count.
     *
     * @throws InvalidArgumentException When the minimum is less than one.
     */
    public function __construct(public int $value)
    {
        if ($this->value < 1) {
            throw new InvalidArgumentException('Minimum length must be positive.');
        }
    }
}
