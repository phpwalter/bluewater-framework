<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\OpenApiGenerator;
use Bluewater\OpenApi\Summary;

final class Openapi extends Endpoint
{
    #[Summary('OpenAPI 3.1 document')]
    public function get(OpenApiGenerator $generator): array
    {
        return $generator->generate();
    }
}
