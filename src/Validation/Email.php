<?php

declare(strict_types=1);

namespace Bluewater\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class Email {}
