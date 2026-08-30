<?php

declare(strict_types=1);

namespace BikeShare\Credit\CodeGenerator;

interface CodeGeneratorInterface
{
    /**
     * @return array<int, string>
     */
    public function generate(int $count, int $length): array;
}
