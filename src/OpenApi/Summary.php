<?php

declare(strict_types=1);

namespace Bluewater\OpenApi;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Summary
{
    public function __construct(public string $value) {}
}
