<?php

declare(strict_types=1);

namespace Bluewater\Routing;

use Bluewater\ApplicationDefinition;
use Bluewater\Config\Config;
use Bluewater\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class Router
{
    /** @var Route[] */
    private array $routes = [];

    public function __construct(
        private readonly ApplicationDefinition $app,
        private readonly Config $config,
    ) {}

    public function discover(): void
    {
        $cache = $this->app->cache . '/routes.php';
        $files = $this->endpointFiles();
        if ($this->cacheIsFresh($cache, $files)) {
            $rows = require $cache;
            $this->routes = array_map(static fn(array $r): Route => new Route(...$r), $rows);
            return;
        }

        $routes = [];
        $seen = [];
        foreach ($files as $file) {
            require_once $file;
            $relative = substr($file, strlen(rtrim($this->app->endpointPath(), '/')) + 1);
            $withoutExt = preg_replace('/\.php$/i', '', $relative) ?? $relative;
            $segments = explode('/', str_replace('\\', '/', $withoutExt));
            $className = array_pop($segments);
            $resource = strtolower((string) $className);
            $prefix = $segments === [] ? '' : '/' . implode('/', array_map('strtolower', $segments));
            $basePath = $prefix . '/' . $resource;
            $class = trim($this->app->namespace, '\\') . '\\Endpoints\\' . ($segments === [] ? '' : implode('\\', $segments) . '\\') . $this->studly((string) $className);
            if (!class_exists($class)) {
                throw new RuntimeException("Endpoint file {$file} must define {$class}.");
            }

            $ref = new ReflectionClass($class);
            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor() || $method->isStatic() || $method->getDeclaringClass()->getName() !== $class) { continue; }
                [$verb, $suffix] = $this->parseHandlerName($method->getName());
                if ($verb === null) { continue; }

                $path = $basePath;
                $pathAttr = $method->getAttributes(Path::class)[0] ?? null;
                if ($pathAttr !== null) {
                    $custom = $pathAttr->newInstance()->value;
                    $path .= '/' . ltrim($custom, '/');
                } elseif ($suffix !== '') {
                    foreach ($method->getParameters() as $parameter) {
                        if ($parameter->getType()?->isBuiltin() ?? false) {
                            $path .= '/{' . $parameter->getName() . '}';
                        }
                    }
                }

                $path = preg_replace('#/+#', '/', $path) ?: '/';
                $key = $verb . ' ' . $path;
                if (isset($seen[$key])) {
                    throw new RuntimeException("Route conflict: {$key} is defined by {$seen[$key]} and {$class}::{$method->getName()}.");
                }
                $seen[$key] = $class . '::' . $method->getName();
                $params = [];
                $regex = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', static function(array $m) use (&$params): string {
                    $params[] = $m[1];
                    return '(?P<' . $m[1] . '>[^/]+)';
                }, $path) ?: $path;
                $routes[] = new Route($verb, $path, '#^' . $regex . '/?$#', $file, $class, $method->getName(), $params);
            }
        }

        usort($routes, static fn(Route $a, Route $b): int => substr_count($a->path, '{') <=> substr_count($b->path, '{') ?: strlen($b->path) <=> strlen($a->path));
        $this->routes = $routes;
        $this->compile($cache, $routes);
    }

    public function match(Request $request): Route
    {
        foreach ($this->routes as $route) {
            if ($route->httpMethod !== $request->method) { continue; }
            if (preg_match($route->regex, $request->path, $matches) === 1) {
                $params = [];
                foreach ($route->parameters as $name) { if (isset($matches[$name])) { $params[$name] = $matches[$name]; } }
                return new Route($route->httpMethod, $route->path, $route->regex, $route->file, $route->class, $route->method, $params, $route->middleware);
            }
        }
        throw new RuntimeException("Route not found: {$request->method} {$request->path}");
    }

    private function endpointFiles(): array
    {
        $dir = $this->app->endpointPath();
        if (!is_dir($dir)) { return []; }
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') { $files[] = $file->getPathname(); }
        }
        sort($files);
        return $files;
    }

    private function parseHandlerName(string $name): array
    {
        foreach (['get','post','put','patch','delete','options','head'] as $verb) {
            if ($name === $verb) { return [strtoupper($verb), '']; }
            if (str_starts_with($name, $verb . 'By')) { return [strtoupper($verb), substr($name, strlen($verb . 'By'))]; }
        }
        return [null, ''];
    }

    private function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    private function cacheIsFresh(string $cache, array $files): bool
    {
        if (!is_file($cache)) { return false; }
        $cacheTime = filemtime($cache) ?: 0;
        foreach ($files as $file) { if ((filemtime($file) ?: PHP_INT_MAX) > $cacheTime) { return false; } }
        return true;
    }

    /** @param Route[] $routes */
    private function compile(string $cache, array $routes): void
    {
        $rows = array_map(static fn(Route $r): array => [
            'httpMethod' => $r->httpMethod,
            'path' => $r->path,
            'regex' => $r->regex,
            'file' => $r->file,
            'class' => $r->class,
            'method' => $r->method,
            'parameters' => $r->parameters,
            'middleware' => $r->middleware,
        ], $routes);
        $tmp = $cache . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, "<?php\nreturn " . var_export($rows, true) . ";\n", LOCK_EX);
        rename($tmp, $cache);
    }
}
