<?php

/**
 * @file Application.php
 * @path src/Application.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the application lifecycle coordinator that boots extensions,
 * dispatches requests, and converts failures into HTTP responses.
 */

declare(strict_types=1);

namespace Bluewater;

use Bluewater\Config\Config;
use Bluewater\Container\Container;
use Bluewater\Endpoint\EndpointDispatcher;
use Bluewater\Extension\ExtensionManager;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Pipeline;
use Bluewater\Routing\RouteNotFound;
use Bluewater\Routing\Router;
use Bluewater\Runtime\RuntimeAdapter;
use Throwable;

/**
 * Coordinates the lifecycle and request pipeline of one hosted application.
 *
 * Construction registers framework-owned service instances in the supplied
 * container. Boot is idempotent and orders application registration, extension
 * registration, route discovery, extension boot, and application boot. Request
 * handling converts all failures to problem responses and exposes exception
 * details only in development. Runtime-specific input and output are delegated
 * to RuntimeAdapter.
 */
final class Application
{
    /** True only after the complete bootstrap sequence succeeds. */
    private bool $booted = false;

    /**
     * Creates an application and publishes its core collaborators as services.
     *
     * The supplied container is mutated synchronously. No extension, route, or
     * application bootstrap code runs during construction.
     */
    public function __construct(
        private readonly ApplicationDefinition $definition,
        private readonly Container $container,
        private readonly Config $config,
        private readonly Router $router,
        private readonly Pipeline $pipeline,
        private readonly EndpointDispatcher $dispatcher,
        private readonly ExtensionManager $extensions,
    ) {
        $this->container->instance(self::class, $this);
        $this->container->instance(ApplicationDefinition::class, $this->definition);
        $this->container->instance(Config::class, $this->config);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(Pipeline::class, $this->pipeline);
        $this->container->instance(EndpointDispatcher::class, $this->dispatcher);
        $this->container->instance(ExtensionManager::class, $this->extensions);
    }

    /** Returns the immutable definition retained by this application. */
    public function definition(): ApplicationDefinition
    {
        return $this->definition;
    }

    /** Returns the mutable service container owned by this application. */
    public function services(): Container
    {
        return $this->container;
    }

    /** Returns the immutable resolved configuration view. */
    public function config(): Config
    {
        return $this->config;
    }

    /** Returns the router that owns route discovery and matching state. */
    public function router(): Router
    {
        return $this->router;
    }

    /** Returns the mutable global middleware pipeline. */
    public function middleware(): Pipeline
    {
        return $this->pipeline;
    }

    /** Returns the mutable extension registry. */
    public function extensions(): ExtensionManager
    {
        return $this->extensions;
    }

    /**
     * Boots the application exactly once after a fully successful lifecycle.
     *
     * A repeated call after success has no effect. If any lifecycle callback or
     * route discovery fails, the exception escapes and the application remains
     * unbooted, allowing the caller to decide whether retry is safe.
     */
    public function boot(ApplicationBootstrap $bootstrap): void
    {
        if ($this->booted) {
            return;
        }

        $bootstrap->register($this);
        $this->extensions->registerAll($this);
        $this->router->discover();
        $this->extensions->bootAll($this);
        $bootstrap->boot($this);
        $this->booted = true;
    }

    /**
     * Handles one immutable request through routing, middleware, and dispatch.
     *
     * Route misses become 404 responses and every other throwable becomes a
     * 500 response. Production responses omit exception messages; development
     * responses include them for diagnostics. No exception escapes this method.
     */
    public function handle(Request $request): Response
    {
        try {
            $route = $this->router->match($request);
            return $this->pipeline->handle(
                $request,
                fn (Request $r): Response => $this->dispatcher->dispatch($route, $r),
                $route->middleware,
            );
        } catch (RouteNotFound $e) {
            return Response::problem(404, 'Not Found', $this->isDevelopment() ? $e->getMessage() : null);
        } catch (Throwable $e) {
            return Response::problem(500, 'Internal Server Error', $this->isDevelopment() ? $e->getMessage() : null);
        }
    }

    /**
     * Obtains, handles, and emits one request through a runtime adapter.
     *
     * The adapter's request() and emit() methods are each invoked once.
     */
    public function run(RuntimeAdapter $runtime): void
    {
        $runtime->emit($this->handle($runtime->request()));
    }

    /** Returns whether diagnostic exception details may be exposed. */
    private function isDevelopment(): bool
    {
        return $this->definition->environment === 'development' || $this->config->get('BW_ENV') === 'development';
    }
}
