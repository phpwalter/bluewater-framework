<?php

declare(strict_types=1);

namespace Bluewater;

use Bluewater\Config\Config;
use Bluewater\Container\Container;
use Bluewater\Endpoint\EndpointDispatcher;
use Bluewater\Http\Request;
use Bluewater\Http\Response;
use Bluewater\Middleware\Pipeline;
use Bluewater\Routing\Router;
use Bluewater\Runtime\RuntimeAdapter;
use Throwable;

final class Application
{
    private bool $booted = false;

    public function __construct(
        private readonly ApplicationDefinition $definition,
        private readonly Container $container,
        private readonly Config $config,
        private readonly Router $router,
        private readonly Pipeline $pipeline,
        private readonly EndpointDispatcher $dispatcher,
    ) {
        $this->container->instance(self::class, $this);
        $this->container->instance(ApplicationDefinition::class, $this->definition);
        $this->container->instance(Config::class, $this->config);
    }

    public function definition(): ApplicationDefinition { return $this->definition; }
    public function services(): Container { return $this->container; }
    public function config(): Config { return $this->config; }
    public function router(): Router { return $this->router; }
    public function middleware(): Pipeline { return $this->pipeline; }

    public function boot(ApplicationBootstrap $bootstrap): void
    {
        if ($this->booted) { return; }
        $bootstrap->register($this);
        $this->router->discover();
        $bootstrap->boot($this);
        $this->booted = true;
    }

    public function handle(Request $request): Response
    {
        try {
            $route = $this->router->match($request);
            return $this->pipeline->handle($request, fn (Request $r): Response => $this->dispatcher->dispatch($route, $r));
        } catch (Throwable $e) {
            return Response::problem(500, 'Internal Server Error', $this->config->get('BW_ENV') === 'development' ? $e->getMessage() : null);
        }
    }

    public function run(RuntimeAdapter $runtime): void
    {
        $runtime->emit($this->handle($runtime->request()));
    }
}
