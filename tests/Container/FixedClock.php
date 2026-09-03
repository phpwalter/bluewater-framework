<?php

/**
 * @file FixedClock.php
 * @path tests/Container/FixedClock.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the deterministic clock implementation used by container tests.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Container;

/** Provides a deterministic clock for container tests. */
final class FixedClock implements Clock
{
    /** @return 'fixed' Stable test value. */
    public function now(): string
    {
        return 'fixed';
    }
}
