<?php

/**
 * @file ExtensionManager.php
 * @path src/Extension/ExtensionManager.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Registers, resolves, and executes extensions in deterministic insertion order.
 */

declare(strict_types=1);

namespace Bluewater\Extension;

use Bluewater\Application;
use Bluewater\Container\Container;
use RuntimeException;

/**
 * Resolves and executes application extensions in insertion order.
 *
 * Entries may be Extension instances or class names resolved lazily through the
 * container. Resolved instances replace their class-name entries and are reused
 * by later lifecycle phases. Duplicates remain duplicates and execute more than
 * once; the manager performs no rollback after a lifecycle failure.
 */
final class ExtensionManager
{
    /** @var list<class-string|Extension> */
    private array $extensions = [];

    /** Retains the resolution container without resolving extensions. */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * Appends an extension to the ordered registry without resolving it.
     *
     * @param class-string|Extension $extension Extension instance or service class.
     *
     * @return $this Mutated manager for fluent bootstrap configuration.
     */
    public function add(string|Extension $extension): self
    {
        $this->extensions[] = $extension;
        return $this;
    }

    /**
     * Invokes register() once per ordered entry.
     *
     * Resolution and extension exceptions escape; completed earlier side effects
     * are not rolled back.
     */
    public function registerAll(Application $app): void
    {
        foreach ($this->resolved() as $extension) {
            $extension->register($app);
        }
    }

    /**
     * Invokes boot() once per ordered entry.
     *
     * Resolution and extension exceptions escape; completed earlier side effects
     * are not rolled back.
     */
    public function bootAll(Application $app): void
    {
        foreach ($this->resolved() as $extension) {
            $extension->boot($app);
        }
    }

    /**
     * Resolves class entries in place and returns the ordered instance list.
     *
     * @return list<Extension> Retained instances in insertion order.
     *
     * @throws RuntimeException When a resolved service violates Extension.
     */
    private function resolved(): array
    {
        foreach ($this->extensions as $index => $extension) {
            if (is_string($extension)) {
                $extension = $this->container->get($extension);
                if (!$extension instanceof Extension) {
                    throw new RuntimeException('Extension must implement ' . Extension::class);
                }
                $this->extensions[$index] = $extension;
            }
        }
        return $this->extensions;
    }
}
