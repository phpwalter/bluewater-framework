<?php

declare(strict_types=1);

namespace Apps\App1\Extensions;

use Apps\App1\Services\AppInfo;
use Bluewater\Application;
use Bluewater\Extension\Extension;
use Psr\Log\LoggerInterface;

final class AppInfoExtension implements Extension
{
    public function register(Application $app): void
    {
        $app->services()->instance(
            AppInfo::class,
            new AppInfo($app->definition()->name, $app->definition()->environment),
        );
    }

    public function boot(Application $app): void
    {
        $app->services()->get(LoggerInterface::class)->debug('Application extension booted', [
            'app' => $app->definition()->name,
        ]);
    }
}
