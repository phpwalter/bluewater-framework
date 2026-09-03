<?php

/**
 * @file Validator.php
 * @path src/Validation/Validator.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Validates DTO property attributes and reports all field failures through a structured exception.
 */

declare(strict_types=1);

namespace Bluewater\Validation;

use ReflectionClass;

/**
 * Evaluates supported validation attributes on initialized object properties.
 *
 * All properties are inspected and all detected field failures are accumulated
 * before one ValidationException is thrown. Uninitialized properties and
 * unsupported attributes are ignored. Validation performs no mutation, I/O,
 * authentication, or presentation-layer formatting.
 */
final class Validator
{
    /**
     * Validates Required, Email, and MinLength constraints on an object.
     *
     * @throws ValidationException When one or more fields violate constraints.
     */
    public function validate(object $value): void
    {
        $errors = [];
        $ref = new ReflectionClass($value);
        foreach ($ref->getProperties() as $property) {
            if (!$property->isInitialized($value)) {
                continue;
            }

            $current = $property->getValue($value);
            if (
                $property->getAttributes(Required::class) !== []
                && ($current === null || (is_string($current) && trim($current) === ''))
            ) {
                $errors[$property->getName()][] = 'This value is required.';
            }
            if (
                $property->getAttributes(Email::class) !== []
                && (!is_string($current) || filter_var($current, FILTER_VALIDATE_EMAIL) === false)
            ) {
                $errors[$property->getName()][] = 'Invalid email address.';
            }
            foreach ($property->getAttributes(MinLength::class) as $attribute) {
                $min = $attribute->newInstance()->value;
                if (is_string($current) && mb_strlen($current) < $min) {
                    $errors[$property->getName()][] = "Minimum length is {$min}.";
                }
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
