<?php

declare(strict_types=1);

namespace Bluewater\Tests\Container;

use Bluewater\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testInterfacesCanBeRegisteredAndConcreteDependenciesAutowire(): void
    {
        $container = new Container();
        $container->instance(Clock::class, new FixedClock());
        $service = $container->get(UsesClock::class);

        self::assertSame('fixed', $service->value());
    }
}

interface Clock { public function now(): string; }
final class FixedClock implements Clock { public function now(): string { return 'fixed'; } }
final class UsesClock
{
    public function __construct(private readonly Clock $clock) {}
    public function value(): string { return $this->clock->now(); }
}
