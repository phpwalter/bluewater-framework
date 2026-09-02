<?php

declare(strict_types=1);

namespace Bluewater;

final class ApplicationClassLoader
{
    private string $prefix;

    public function __construct(
        string $namespace,
        private readonly string $root,
    ) {
        $this->prefix = trim($namespace, '\\') . '\\';
    }

    public function register(): void
    {
        spl_autoload_register($this->load(...), prepend: true);
    }

    private function load(string $class): void
    {
        if (!str_starts_with($class, $this->prefix)) { return; }
        $relative = substr($class, strlen($this->prefix));
        $file = $this->root . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) { require $file; }
    }
}
