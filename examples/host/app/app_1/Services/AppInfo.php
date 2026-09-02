<?php

declare(strict_types=1);

namespace Apps\App1\Services;

final readonly class AppInfo
{
    public function __construct(
        public string $name,
        public string $environment,
    ) {}
}
