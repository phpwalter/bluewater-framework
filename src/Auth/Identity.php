<?php

declare(strict_types=1);

namespace Bluewater\Auth;

final readonly class Identity
{
    public function __construct(
        public string $id,
        public array $claims = [],
        public array $scopes = [],
    ) {}
}
