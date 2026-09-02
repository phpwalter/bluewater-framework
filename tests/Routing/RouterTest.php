<?php

/**
 * @file RouterTest.php
 * @path tests/Routing/RouterTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies the router test behavior and its observable framework contracts.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Routing;

use Bluewater\ApplicationDefinition;
use Bluewater\Config\Config;
use Bluewater\Http\Request;
use Bluewater\Routing\Router;
use PHPUnit\Framework\TestCase;

/** Verifies deterministic file-based route derivation and explicit path attributes. */
final class RouterTest extends TestCase
{
    /** @var non-empty-string Per-test endpoint and cache root. */
    private string $root;

    /** Creates isolated endpoint and route-cache directories. */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bluewater-router-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/Endpoints', 0777, true);
        mkdir($this->root . '/cache', 0777, true);
    }

    /** Removes all test-owned route fixture files. */
    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    /** Confirms conventional handler names derive collection and item routes. */
    public function testMethodsDeriveFileBasedRoutesWithoutManifest(): void
    {
        $namespace = 'RouteFixture' . bin2hex(random_bytes(4));
        $source = <<<PHP
<?php
namespace {$namespace}\\Endpoints;
use Bluewater\\Endpoint\\Endpoint;
final class Users extends Endpoint {
    public function get(): array { return []; }
    public function getById(int \$id): array { return ['id' => \$id]; }
}
PHP;
        file_put_contents($this->root . '/Endpoints/users.php', $source);

        $router = $this->router($namespace);
        $router->discover();

        self::assertSame('/users', $router->match(new Request('GET', '/users'))->path);
        $dynamic = $router->match(new Request('GET', '/users/42'));
        self::assertSame('/users/{id}', $dynamic->path);
        self::assertSame('42', $dynamic->parameters['id']);
    }

    /** Confirms Path appends an explicit template to a readable handler name. */
    public function testPathAttributeRefinesAReadableHttpHandler(): void
    {
        $namespace = 'RouteFixture' . bin2hex(random_bytes(4));
        $source = <<<PHP
<?php
namespace {$namespace}\\Endpoints;
use Bluewater\\Endpoint\\Endpoint;
use Bluewater\\Routing\\Path;
final class Users extends Endpoint {
    #[Path('/{id}/permissions')]
    public function getPermissions(int \$id): array { return ['id' => \$id]; }
}
PHP;
        file_put_contents($this->root . '/Endpoints/users.php', $source);

        $router = $this->router($namespace);
        $router->discover();
        $route = $router->match(new Request('GET', '/users/7/permissions'));

        self::assertSame('/users/{id}/permissions', $route->path);
        self::assertSame('7', $route->parameters['id']);
    }

    /** Builds a router over the current isolated fixture root. */
    private function router(string $namespace): Router
    {
        self::assertNotSame('', $namespace);
        /** @var non-empty-string $namespace */
        return new Router(
            new ApplicationDefinition('test', $namespace, $this->root, $this->root . '/cache', $this->root . '/logs'),
            new Config([]),
        );
    }

    /** Recursively removes one test-owned path. */
    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            @unlink($path);
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->remove($path . '/' . $item);
        }

        @rmdir($path);
    }
}
