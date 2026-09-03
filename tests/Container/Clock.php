<?php

/**
 * @file Clock.php
 * @path tests/Container/Clock.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the minimal clock contract used by container autowiring tests.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Container;

/** Defines the minimal test clock dependency. */
interface Clock
{
    /** Returns the fixture's stable time label. */
    public function now(): string;
}
