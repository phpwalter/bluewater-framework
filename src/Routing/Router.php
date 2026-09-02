<?php

/**
 * @file Router.php
 * @path src/Routing/Router.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Discovers file-based endpoints, rejects route ambiguity, compiles route
 * metadata, and performs deterministic matching.
 */

declare(strict_types=1);

namespace Bluewater\Routing;

use Bluewater\ApplicationDefinition;
use Bluewater\Config\Config;
use Bluewater\Http\Request;
use Bluewater\Middleware\UseMiddleware;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionParameter;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;

/**
 * Discovers endpoint handlers and matches immutable requests to compiled routes.
 *
 * Endpoint files are traversed and sorted lexically. Routes are derived from
 * handler names or Path attributes; duplicate method/canonical-path pairs and
 * unmatched parameter declarations fail discovery. Static routes sort before
 * dynamic routes, then longer paths sort first. A source fingerprint controls
 * atomic cache reuse. Router performs resolution only; dispatch is delegated.
 */
final class Router
{
    /** @var list<Route> Routes in deterministic match-precedence order. */
    private array $routes = [];

    /** Retains application and configuration metadata without filesystem I/O. */
    public function __construct(
        private readonly ApplicationDefinition $app,
        Config $config,
    ) {
        unset($config);
    }

