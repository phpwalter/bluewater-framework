<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Apps\App1\Services\AppInfo;
use Bluewater\Endpoint\Endpoint;
use Bluewater\OpenApi\Summary;

final class Health extends Endpoint
{
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
