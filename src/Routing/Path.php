<?php

declare(strict_types=1);

namespace Bluewater\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Path
{
    public function __construct(public string $value) {}
}
