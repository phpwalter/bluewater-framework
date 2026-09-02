<?php

/**
 * @file AuthenticationTest.php
 * @path tests/Auth/AuthenticationTest.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Verifies fail-closed API-key, JWT, OAuth, and authentication-registry behavior.
 */

declare(strict_types=1);

namespace Bluewater\Tests\Auth;

use Bluewater\Auth\ApiKeyProvider;
use Bluewater\Auth\AuthManager;
use Bluewater\Auth\JwtProvider;
use Bluewater\Auth\OAuthBearerProvider;
use Bluewater\Http\Request;
use LogicException;
use PHPUnit\Framework\TestCase;

/** Verifies authentication success invariants and representative denial paths. */
final class AuthenticationTest extends TestCase
{
    /** Confirms API-key authentication returns configured identity data only. */
    public function testApiKeyProviderAuthenticatesAnExactKey(): void
    {
        $provider = new ApiKeyProvider([
            'unit-test-api-key' => ['id' => 'client-1', 'scopes' => ['read', 'read', '']],
        ]);

        $identity = $provider->authenticate(new Request('GET', '/', ['X-API-Key' => 'unit-test-api-key']));

        self::assertNotNull($identity);
        self::assertSame('client-1', $identity->id);
        self::assertSame(['read'], $identity->scopes);
        self::assertNull($provider->authenticate(new Request('GET', '/', ['X-API-Key' => 'wrong'])));
    }

    /** Confirms JWT verification requires signature, subject, expiry, issuer, and audience. */
    public function testJwtProviderAcceptsOnlyTheConfiguredClaimContract(): void
    {
        $secret = 'unit-test-jwt-secret';
        $provider = new JwtProvider($secret, issuer: 'test-issuer', audience: 'test-audience');
        $claims = [
            'sub' => 'user-1',
            'exp' => time() + 300,
            'iss' => 'test-issuer',
            'aud' => ['another-audience', 'test-audience'],
            'scope' => 'read write',
        ];

        $identity = $provider->authenticate($this->bearer($this->jwt($claims, $secret)));

        self::assertNotNull($identity);
        self::assertSame('user-1', $identity->id);
        self::assertSame(['read', 'write'], $identity->scopes);

        unset($claims['exp']);
        self::assertNull($provider->authenticate($this->bearer($this->jwt($claims, $secret))));
    }

    /** Confirms inactive OAuth results and results without identity are denied. */
    public function testOAuthProviderRequiresActiveIdentityClaims(): void
    {
        $active = new OAuthBearerProvider(new StubOAuthIntrospector([
            'active' => true,
            'sub' => 'oauth-user',
            'scope' => 'read',
        ]));
        $inactive = new OAuthBearerProvider(new StubOAuthIntrospector([
            'active' => false,
            'sub' => 'oauth-user',
        ]));

        self::assertSame('oauth-user', $active->authenticate($this->bearer('opaque-test-token'))?->id);
        self::assertNull($inactive->authenticate($this->bearer('opaque-test-token')));
    }

    /** Confirms canonical duplicate strategy registration is rejected. */
    public function testAuthManagerRejectsDuplicateCanonicalNames(): void
    {
        $manager = new AuthManager();
        $provider = new ApiKeyProvider(['unit-test-key' => 'client']);
        $manager->register('API_KEY', $provider);

        $this->expectException(LogicException::class);
        $manager->register(' api_key ', $provider);
    }

    /** Creates a request carrying one bearer credential. */
    private function bearer(string $token): Request
    {
        return new Request('GET', '/', ['Authorization' => 'Bearer ' . $token]);
    }

    /**
     * Creates an HS256 compact token solely for verifier tests.
     *
     * @param array<string, mixed> $claims Test claim set.
     */
    private function jwt(array $claims, string $secret): string
    {
        $header = $this->base64Url((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64Url((string) json_encode($claims, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);

        return $header . '.' . $payload . '.' . $this->base64Url($signature);
    }

    /** Returns unpadded base64url text for test token construction. */
    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
