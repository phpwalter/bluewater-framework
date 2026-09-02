<?php

/**
 * @file PdoDatabase.php
 * @path src/Database/PdoDatabase.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Implements parameterized database access and transactional execution over PDO.
 */

declare(strict_types=1);

namespace Bluewater\Database;

use PDO;
use Throwable;

/**
 * Provides parameterized SQL execution and transactions over one PDO connection.
 *
 * The adapter forces exception error mode and associative fetches. It prepares
 * every statement and passes parameter values separately. It does not build SQL,
 * quote identifiers, retry failures, or manage nested transactions.
 */
final class PdoDatabase implements Database
{
    /**
     * Configures and retains a caller-owned PDO connection.
     *
     * The connection's error and fetch mode attributes are mutated immediately.
     */
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Opens and configures a PDO connection.
     *
     * @param non-empty-string $dsn PDO driver data-source name.
     * @param string|null $username Database user, or null for driver default.
     * @param string|null $password Sensitive credential retained by PDO; never logged.
     * @param array<int, mixed> $options PDO constructor options.
     *
     * @return self Newly allocated database adapter.
     *
     * @throws \PDOException When the driver cannot establish the connection.
     */
    public static function connect(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
    ): self {
        return new self(new PDO($dsn, $username, $password, $options));
    }

    /** @inheritDoc */
    public function fetchOne(string $sql, array $parameters = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @inheritDoc */
    public function fetchAll(string $sql, array $parameters = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /** @inheritDoc */
    public function execute(string $sql, array $parameters = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        return $statement->rowCount();
    }

    /**
     * Executes a callback and commits only after it returns successfully.
     *
     * Any throwable triggers rollback when the connection still has an active
     * transaction, then the same throwable is rethrown. The adapter does not
     * support nesting and performs no automatic retry.
     *
     * @template TResult
     *
     * @param callable(self): TResult $callback Synchronous transactional work.
     *
     * @return TResult Callback result after commit.
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Exposes the retained connection for driver-specific operations.
     *
     * Callers can mutate connection state and must preserve the adapter's error
     * and fetch-mode guarantees.
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
