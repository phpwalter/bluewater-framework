<?php

declare(strict_types=1);

namespace Bluewater\Tests\Routing;

use Bluewater\ApplicationDefinition;
use Bluewater\Config\Config;
use Bluewater\Http\Request;
use Bluewater\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/bluewater-router-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/Endpoints', 0777, true);
        mkdir($this->root . '/cache', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

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

    private function router(string $namespace): Router
    {
        return new Router(
            new ApplicationDefinition('test', $namespace, $this->root, $this->root . '/cache', $this->root . '/logs'),
            new Config([]),
        );
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { @unlink($path); return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) { $this->remove($path . '/' . $item); }
        @rmdir($path);
    }
}
