<?php

declare(strict_types=1);

namespace Bluewater\Routing;

final readonly class Route
{
    public function __construct(
        public string $httpMethod,
        public string $path,
        public string $regex,
        public string $file,
        public string $class,
        public string $method,
        public array $parameters = [],
        public array $middleware = [],
    ) {}
}
