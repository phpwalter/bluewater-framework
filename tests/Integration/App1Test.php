<?php

/**
 * @file App1Test.php
 * @path tests/Integration/App1Test.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies the app1test behavior and its observable framework contracts.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Integration;

use Bluewater\Host;
use Bluewater\Http\Request;
use PHPUnit\Framework\TestCase;

/** Verifies the example application through its complete host and request pipeline. */
final class App1Test extends TestCase
{
    /** @var non-empty-string Absolute example application root. */
    private string $appRoot;

    /** Configures process environment and clears test-owned runtime artifacts. */
    protected function setUp(): void
    {
        $this->appRoot = dirname(__DIR__, 2) . '/examples/host/app/app_1';
        putenv('BLUEWATER_APP_BASE=' . dirname($this->appRoot));
        putenv('BLUEWATER_ENV=testing');
        $this->cleanupRuntime();
    }

    /** Clears artifacts and removes the process environment overrides. */
    protected function tearDown(): void
    {
        $this->cleanupRuntime();
        putenv('BLUEWATER_APP_BASE');
        putenv('BLUEWATER_ENV');
    }

    /** Confirms that the health endpoint boots and returns JSON through Host. */
    public function testHealthEndpointBootsThroughSharedHost(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $response = $app->handle(new Request('GET', '/health', ['Accept' => 'application/json']));

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"status":"ok"', $response->body);
    }

    /** Confirms DTO validation becomes a structured 422 response. */
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

    /** Confirms directory middleware denies a missing key and accepts a valid key. */
    public function testDirectoryMiddlewareProtectsAdminEndpoint(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $unauthorized = $app->handle(new Request('GET', '/admin/stats'));
        $authorized = $app->handle(new Request('GET', '/admin/stats', ['X-API-Key' => 'demo-key']));

        self::assertSame(401, $unauthorized->status);
        self::assertSame(200, $authorized->status);
    }

    /** Confirms discovered routes appear in the generated OpenAPI document. */
    public function testOpenApiIsDerivedFromDiscoveredRoutes(): void
    {
        $app = Host::fromEnvironment()->application('app_1');
        $response = $app->handle(new Request('GET', '/openapi', ['Accept' => 'application/json']));

        self::assertSame(200, $response->status);
        $document = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($document);
        self::assertArrayHasKey('openapi', $document);
        self::assertArrayHasKey('paths', $document);
        self::assertIsArray($document['paths']);
        self::assertSame('3.1.0', $document['openapi']);
        self::assertArrayHasKey('/users/{id}', $document['paths']);
    }

    /** Removes only runtime files owned by the example integration fixture. */
    private function cleanupRuntime(): void
    {
        $runtimeFiles = [
            'cache/config.php',
            'cache/routes.php',
            'logs/app_1.log',
            'logs/application.log',
            'data/app_1.sqlite',
        ];
        foreach ($runtimeFiles as $relative) {
            @unlink($this->appRoot . '/' . $relative);
        }
    }
}
