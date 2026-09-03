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
        return array_map(
            fn (array $row): UserDto => $this->toDto($row),
            $this->database->fetchAll('SELECT id, email, name FROM users ORDER BY id'),
        );
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

        if ($row === null) {
            throw new \UnexpectedValueException('The inserted user could not be reloaded.');
        }

        return $this->toDto($row);
    }

    /** @inheritDoc */
    public function delete(int $id): bool
    {
        return $this->database->execute('DELETE FROM users WHERE id = :id', ['id' => $id]) > 0;
    }

    /**
     * Maps one trusted query row to a public DTO.
     *
     * @param array<string, mixed> $row Trusted query row before runtime shape validation.
     *
     * @throws \UnexpectedValueException When the database row violates the selected shape.
     */
    private function toDto(array $row): UserDto
    {
        $id = $row['id'] ?? null;
        $email = $row['email'] ?? null;
        $name = $row['name'] ?? null;
        if ((!is_int($id) && !is_string($id)) || !is_numeric($id) || (int) $id < 1) {
            throw new \UnexpectedValueException('User row contains an invalid identifier.');
        }
        if (!is_string($email) || $email === '' || !is_string($name) || $name === '') {
            throw new \UnexpectedValueException('User row contains invalid text fields.');
        }

        return new UserDto((int) $id, $email, $name);
    }
}
