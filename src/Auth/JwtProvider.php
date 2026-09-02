<?php

/**
 * @file JwtProvider.php
 * @path src/Auth/JwtProvider.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Validates HMAC-signed JWT bearer tokens and maps accepted claims to immutable identities.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use Bluewater\Http\Request;
use InvalidArgumentException;
use JsonException;

/**
 * Authenticates compact JWT bearer tokens signed with HMAC SHA-256.
 *
 * The provider validates compact serialization, base64url encoding, JSON
 * structure, the configured algorithm, signature, non-blank subject, required
 * expiration, optional not-before time, and configured issuer and audience.
 * Every malformed or unverifiable token is denied without exposing token data.
 * Verification uses constant-time signature comparison and never falls back to
 * another algorithm or provider. Authorization remains outside this class.
 */
final class JwtProvider implements AuthenticationProvider
{
    /**
     * Creates a fail-closed JWT verifier.
     *
     * @param non-empty-string $secret HMAC verification secret retained in
     *     memory and never logged or serialized.
     * @param 'HS256' $algorithm Closed set of supported JWT algorithms.
     * @param non-empty-string|null $issuer Exact required `iss` claim, or null
     *     when issuer validation is intentionally not configured.
     * @param non-empty-string|null $audience Required `aud` member, or null
     *     when audience validation is intentionally not configured.
     * @param non-negative-int $clockSkew Maximum temporal claim tolerance in seconds.
     *
     * @throws InvalidArgumentException When configuration is blank, unsupported,
     *     or specifies a negative clock-skew value.
     */
    public function __construct(
        private readonly string $secret,
        private readonly string $algorithm = 'HS256',
        private readonly ?string $issuer = null,
        private readonly ?string $audience = null,
        private readonly int $clockSkew = 0,
    ) {
        if ($this->secret === '') {
            throw new InvalidArgumentException('The JWT verification secret must not be empty.');
        }
        if ($this->algorithm !== 'HS256') {
            throw new InvalidArgumentException('JwtProvider supports only the HS256 algorithm.');
        }
        if ($this->issuer !== null && trim($this->issuer) === '') {
            throw new InvalidArgumentException('The configured JWT issuer must not be blank.');
        }
        if ($this->audience !== null && trim($this->audience) === '') {
            throw new InvalidArgumentException('The configured JWT audience must not be blank.');
        }
        if ($this->clockSkew < 0) {
            throw new InvalidArgumentException('JWT clock skew must not be negative.');
        }
    }

    /**
     * Validates an untrusted bearer token and maps its claims to an identity.
     *
     * Expiration and subject claims are mandatory. Issuer and audience become
     * mandatory when configured in the constructor. Parsing and expected token
     * failures return null; no raw token or signature enters an exception.
     *
     * @return Identity|null Validated immutable identity, or null on denial.
     */
    public function authenticate(Request $request): ?Identity
    {
        $authorization = $request->header('Authorization');
        if ($authorization === null || preg_match('/^Bearer\s+(\S+)$/i', $authorization, $matches) !== 1) {
            return null;
        }

        $parts = explode('.', $matches[1]);
        if (count($parts) !== 3 || in_array('', $parts, true)) {
            return null;
        }
        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeObject($encodedHeader);
        $claims = $this->decodeObject($encodedPayload);
        $signature = $this->decode($encodedSignature);
        if ($header === null || $claims === null || $signature === null) {
            return null;
        }
        if (($header['alg'] ?? null) !== $this->algorithm) {
            return null;
        }

        $expected = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $now = time();
        if (!is_int($claims['exp'] ?? null) && !is_float($claims['exp'] ?? null)) {
            return null;
        }
        if ((int) $claims['exp'] < $now - $this->clockSkew) {
            return null;
        }
        if (isset($claims['nbf'])) {
            if (!is_int($claims['nbf']) && !is_float($claims['nbf'])) {
                return null;
            }
            if ((int) $claims['nbf'] > $now + $this->clockSkew) {
                return null;
            }
        }
        if ($this->issuer !== null && !$this->matchesIssuer($claims)) {
            return null;
        }
        if ($this->audience !== null && !$this->matchesAudience($claims)) {
            return null;
        }

        $subject = $claims['sub'] ?? null;
        if (!is_string($subject) || trim($subject) === '') {
            return null;
        }

        $scopes = $claims['scope'] ?? $claims['scopes'] ?? [];
        if (is_string($scopes)) {
            $scopes = preg_split('/\s+/', trim($scopes)) ?: [];
        }

        return new Identity($subject, $claims, is_array($scopes) ? $scopes : []);
    }

    /**
     * Decodes one unpadded base64url segment without accepting invalid bytes.
     *
     * @return string|null Decoded bytes, or null when encoding is invalid.
     */
    private function decode(string $value): ?string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);
        return $decoded === false ? null : $decoded;
    }

    /** @return array<string, mixed>|null Decoded JSON object, or null on denial. */
    private function decodeObject(string $segment): ?array
    {
        $decoded = $this->decode($segment);
        if ($decoded === null) {
            return null;
        }

        try {
            $value = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    /** Returns true only when the scalar issuer exactly matches configuration. */
    private function matchesIssuer(array $claims): bool
    {
        $issuer = $claims['iss'] ?? null;
        return is_string($issuer) && hash_equals((string) $this->issuer, $issuer);
    }

    /** Returns true only when the configured audience is an exact `aud` member. */
    private function matchesAudience(array $claims): bool
    {
        $audience = $claims['aud'] ?? null;
        if (is_string($audience)) {
            return hash_equals((string) $this->audience, $audience);
        }
        if (!is_array($audience)) {
            return false;
        }

        foreach ($audience as $candidate) {
            if (is_string($candidate) && hash_equals((string) $this->audience, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
