<?php

/**
 * @file openapi.php
 * @path examples/host/app/app_1/Endpoints/openapi.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example openapi HTTP endpoint and its serialized response contract.
 */

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\OpenApiGenerator;
use Bluewater\OpenApi\Summary;

/** Exposes the current route-derived OpenAPI document for the example application. */
final class Openapi extends Endpoint
{
    /** Returns a newly generated OpenAPI document without caching it. */
    #[Summary('OpenAPI 3.1 document')]
    public function get(OpenApiGenerator $generator): array
    {
        return $generator->generate();
    }
}
