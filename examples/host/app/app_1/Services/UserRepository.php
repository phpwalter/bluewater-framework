<?php

/**
 * @file UserRepository.php
 * @path examples/host/app/app_1/Services/UserRepository.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the example user repository application service and its domain boundary.
 */

declare(strict_types=1);

namespace Apps\App1\Services;

use Apps\App1\DTO\UserDto;

/**
 * Defines persistence operations required by the example user endpoints.
 *
 * Implementations preserve ascending identifier order for all(), return null
 * for an absent lookup, and distinguish an absent delete through false.
 */
interface UserRepository
{
    /** @return list<UserDto> Users ordered by ascending persistent identifier. */
    public function all(): array;

    /** @param positive-int $id Persistent identifier to find. */
    public function find(int $id): ?UserDto;

    /**
     * Persists one validated user.
     *
     * @param non-empty-string $email Unique validated email address.
     * @param non-empty-string $name Validated display name.
     */
    public function create(string $email, string $name): UserDto;

    /**
     * Removes one user when present.
     *
     * @param positive-int $id Persistent identifier to remove.
     *
     * @return bool True only when a row was removed.
     */
    public function delete(int $id): bool;
}
