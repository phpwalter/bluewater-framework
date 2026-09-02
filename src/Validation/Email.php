<?php

/**
 * @file Email.php
 * @path src/Validation/Email.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the property attribute that requires a syntactically valid email address.
 */

declare(strict_types=1);

namespace Bluewater\Validation;

use Attribute;

/**
 * Requires an initialized value to be a syntactically valid email string.
 *
 * Validator uses PHP's FILTER_VALIDATE_EMAIL rule and reports a field error;
 * the attribute does not normalize, deliver to, or prove ownership of an address.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Email
{
}
