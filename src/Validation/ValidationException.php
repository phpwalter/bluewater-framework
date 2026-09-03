<?php

/**
 * @file ValidationException.php
 * @path src/Validation/ValidationException.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the structured exception that reports every field-level DTO validation failure.
 */

declare(strict_types=1);

namespace Bluewater\Validation;

use RuntimeException;

/**
 * Reports every field-level validation failure for one attempted DTO value.
 *
 * Error keys are property names and values are non-empty ordered message lists.
 * The exception contains no request bodies, credentials, or property values.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<non-empty-string, non-empty-list<non-empty-string>> $errors
     *     Field errors in reflection and validator evaluation order.
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Request validation failed.');
    }
}
