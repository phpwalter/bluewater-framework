<?php

declare(strict_types=1);

namespace Bluewater;

use Bluewater\Config\ConfigFactory;
use Bluewater\Container\Container;
use Bluewater\Endpoint\EndpointDispatcher;
use Bluewater\Middleware\Pipeline;
use Bluewater\Routing\Router;
use RuntimeException;

final class Host
{
    public function __construct(
        private readonly string $applicationBase,
        private readonly string $coreConfigPath,
    ) {}

    public static function fromEnvironment(): self
    {
        $base = getenv('BLUEWATER_APP_BASE') ?: dirname(__DIR__) . '/app';
        return new self($base, __DIR__ . '/../config');
    }

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

        $appConfig = $root . '/config/App.ini.php';
        $config = (new ConfigFactory($this->coreConfigPath, $root . '/config', $root . '/cache'))->create();
        $namespace = (string) $config->get('APP_NAMESPACE', 'Apps\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name))));
        $environment = (string) (getenv('BLUEWATER_ENV') ?: $config->get('BW_ENV', 'production'));

        (new ApplicationClassLoader($namespace, $root))->register();

        $definition = new ApplicationDefinition($name, $namespace, $root, $root . '/cache', $root . '/logs', $environment);
        $container = new Container();
        $pipeline = new Pipeline($container);
        $router = new Router($definition, $config);
        $dispatcher = new EndpointDispatcher($container);

        $app = new Application($definition, $container, $config, $router, $pipeline, $dispatcher);
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
