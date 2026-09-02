<?php

/**
 * @file Database.php
 * @path src/Database/Database.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer ApiForge Team
 * @status dev
 *
 * Defines the database access and transaction contract exposed to application repositories.
 */

declare(strict_types=1);

namespace Bluewater\Database;

/**
 * Defines parameterized database operations available to application code.
 *
 * Implementations must bind supplied parameters instead of interpolating them,
 * return associative rows, and provide atomic transaction execution. SQL text
 * remains trusted application code; this interface does not sanitize identifiers
 * or fragments that callers embed directly in a statement.
 */
interface Database
{
    /**
     * Executes a query and returns its first row.
     *
     * @param non-empty-string $sql Trusted SQL containing driver placeholders.
     * @param array<string|int, scalar|null> $parameters Bound parameter values.
     *
     * @return array<string, mixed>|null Associative row, or null when no row exists.
     */
    public function fetchOne(string $sql, array $parameters = []): ?array;

    /**
     * Executes a query and returns every row in driver order.
     *
     * @param non-empty-string $sql Trusted SQL containing driver placeholders.
     * @param array<string|int, scalar|null> $parameters Bound parameter values.
     *
     * @return list<array<string, mixed>> Associative rows; an empty list is valid.
     */
    public function fetchAll(string $sql, array $parameters = []): array;

    /**
     * Executes a parameterized statement and returns the affected-row count.
     *
     * @param non-empty-string $sql Trusted SQL containing driver placeholders.
     * @param array<string|int, scalar|null> $parameters Bound parameter values.
     *
     * @return non-negative-int Driver-reported affected-row count.
     */
    public function execute(string $sql, array $parameters = []): int;

    /**
     * Executes one synchronous callback in an atomic transaction.
     *
     * @template TResult
     *
     * @param callable(self): TResult $callback Callback invoked exactly once.
     *
     * @return TResult Unmodified callback result after a successful commit.
     */
    public function transaction(callable $callback): mixed;
}
