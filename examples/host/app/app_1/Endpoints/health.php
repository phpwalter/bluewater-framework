<?php

/**
 * @file health.php
 * @path examples/host/app/app_1/Endpoints/health.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example health HTTP endpoint and its serialized response contract.
 */

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Apps\App1\Services\AppInfo;
use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\Summary;

/**
 * Reports example application identity, environment, and current UTC time.
 *
 * The endpoint performs no dependency checks and therefore represents process
 * liveness rather than database or downstream readiness.
 */
final class Health extends Endpoint
{
    /**
     * Returns a newly generated liveness document.
     *
     * @return array{status: 'ok', app: string, environment: string, time: string}
     *     Public values with an ISO-8601 UTC timestamp.
     */
    #[Summary('Application health check')]
    public function get(AppInfo $app): array
    {
        return [
            'status' => 'ok',
            'app' => $app->name,
            'environment' => $app->environment,
            'time' => gmdate(DATE_ATOM),
        ];
    }
}
