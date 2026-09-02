<?php

declare(strict_types=1);

namespace Apps\App1\Services;

use Apps\App1\DTO\UserDto;

interface UserRepository
{
    /** @return UserDto[] */
    public function all(): array;
    public function find(int $id): ?UserDto;
    public function create(string $email, string $name): UserDto;
    public function delete(int $id): bool;
}
