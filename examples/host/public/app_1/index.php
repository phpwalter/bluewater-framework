<?php

/**
 * @file index.php
 * @path examples/host/public/app_1/index.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Runs the example application through the PHP-FPM runtime adapter after validating required environment configuration.
 */

declare(strict_types=1);

use Bluewater\Host;
use Bluewater\Runtime\FpmAdapter;

$packageRoot = dirname(__DIR__, 4);
require $packageRoot . '/vendor/autoload.php';

if (getenv('BLUEWATER_APP_BASE') === false) {
    putenv('BLUEWATER_APP_BASE=' . dirname(__DIR__, 2) . '/app');
}

$appName = getenv('BLUEWATER_APP');
if (!is_string($appName) || $appName === '') {
    throw new RuntimeException('BLUEWATER_APP must be configured by the web server/FPM pool.');
}

Host::fromEnvironment()
    ->application($appName)
    ->run(new FpmAdapter());
