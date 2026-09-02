<?php

/**
 * @file Extension.php
 * @path src/Extension/Extension.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the two-phase registration and boot lifecycle required of framework extensions.
 */

declare(strict_types=1);

namespace Bluewater\Extension;

use Bluewater\Application;

/**
 * Defines a two-phase extension lifecycle for a hosted application.
 *
 * Implementations may register services during register() and perform work that
 * depends on route discovery during boot(). ExtensionManager preserves declared
 * order and does not roll back side effects when a later extension fails.
 */
interface Extension
{
    /** Registers services or framework integrations before route discovery. */
    public function register(Application $app): void;

    /** Performs post-discovery initialization after every extension registers. */
    public function boot(Application $app): void;
}