    /**
     * Discovers routes or restores them from a current compiled cache.
     *
     * Endpoint and directory-middleware files may be included, reflection is
     * performed, and the route cache may be atomically replaced. The route list
     * changes only after a complete cache load or successful discovery pass.
     *
     * @throws RuntimeException When endpoint declarations, route parameters,
     *     middleware manifests, conflicts, or cache writes are invalid.
     */
    public function discover(): void
    {
        $cache = $this->app->cache . '/routes.php';
        $files = $this->endpointFiles();
        $fingerprint = $this->fingerprint($files);

        if (is_file($cache)) {
            $compiled = require $cache;
            if (is_array($compiled) && ($compiled['fingerprint'] ?? null) === $fingerprint) {
                $rows = $compiled['routes'] ?? null;
                if (is_array($rows)) {
                    /** @var list<array{
                     *     httpMethod: non-empty-string,
                     *     path: non-empty-string,
                     *     regex: non-empty-string,
                     *     file: non-empty-string,
                     *     class: class-string,
                     *     method: non-empty-string,
                     *     parameters: array<array-key, string>,
                     *     middleware: list<class-string>
                     * }> $rows
                     */
                    $this->routes = array_map(static fn (array $row): Route => new Route(...$row), $rows);
                    return;
                }
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
                if (
                    $method->isConstructor()
                    || $method->isStatic()
                    || $method->getDeclaringClass()->getName() !== $class
                ) {
                    continue;
                }

                $pathAttr = $method->getAttributes(Path::class)[0] ?? null;
                [$verb, $suffix] = $this->parseHandlerName($method->getName(), $pathAttr !== null);
                if ($verb === null) {
                    continue;
                }

                $methodParameters = array_map(
                    static fn (ReflectionParameter $parameter): string => $parameter->getName(),
                    $method->getParameters(),
                );
                $path = $basePath;
                if ($pathAttr !== null) {
                    $custom = $pathAttr->newInstance()->value;
                    $path .= '/' . ltrim($custom, '/');
                } elseif ($suffix !== '') {
                    foreach ($this->suffixParameters($suffix) as $parameterName) {
                        if (!in_array($parameterName, $methodParameters, true)) {
                            throw new RuntimeException(
                                "Handler {$class}::{$method->getName()} derives route parameter "
                                . "{{$parameterName}} but has no matching method parameter.",
                            );
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
                foreach ($params as $parameterName) {
                    if (!in_array($parameterName, $methodParameters, true)) {
                        throw new RuntimeException(
                            "Route {$verb} {$path} declares {{$parameterName}} but "
                            . "{$class}::{$method->getName()} has no matching parameter.",
                        );
                    }
                }

                $methodMiddleware = $this->middlewareAttributes($method->getAttributes(UseMiddleware::class));
                $middleware = [...$directoryMiddleware, ...$classMiddleware, ...$methodMiddleware];
                $routes[] = new Route($verb, $path, $regex, $file, $class, $method->getName(), $params, $middleware);
            }
        }

        usort(
            $routes,
            static fn (Route $a, Route $b): int =>
                substr_count($a->path, '{') <=> substr_count($b->path, '{')
                ?: strlen($b->path) <=> strlen($a->path),
        );
        $this->routes = array_values($routes);
        $this->compile($cache, $fingerprint, $routes);
    }

    /**
     * Returns the first route matching the request method and path.
     *
     * Captured path parameters preserve declaration order and remain strings.
     *
     * @throws RouteNotFound When no compiled route matches exactly.
     */
    public function match(Request $request): Route
    {
        foreach ($this->routes as $route) {
            if ($route->httpMethod !== $request->method) {
                continue;
            }
            if (preg_match($route->regex, $request->path, $matches) === 1) {
                $params = [];
                foreach ($route->parameters as $name) {
                    if (isset($matches[$name])) {
                        $params[$name] = $matches[$name];
                    }
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

    /** @return list<Route> Routes in deterministic match-precedence order. */
    public function routes(): array
    {
        return $this->routes;
    }

    /** @return list<non-empty-string> Endpoint files in lexical path order. */
    private function endpointFiles(): array
    {
        $dir = $this->app->endpointPath();
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            if (str_starts_with($file->getBasename(), '_')) {
                continue;
            }
            $files[] = $file->getPathname();
        }
        sort($files);
        return $files;
    }

    /**
     * Derives an HTTP verb and route-parameter suffix from a handler name.
     *
     * @return array{0: non-empty-string|null, 1: string} Uppercase verb and suffix.
     */
    private function parseHandlerName(string $name, bool $allowPrefixedVerb = false): array
    {
        foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $verb) {
            if ($name === $verb) {
                return [strtoupper($verb), ''];
            }
            if (str_starts_with($name, $verb . 'By')) {
                return [strtoupper($verb), substr($name, strlen($verb . 'By'))];
            }
            if ($allowPrefixedVerb && str_starts_with($name, $verb) && strlen($name) > strlen($verb)) {
                return [strtoupper($verb), ''];
            }
        }
        return [null, ''];
    }

    /** @return list<non-empty-string> Lower-camel parameter names in suffix order. */
    private function suffixParameters(string $suffix): array
    {
        if ($suffix === '') {
            return [];
        }
        $parts = preg_split('/And(?=[A-Z])/', $suffix) ?: [$suffix];
        $parameters = [];
        foreach ($parts as $part) {
            $parameter = lcfirst($part);
            if ($parameter !== '') {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * Compiles a path template to an anchored regex and ordered parameter list.
     *
     * @return array{0: non-empty-string, 1: list<non-empty-string>}
     */
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

    /** @return non-empty-string StudlyCaps class segment derived from a filename. */
    private function studly(string $name): string
    {
        $result = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        if ($result === '') {
            throw new RuntimeException('An endpoint filename must contain a class name.');
        }

        return $result;
    }

    /**
     * Loads directory middleware from root to the endpoint's parent directory.
     *
     * @return list<class-string> Middleware class names in execution order.
     *
     * @throws RuntimeException When a manifest or entry has the wrong type.
     */
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
            if (!is_file($file)) {
                continue;
            }
            $items = require $file;
            if (!is_array($items)) {
                throw new RuntimeException("Directory middleware file must return an array: {$file}");
            }
            foreach ($items as $item) {
                if (!is_string($item) || !class_exists($item)) {
                    throw new RuntimeException("Middleware entries must be class names: {$file}");
                }
                /** @var class-string $item */
                $middleware[] = $item;
            }
        }
        return $middleware;
    }

    /**
     * Instantiates middleware attributes in reflection declaration order.
     *
     * @param list<\ReflectionAttribute<UseMiddleware>> $attributes
     *
     * @return list<class-string> Declared middleware class names.
     */
    private function middlewareAttributes(array $attributes): array
    {
        return array_map(static fn ($attribute): string => $attribute->newInstance()->middleware, $attributes);
    }

    /**
     * @param list<non-empty-string> $files Endpoint source files.
     *
     * @return non-empty-string SHA-256 fingerprint of route-affecting files.
     */
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
                if ($dir === $this->app->endpointPath()) {
                    break;
                }
                $dir = dirname($dir);
            }
        }
        sort($parts);
        return hash('sha256', implode('|', array_unique($parts)));
    }

    /**
     * Atomically writes a PHP route cache derived from immutable route values.
     *
     * @param non-empty-string $cache Target cache path.
     * @param non-empty-string $fingerprint Route-source fingerprint.
     * @param list<Route> $routes Routes in match-precedence order.
     *
     * @throws RuntimeException When serialization or atomic replacement fails.
     */
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
        $written = file_put_contents(
            $tmp,
            "<?php\nreturn " . var_export($payload, true) . ";\n",
            LOCK_EX,
        );
        if ($written === false || !rename($tmp, $cache)) {
            @unlink($tmp);
            throw new RuntimeException("Unable to compile route cache: {$cache}");
        }
    }
}
