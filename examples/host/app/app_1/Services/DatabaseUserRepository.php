<?php

/**
 * @file DatabaseUserRepository.php
 * @path examples/host/app/app_1/Services/DatabaseUserRepository.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example database user repository application service and its domain boundary.
 */

declare(strict_types=1);

namespace Apps\App1\Services;

use Apps\App1\DTO\UserDto;
use Bluewater\Database\Database;

/**
 * Persists example users through the framework's parameterized database boundary.
 *
 * Rows are mapped to immutable public DTOs. The repository owns SQL and mapping,
 * but delegates transaction and driver behavior to Database and performs no
 * transport serialization or authorization.
 */
final class DatabaseUserRepository implements UserRepository
{
    /** Retains the database adapter without executing a query. */
    public function __construct(private readonly Database $database)
    {
    }

    /** @inheritDoc */
    public function all(): array
    {
        return array_map($this->toDto(...), $this->database->fetchAll('SELECT id, email, name FROM users ORDER BY id'));
    }

    /** @inheritDoc */
    public function find(int $id): ?UserDto
    {
        $row = $this->database->fetchOne('SELECT id, email, name FROM users WHERE id = :id', ['id' => $id]);
        return $row === null ? null : $this->toDto($row);
    }

    /** @inheritDoc */
    public function create(string $email, string $name): UserDto
    {
        $this->database->execute(
            'INSERT INTO users (email, name) VALUES (:email, :name)',
            ['email' => $email, 'name' => $name],
        );
        $row = $this->database->fetchOne(
            'SELECT id, email, name FROM users WHERE email = :email ORDER BY id DESC LIMIT 1',
            ['email' => $email],
        );

        return $this->toDto($row ?? ['id' => 0, 'email' => $email, 'name' => $name]);
    }

    /** @inheritDoc */
    public function delete(int $id): bool
    {
        return $this->database->execute('DELETE FROM users WHERE id = :id', ['id' => $id]) > 0;
    }

    /**
     * Maps one trusted query row to a public DTO.
     *
     * @param array{id: int|numeric-string, email: string, name: string} $row
     */
    private function toDto(array $row): UserDto
    {
        return new UserDto((int) $row['id'], (string) $row['email'], (string) $row['name']);
    }
}
