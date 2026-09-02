<?php

declare(strict_types=1);

namespace Bluewater\Database;

interface Database
{
    public function fetchOne(string $sql, array $parameters = []): ?array;
    public function fetchAll(string $sql, array $parameters = []): array;
    public function execute(string $sql, array $parameters = []): int;
    public function transaction(callable $callback): mixed;
}
