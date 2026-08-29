<?php

namespace BikeShare\Db;

interface DbResultInterface
{
    public function fetchAssoc(): ?array;

    public function fetchAllAssoc(): array;

    public function rowCount(): int;
}
