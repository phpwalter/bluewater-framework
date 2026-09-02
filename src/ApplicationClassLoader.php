<?php

/**
 * @file ApplicationClassLoader.php
 * @path src/ApplicationClassLoader.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the application namespace autoloader that maps application classes to files beneath a configured root.
 */

declare(strict_types=1);

namespace Bluewater;

/**
 * Autoloads classes belonging to one configured application namespace.
 *
 * The loader performs exact prefix matching and maps namespace separators to
 * path separators beneath the retained root. It never searches outside that
 * root and silently ignores classes owned by other namespaces.
 */
final class ApplicationClassLoader
{
    /** @var non-empty-string Canonical namespace prefix ending in a separator. */
    private string $prefix;

    /**
     * Creates a loader without registering it globally.
     *
     * @param non-empty-string $namespace Application namespace to own.
     * @param non-empty-string $root Application source root retained by the loader.
     */
    public function __construct(
        string $namespace,
        private readonly string $root,
    ) {
        $this->prefix = trim($namespace, '\\') . '\\';
    }

    /**
     * Prepends this loader to PHP's process-wide autoload stack.
     *
     * Repeated calls register repeated callbacks; callers should invoke it once
     * per application definition during host construction.
     */
    public function register(): void
    {
        spl_autoload_register($this->load(...), prepend: true);
    }

    /**
     * Loads a matching class file when it exists.
     *
     * The method performs filesystem reads only for classes under the owned
     * prefix. Missing files are ignored so later autoloaders may run.
     *
     * @param class-string $class Fully qualified class name requested by PHP.
     */
    private function load(string $class): void
    {
        if (!str_starts_with($class, $this->prefix)) {
            return;
        }

        $relative = substr($class, strlen($this->prefix));
        $file = $this->root . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}
