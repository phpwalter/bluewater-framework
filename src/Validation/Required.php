<?php

/**
 * @file Required.php
 * @path src/Validation/Required.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the property attribute that rejects missing, null, or blank property values.
 */

declare(strict_types=1);

namespace Bluewater\Validation;

use Attribute;

/**
 * Requires an initialized value to be neither null nor a blank string.
 *
 * Validator trims string whitespace for this check. Other false-like values,
 * including zero and false, remain valid.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Required
{
}
