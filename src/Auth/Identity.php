<?php

/**
 * @file Identity.php
 * @path src/Auth/Identity.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the immutable authenticated identity, claims, and scopes attached to accepted requests.
 */

declare(strict_types=1);

namespace Bluewater\Auth;

use InvalidArgumentException;

/**
 * Represents the immutable identity established by an authentication provider.
 *
 * The identifier is always non-blank. Claims retain provider-supplied values,
 * while scopes are normalized to a unique list of non-blank strings. Identity
 * does not authorize actions and must not be treated as proof of a permission
 * without a separate policy decision.
 */
final readonly class Identity
{
    /** @var non-empty-string Stable provider subject or client identifier. */
    public string $id;

    /** @var array<string, mixed> Validated provider claims. */
    public array $claims;

    /** @var list<non-empty-string> Unique scopes in first-seen order. */
    public array $scopes;

    /**
     * Creates an identity after enforcing its cross-provider invariants.
     *
     * @param non-empty-string $id Stable provider subject or client identifier.
     * @param array<string, mixed> $claims Validated provider claims. Callers
     *     must avoid logging or publicly serializing sensitive claim values.
     * @param list<string> $scopes Provider scopes; blank and duplicate values
     *     are removed while first-seen ordering is preserved.
     *
     * @throws InvalidArgumentException When the identifier is blank.
     */
    public function __construct(
        string $id,
        array $claims = [],
        array $scopes = [],
    ) {
        $id = trim($id);
        if ($id === '') {
            throw new InvalidArgumentException('An authenticated identity requires a non-blank identifier.');
        }

        $normalizedScopes = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope) || trim($scope) === '') {
                continue;
            }

            $normalizedScopes[] = trim($scope);
        }

        $this->id = $id;
        $this->claims = $claims;
        $this->scopes = array_values(array_unique($normalizedScopes));
    }
}
