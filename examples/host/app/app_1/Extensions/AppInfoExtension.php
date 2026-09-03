<?php

/**
 * @file AppInfoExtension.php
 * @path examples/host/app/app_1/Extensions/AppInfoExtension.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example app info extension extension and its application lifecycle effects.
 */

declare(strict_types=1);

namespace Apps\App1\Extensions;

use Apps\App1\Services\AppInfo;
use Bluewater\Application;
use Bluewater\Extension\Extension;
use Psr\Log\LoggerInterface;

/**
 * Publishes immutable application metadata and records extension startup.
 *
 * Registration adds AppInfo to the application container. Boot writes one
 * debug entry through the configured logger and performs no other state change.
 */
final class AppInfoExtension implements Extension
{
    /** Stores one reusable AppInfo instance in the application container. */
    public function register(Application $app): void
    {
        $app->services()->instance(
            AppInfo::class,
            new AppInfo($app->definition()->name, $app->definition()->environment),
        );
    }

    /** Writes one non-sensitive debug event after route discovery. */
    public function boot(Application $app): void
    {
        $logger = $app->services()->get(LoggerInterface::class);
        if (!$logger instanceof LoggerInterface) {
            throw new \RuntimeException('The logger service must implement LoggerInterface.');
        }
        $logger->debug('Application extension booted', [
            'app' => $app->definition()->name,
        ]);
    }
}
