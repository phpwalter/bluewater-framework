<?php

declare(strict_types=1);

namespace Bluewater;

final readonly class ApplicationDefinition
{
    public function __construct(
        public string $name,
        public string $namespace,
        public string $root,
        public string $cache,
        public string $logs,
        public string $environment = 'production',
    ) {}

    public function configPath(): string { return $this->root . '/config'; }
    public function endpointPath(): string { return $this->root . '/Endpoints'; }
}
