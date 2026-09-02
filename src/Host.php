<?php

/**
 * @file Host.php
 * @path src/Host.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the application factory that loads configuration, provisions runtime
 * services, and boots a named hosted application.
 */

declare(strict_types=1);

namespace Bluewater;

use Bluewater\Config\ConfigFactory;
use Bluewater\Container\Container;
use Bluewater\Endpoint\EndpointDispatcher;
use Bluewater\Extension\ExtensionManager;
use Bluewater\Logging\FileLogger;
use Bluewater\Middleware\Pipeline;
use Bluewater\Routing\Router;
use Bluewater\Serialization\SerializerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Builds and boots named applications beneath one configured application root.
 *
 * Host validates application names before composing filesystem paths, creates
 * required runtime directories, resolves configuration, installs application
 * autoloading, and wires framework services. It never accepts arbitrary paths
 * from callers and fails before returning a partially booted application.
 */
final class Host
{
    /**
     * Creates a host around application and framework configuration roots.
     *
     * Construction performs no filesystem or environment access.
     *
     * @param non-empty-string $applicationBase Parent directory of applications.
     * @param non-empty-string $coreConfigPath Framework configuration directory.
     */
    public function __construct(
        private readonly string $applicationBase,
        private readonly string $coreConfigPath,
    ) {
    }

    /**
     * Creates a host from process environment and package-relative defaults.
     *
     * BLUEWATER_APP_BASE is read once. A missing or empty value selects the
     * package's `app` directory; no directories are created by this factory.
     */
    public static function fromEnvironment(): self
    {
        $base = getenv('BLUEWATER_APP_BASE') ?: dirname(__DIR__) . '/app';
        return new self($base, __DIR__ . '/../config');
    }

    /**
     * Builds and boots one validated application.
     *
     * The name accepts only ASCII letters, digits, underscore, dot, and hyphen,
     * preventing path traversal. Runtime directories may be created, guarded
     * configuration and cache files are read or written, an autoloader is
     * registered, and application bootstrap code may perform additional I/O.
     *
     * @param non-empty-string $name Application directory identifier.
     *
     * @return Application Fully booted application ready to handle requests.
     *
     * @throws RuntimeException When the name, application directory, runtime
     *     directory, bootstrap class, or bootstrap contract is invalid.
     */
    public function application(string $name): Application
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            throw new RuntimeException('Invalid Bluewater application name.');
        }

        $root = rtrim($this->applicationBase, '/') . '/' . $name;
        if (!is_dir($root)) {
            throw new RuntimeException("Application directory not found: {$root}");
        }

        foreach ([$root . '/cache', $root . '/logs'] as $runtimeDir) {
            if (!is_dir($runtimeDir) && !mkdir($runtimeDir, 0775, true) && !is_dir($runtimeDir)) {
                throw new RuntimeException("Unable to create runtime directory: {$runtimeDir}");
            }
        }

        $runtimeSymbols = [
            'DS' => DIRECTORY_SEPARATOR,
            'APP_ROOT' => $root,
            'CACHE_ROOT' => $root . '/cache',
            'BLUEWATER' => dirname($this->coreConfigPath),
            'SITE_ROOT' => dirname(rtrim($this->applicationBase, '/')),
        ];
        $config = (new ConfigFactory(
            $this->coreConfigPath,
            $root . '/config',
            $root . '/cache',
            $runtimeSymbols,
        ))->create();

        $namespace = (string) $config->get(
            'APP_NAMESPACE',
            'Apps\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name))),
        );
        $environment = (string) (getenv('BLUEWATER_ENV') ?: $config->get('BW_ENV', 'production'));

        (new ApplicationClassLoader($namespace, $root))->register();

        $definition = new ApplicationDefinition(
            $name,
            $namespace,
            $root,
            $root . '/cache',
            $root . '/logs',
            $environment,
        );
        $container = new Container();
        $logging = (bool) $config->get('features.LOGGING', true);
        $logFile = (string) $config->get('logging.FILE', $definition->logs . '/application.log');
        $container->instance(LoggerInterface::class, $logging ? new FileLogger($logFile) : new NullLogger());
        $container->instance(SerializerRegistry::class, new SerializerRegistry());

        $pipeline = new Pipeline($container);
        $router = new Router($definition, $config);
        $dispatcher = new EndpointDispatcher($container);
        $extensions = new ExtensionManager($container);

        $app = new Application($definition, $container, $config, $router, $pipeline, $dispatcher, $extensions);
        $bootstrapClass = $namespace . '\\Bootstrap';
        if (!class_exists($bootstrapClass)) {
            throw new RuntimeException("Required bootstrap class not found: {$bootstrapClass}");
        }
        $bootstrap = new $bootstrapClass();
        if (!$bootstrap instanceof ApplicationBootstrap) {
            throw new RuntimeException("{$bootstrapClass} must implement " . ApplicationBootstrap::class);
        }
        $app->boot($bootstrap);
        return $app;
    }
}
