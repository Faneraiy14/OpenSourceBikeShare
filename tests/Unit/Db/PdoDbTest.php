<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Db;

use BikeShare\Db\PdoDb;
use PHPUnit\Framework\TestCase;

class PdoDbTest extends TestCase
{
    public function testFailedConnectionThrowsPdoExceptionInsteadOfReturningFalse(): void
    {
        $this->expectException(\PDOException::class);

        new PdoDb('this-is-not-a-valid-dsn', 'user', 'password');
    }

    public function testFailedQueryThrowsPdoExceptionInsteadOfReturningFalse(): void
    {
        $db = new PdoDb('sqlite::memory:', '', '');

        $this->expectException(\PDOException::class);

        $db->query('SELECT * FROM this_table_does_not_exist');
    }

    /**
     * Regresses the bug sveneld found in review: fetchAssoc() used to gate on
     * rowCount() > 0 instead of checking fetch()'s own return value. On
     * MariaDB rowCount() doesn't decrease as the cursor is consumed, so the
     * terminating call of a `while ($row = fetchAssoc())` loop saw
     * rowCount() > 0 (stale from the initial count) while fetch() itself
     * had already exhausted the set and returned false - and since
     * fetchAssoc() declares : ?array, returning that raw false is a
     * TypeError, not a clean "no more rows" signal.
     *
     * SQLite's rowCount() is unreliable for SELECTs in general (it's not
     * required to track them at all), which reproduces the same shape of
     * mismatch as the MariaDB case sveneld observed directly.
     */
    public function testFetchAssocTerminatesMultiRowLoopWithNullInsteadOfThrowing(): void
    {
        $db = new PdoDb('sqlite::memory:', '', '');
        $db->exec('CREATE TABLE t (n INTEGER)');
        $db->exec('INSERT INTO t (n) VALUES (1), (2)');

        $result = $db->query('SELECT n FROM t ORDER BY n');

        $seen = [];
        while ($row = $result->fetchAssoc()) {
            $seen[] = $row['n'];
        }

        self::assertSame([1, 2], $seen);
        self::assertNull($result->fetchAssoc());
    }
}
