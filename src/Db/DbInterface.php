<?php

declare(strict_types=1);

namespace BikeShare\Db;

interface DbInterface
{
    public function query(string $query, array $params = []): DbResultInterface;

    public function exec(string $query): int|bool;

    public function getLastInsertId(): int;
}
