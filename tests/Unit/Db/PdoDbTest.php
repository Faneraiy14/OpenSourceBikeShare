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
}
