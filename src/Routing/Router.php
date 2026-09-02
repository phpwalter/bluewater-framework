<?php

declare(strict_types=1);

namespace Bluewater\Routing;

use Bluewater\ApplicationDefinition;
use Bluewater\Config\Config;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
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
        $fingerprint = $this->fingerprint($files);

        if (is_file($cache)) {
            $compiled = require $cache;
            if (is_array($compiled) && ($compiled['fingerprint'] ?? null) === $fingerprint) {
                $this->routes = array_map(static fn (array $r): Route => new Route(...$r), $compiled['routes'] ?? []);
                return;
            }
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
            $class = trim($this->app->namespace, '\\') . '\\Endpoints\\'
                . ($segments === [] ? '' : implode('\\', $segments) . '\\')
                . $this->studly((string) $className);

            if (!class_exists($class)) {
                throw new RuntimeException("Endpoint file {$file} must define {$class}.");
            }

            $ref = new ReflectionClass($class);
            $classMiddleware = $this->middlewareAttributes($ref->getAttributes(UseMiddleware::class));
            $directoryMiddleware = $this->directoryMiddleware(dirname($file));

            foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor() || $method->isStatic() || $method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                [$verb, $suffix] = $this->parseHandlerName($method->getName());
                if ($verb === null) { continue; }

                $path = $basePath;
                $pathAttr = $method->getAttributes(Path::class)[0] ?? null;
                if ($pathAttr !== null) {
                    $custom = $pathAttr->newInstance()->value;
                    $path .= '/' . ltrim($custom, '/');
                } elseif ($suffix !== '') {
                    $methodParameters = array_map(static fn ($parameter): string => $parameter->getName(), $method->getParameters());
                    foreach ($this->suffixParameters($suffix) as $parameterName) {
                        if (!in_array($parameterName, $methodParameters, true)) {
                            throw new RuntimeException("Handler {$class}::{$method->getName()} derives route parameter {{$parameterName}} but has no matching method parameter.");
                        }
                        $path .= '/{' . $parameterName . '}';
                    }
                }

                $path = preg_replace('#/+#', '/', $path) ?: '/';
                $canonicalPath = preg_replace('/\{[A-Za-z_][A-Za-z0-9_]*\}/', '{}', $path) ?: $path;
                $key = $verb . ' ' . $canonicalPath;
                if (isset($seen[$key])) {
                    throw new RuntimeException("Route conflict: {$verb} {$path} conflicts with {$seen[$key]}.");
                }
                $seen[$key] = $class . '::' . $method->getName();

                [$regex, $params] = $this->compileRegex($path);
                $methodMiddleware = $this->middlewareAttributes($method->getAttributes(UseMiddleware::class));
                $middleware = [...$directoryMiddleware, ...$classMiddleware, ...$methodMiddleware];
                $routes[] = new Route($verb, $path, $regex, $file, $class, $method->getName(), $params, $middleware);
            }
        }

        usort($routes, static fn (Route $a, Route $b): int =>
            substr_count($a->path, '{') <=> substr_count($b->path, '{')
            ?: strlen($b->path) <=> strlen($a->path)
        );
        $this->routes = $routes;
        $this->compile($cache, $fingerprint, $routes);
    }

    public function match(Request $request): Route
    {
        foreach ($this->routes as $route) {
            if ($route->httpMethod !== $request->method) { continue; }
            if (preg_match($route->regex, $request->path, $matches) === 1) {
                $params = [];
                foreach ($route->parameters as $name) {
                    if (isset($matches[$name])) { $params[$name] = $matches[$name]; }
                }
                return new Route(
                    $route->httpMethod,
                    $route->path,
                    $route->regex,
                    $route->file,
                    $route->class,
                    $route->method,
                    $params,
                    $route->middleware,
                );
            }
        }
        throw new RouteNotFound("Route not found: {$request->method} {$request->path}");
    }

    /** @return Route[] */
    public function routes(): array { return $this->routes; }

    private function endpointFiles(): array
    {
        $dir = $this->app->endpointPath();
        if (!is_dir($dir)) { return []; }
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
            if (str_starts_with($file->getBasename(), '_')) { continue; }
            $files[] = $file->getPathname();
        }
        sort($files);
        return $files;
    }

    private function parseHandlerName(string $name): array
    {
        foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $verb) {
            if ($name === $verb) { return [strtoupper($verb), '']; }
            if (str_starts_with($name, $verb . 'By')) {
                return [strtoupper($verb), substr($name, strlen($verb . 'By'))];
            }
        }
        return [null, ''];
    }

    private function suffixParameters(string $suffix): array
    {
        if ($suffix === '') { return []; }
        $parts = preg_split('/And(?=[A-Z])/', $suffix) ?: [$suffix];
        return array_map(static fn (string $part): string => lcfirst($part), $parts);
    }

    private function compileRegex(string $path): array
    {
        $params = [];
        $regex = '';
        $offset = 0;
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => [$token, $position]) {
            $regex .= preg_quote(substr($path, $offset, $position - $offset), '#');
            $name = $matches[1][$index][0];
            $params[] = $name;
            $regex .= '(?P<' . $name . '>[^/]+)';
            $offset = $position + strlen($token);
        }
        $regex .= preg_quote(substr($path, $offset), '#');
        return ['#^' . $regex . '/?$#', $params];
    }

    private function studly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }

    private function directoryMiddleware(string $endpointDirectory): array
    {
        $root = rtrim($this->app->endpointPath(), '/');
        $current = $root;
        $relative = trim(substr($endpointDirectory, strlen($root)), '/');
        $parts = $relative === '' ? [] : explode('/', str_replace('\\', '/', $relative));
        $middleware = [];

        $candidates = [$root . '/_middleware.php'];
        foreach ($parts as $part) {
            $current .= '/' . $part;
            $candidates[] = $current . '/_middleware.php';
        }

        foreach ($candidates as $file) {
            if (!is_file($file)) { continue; }
            $items = require $file;
            if (!is_array($items)) {
                throw new RuntimeException("Directory middleware file must return an array: {$file}");
            }
            foreach ($items as $item) {
                if (!is_string($item)) { throw new RuntimeException("Middleware entries must be class names: {$file}"); }
                $middleware[] = $item;
            }
        }
        return $middleware;
    }

    private function middlewareAttributes(array $attributes): array
    {
        return array_map(static fn ($attribute): string => $attribute->newInstance()->middleware, $attributes);
    }

    private function fingerprint(array $files): string
    {
        $parts = [];
        foreach ($files as $file) {
            $parts[] = $file . ':' . (filemtime($file) ?: 0) . ':' . (filesize($file) ?: 0);
            $dir = dirname($file);
            while (str_starts_with($dir, $this->app->endpointPath())) {
                $middleware = $dir . '/_middleware.php';
                if (is_file($middleware)) {
                    $parts[] = $middleware . ':' . (filemtime($middleware) ?: 0) . ':' . (filesize($middleware) ?: 0);
                }
                if ($dir === $this->app->endpointPath()) { break; }
                $dir = dirname($dir);
            }
        }
        sort($parts);
        return hash('sha256', implode('|', array_unique($parts)));
    }

    /** @param Route[] $routes */
    private function compile(string $cache, string $fingerprint, array $routes): void
    {
        $rows = array_map(static fn (Route $r): array => [
            'httpMethod' => $r->httpMethod,
            'path' => $r->path,
            'regex' => $r->regex,
            'file' => $r->file,
            'class' => $r->class,
            'method' => $r->method,
            'parameters' => $r->parameters,
            'middleware' => $r->middleware,
        ], $routes);

        $payload = ['fingerprint' => $fingerprint, 'routes' => $rows];
        $tmp = $cache . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmp, "<?php\nreturn " . var_export($payload, true) . ";\n", LOCK_EX) === false || !rename($tmp, $cache)) {
            @unlink($tmp);
            throw new RuntimeException("Unable to compile route cache: {$cache}");
        }
    }
}
