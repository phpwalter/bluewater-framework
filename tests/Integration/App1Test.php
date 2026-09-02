<?php

declare(strict_types=1);

namespace Bluewater\Tests\Integration;

use Bluewater\Host;
use Bluewater\Http\Request;
use PHPUnit\Framework\TestCase;

final class App1Test extends TestCase
{
    private string $appRoot;

    protected function setUp(): void
    {
        $this->appRoot = dirname(__DIR__, 2) . '/examples/host/app/app_1';
        putenv('BLUEWATER_APP_BASE=' . dirname($this->appRoot));
        putenv('BLUEWATER_ENV=testing');
        $this->cleanupRuntime();
    }

    protected function tearDown(): void
    {
        $this->cleanupRuntime();
        putenv('BLUEWATER_APP_BASE');
        putenv('BLUEWATER_ENV');
    }

    public function testHealthEndpointBootsThroughSharedHost(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $response = $app->handle(new Request('GET', '/health', ['Accept' => 'application/json']));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"status":"ok"', $response->body);
    }

    public function testValidationIsFirstClass(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $response = $app->handle(new Request(
            'POST',
            '/users',
            ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            [],
            ['email' => 'not-an-email', 'name' => 'x'],
        ));

        self::assertSame(422, $response->status);
        self::assertStringContainsString('validation_failed', $response->body);
    }

    public function testDirectoryMiddlewareProtectsAdminEndpoint(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $unauthorized = $app->handle(new Request('GET', '/admin/stats'));
        $authorized = $app->handle(new Request('GET', '/admin/stats', ['X-API-Key' => 'demo-key']));

        self::assertSame(401, $unauthorized->status);
        self::assertSame(200, $authorized->status);
    }

    public function testOpenApiIsDerivedFromDiscoveredRoutes(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $response = $app->handle(new Request('GET', '/openapi', ['Accept' => 'application/json']));

        self::assertSame(200, $response->status);
        $document = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('3.1.0', $document['openapi']);
        self::assertArrayHasKey('/users/{id}', $document['paths']);
    }

    private function cleanupRuntime(): void
    {
        foreach (['cache/config.php', 'cache/routes.php', 'logs/app_1.log', 'logs/application.log', 'data/app_1.sqlite'] as $relative) {
            @unlink($this->appRoot . '/' . $relative);
        }
    }
}
