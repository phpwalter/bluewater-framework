<?php

/**
 * @file ApplicationBootstrap.php
 * @path src/ApplicationBootstrap.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the host-application bootstrap contract for dependency registration and post-discovery initialization.
 */

declare(strict_types=1);

namespace Bluewater;

/**
 * Defines the lifecycle contract implemented by every hosted application.
 *
 * Registration runs before extension registration and route discovery; boot
 * runs after extensions have booted. Implementations may configure the
 * supplied application but must not replace it or run its runtime adapter.
 */
interface ApplicationBootstrap
{
    /**
     * Registers application-owned services, middleware, and extensions.
     *
     * This method may mutate the application's collaborators and may perform
     * application-specific I/O. It is invoked exactly once by Application.
     */
    public function register(Application $app): void;

    /**
     * Performs application initialization that depends on discovered routes.
     *
     * This method is invoked after every extension has booted. Implementations
     * must leave the application ready to accept requests when it returns.
     */
    public function boot(Application $app): void;
}
