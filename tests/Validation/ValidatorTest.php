<?php

/**
 * @file ValidatorTest.php
 * @path tests/Validation/ValidatorTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies required, email, minimum-length, and structured error contracts.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Validation;

use Bluewater\Validation\ValidationException;
use Bluewater\Validation\Validator;
use PHPUnit\Framework\TestCase;

/** Verifies that Validator accumulates every supported field failure. */
final class ValidatorTest extends TestCase
{
    /** Confirms whitespace, malformed email, and short text produce stable errors. */
    public function testInvalidFieldsAreAccumulated(): void
    {
        $validator = new Validator();

        try {
            $validator->validate(new ValidationFixture(' ', 'invalid', 'x'));
            self::fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            self::assertSame(['This value is required.'], $exception->errors['required']);
            self::assertSame(['Invalid email address.'], $exception->errors['email']);
            self::assertSame(['Minimum length is 2.'], $exception->errors['name']);
        }
    }
}
