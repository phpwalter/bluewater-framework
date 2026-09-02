<?php

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
