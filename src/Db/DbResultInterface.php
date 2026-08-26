<?php

namespace BikeShare\Db;

interface DbResultInterface
{
    /**
     * @return array|null
     */
    public function fetchAssoc();

    /**
     * @return array
     */
    public function fetchAllAssoc();

    /**
     * @return int
     */
    public function rowCount();
}
