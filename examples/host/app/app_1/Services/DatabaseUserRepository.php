<?php

declare(strict_types=1);

namespace Apps\App1\Services;

use Apps\App1\DTO\UserDto;
use Bluewater\Database\Database;

final class DatabaseUserRepository implements UserRepository
{
    public function __construct(private readonly Database $database) {}

    public function all(): array
    {
        return array_map($this->toDto(...), $this->database->fetchAll('SELECT id, email, name FROM users ORDER BY id'));
    }

    public function find(int $id): ?UserDto
    {
        $row = $this->database->fetchOne('SELECT id, email, name FROM users WHERE id = :id', ['id' => $id]);
        return $row === null ? null : $this->toDto($row);
    }

    public function create(string $email, string $name): UserDto
    {
        $this->database->execute('INSERT INTO users (email, name) VALUES (:email, :name)', ['email' => $email, 'name' => $name]);
        $row = $this->database->fetchOne('SELECT id, email, name FROM users WHERE email = :email ORDER BY id DESC LIMIT 1', ['email' => $email]);
        return $this->toDto($row ?? ['id' => 0, 'email' => $email, 'name' => $name]);
    }

    public function delete(int $id): bool
    {
        return $this->database->execute('DELETE FROM users WHERE id = :id', ['id' => $id]) > 0;
    }

    private function toDto(array $row): UserDto
    {
        return new UserDto((int) $row['id'], (string) $row['email'], (string) $row['name']);
    }
}
