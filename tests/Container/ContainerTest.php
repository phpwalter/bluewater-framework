<?php

/**
 * @file ContainerTest.php
 * @path tests/Container/ContainerTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies the container test behavior and its observable framework contracts.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Container;

use Bluewater\Container\Container;
use PHPUnit\Framework\TestCase;

/** Verifies explicit interface registration and recursive constructor autowiring. */
final class ContainerTest extends TestCase
{
    /** Confirms registered interfaces compose with an autowired concrete service. */
    public function testInterfacesCanBeRegisteredAndConcreteDependenciesAutowire(): void
    {
        $container = new Container();
        $container->instance(Clock::class, new FixedClock());
        $service = $container->get(UsesClock::class);

        self::assertSame('fixed', $service->value());
    }
}

/** Defines the minimal test clock dependency. */
interface Clock
{
    /** Returns the fixture's stable time label. */
    public function now(): string;
}

/** Provides a deterministic clock for container tests. */
final class FixedClock implements Clock
{
    /** @return 'fixed' Stable test value. */
    public function now(): string
    {
        return 'fixed';
    }
}

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
