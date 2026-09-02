<?php

declare(strict_types=1);

namespace Bluewater\Extension;

use Bluewater\Application;
use Bluewater\Container\Container;
use RuntimeException;

final class ExtensionManager
{
    private array $extensions = [];

    public function __construct(private readonly Container $container) {}

    public function add(string|Extension $extension): self
    {
        $this->extensions[] = $extension;
        return $this;
    }

    public function registerAll(Application $app): void
    {
        foreach ($this->resolved() as $extension) {
            $extension->register($app);
        }
    }

    public function bootAll(Application $app): void
    {
        foreach ($this->resolved() as $extension) {
            $extension->boot($app);
        }
    }

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
