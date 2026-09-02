<?php

/**
 * @file UsesClock.php
 * @path tests/Container/UsesClock.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the constructor-injected service used by container autowiring tests.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Container;

/** Demonstrates constructor injection of an interface-bound dependency. */
final class UsesClock
{
    /** Retains the injected clock without invoking it. */
    public function __construct(private readonly Clock $clock)
    {
    }

    /** Returns the value produced by the injected clock. */
    public function value(): string
    {
        return $this->clock->now();
    }
}
