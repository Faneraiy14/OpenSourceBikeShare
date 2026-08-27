<?php

declare(strict_types=1);

namespace BikeShare\Db;

use PDO;
use PDOStatement;

class PdoDbResult implements DbResultInterface
{
    private readonly PDOStatement $result;

    public function __construct(PDOStatement $result)
    {
        $this->result = $result;
    }

    public function fetchAssoc(): ?array
    {
        if ($this->result->rowCount() > 0) {
            return $this->result->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }

    public function fetchAllAssoc(): array
    {
        return $this->result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @phpcs:disable Generic.NamingConventions.CamelCapsFunctionName
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName
     */
    #[\Deprecated(message: 'use fetchAssoc')]
    public function fetch_assoc(): ?array
    {
        return $this->fetchAssoc();
    }

    public function rowCount(): int
    {
        return $this->result->rowCount();
    }
}
