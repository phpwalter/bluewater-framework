<?php

declare(strict_types=1);

namespace Bluewater\Validation;

use ReflectionClass;
use RuntimeException;

final class Validator
{
    public function validate(object $value): void
    {
        $errors = [];
        $ref = new ReflectionClass($value);
        foreach ($ref->getProperties() as $property) {
            if (!$property->isInitialized($value)) { continue; }
            $current = $property->getValue($value);
            if ($property->getAttributes(Required::class) !== [] && ($current === null || $current === '')) {
                $errors[$property->getName()][] = 'This value is required.';
            }
            if ($property->getAttributes(Email::class) !== [] && is_string($current) && filter_var($current, FILTER_VALIDATE_EMAIL) === false) {
                $errors[$property->getName()][] = 'Invalid email address.';
            }
            foreach ($property->getAttributes(MinLength::class) as $attribute) {
                $min = $attribute->newInstance()->value;
                if (is_string($current) && mb_strlen($current) < $min) {
                    $errors[$property->getName()][] = "Minimum length is {$min}.";
                }
            }
        }
        if ($errors !== []) { throw new ValidationException($errors); }
    }
}

final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Request validation failed.');
    }
}
