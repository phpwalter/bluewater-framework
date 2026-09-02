<?php

/**
 * @file AppInfo.php
 * @path examples/host/app/app_1/Services/AppInfo.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example app info application service and its domain boundary.
 */

declare(strict_types=1);

namespace Apps\App1\Services;

/**
 * Exposes immutable non-sensitive application identity to endpoint handlers.
 *
 * Values are copied from ApplicationDefinition and are safe for public health output.
 */
final readonly class AppInfo
{
    /**
     * @param non-empty-string $name Hosted application identifier.
     * @param non-empty-string $environment Runtime environment name.
     */
    public function __construct(
        public string $name,
        public string $environment,
    ) {
    }
}
