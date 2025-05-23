<?php

namespace ArthurTavaresDev\Temaki\Database\SqliteS3;

use PDO;
use PDOStatement;
use RuntimeException;

class PDOSQLiteS3 extends PDO
{
    public readonly DbSynchronizer $dbSynchronizer;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->dbSynchronizer = new DbSynchronizer($path);

        $dbFileName = $this->dbSynchronizer->open();

        parent::__construct('sqlite:' . $dbFileName);

        ConnectionTracker::trackConnection($this);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        $this->dbSynchronizer->close();
    }

    private function ensureIsNotClosed(): void
    {
        if (! $this->dbSynchronizer->isOpened()) {
            // The following error message is constructed so that Laravel detects this as a closed connection
            // and automatically reconnects to the database.
            // This is done via the magic "Lost connection" string.
            throw new RuntimeException('The SQLite database has been closed and uploaded to S3. You need to re-open the PDO connection. Lost connection');
        }
    }

    public function prepare(...$params): PDOStatement|false
    {
        $this->ensureIsNotClosed();

        return parent::prepare(...$params);
    }

    public function beginTransaction(): bool
    {
        $this->ensureIsNotClosed();

        return parent::beginTransaction();
    }

    public function commit(): bool
    {
        $this->ensureIsNotClosed();

        return parent::commit();
    }

    public function rollBack(): bool
    {
        $this->ensureIsNotClosed();

        return parent::rollBack();
    }

    public function exec(...$params): int|false
    {
        $this->ensureIsNotClosed();

        return parent::exec(...$params);
    }

    public function query(mixed ...$params): false|PDOStatement
    {
        $this->ensureIsNotClosed();

        return parent::query(...$params);
    }
}
