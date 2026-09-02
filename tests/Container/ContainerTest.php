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
