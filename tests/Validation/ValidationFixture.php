<?php

/**
 * @file ValidationFixture.php
 * @path tests/Validation/ValidationFixture.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines an immutable attributed DTO used to verify validation failures.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Validation;

use Bluewater\Validation\Email;
use Bluewater\Validation\MinLength;
use Bluewater\Validation\Required;

/** Supplies attributed immutable properties to Validator. */
final readonly class ValidationFixture
{
    /** Creates a fixture without normalizing its deliberately invalid values. */
    public function __construct(
        #[Required]
        public string $required,
        #[Email]
        public string $email,
        #[MinLength(2)]
        public string $name,
    ) {
    }
}
