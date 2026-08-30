<?php

declare(strict_types=1);

namespace BikeShare\Test\Unit\Credit\CodeGenerator;

use BikeShare\Credit\CodeGenerator\CodeGenerator;
use PHPUnit\Framework\TestCase;

class CodeGeneratorTest extends TestCase
{
    private $acceptableChars = 'ACEFHJKMNPRTUVWXY4937';

    public function testGenerate()
    {
        $count = 10;
        $length = 8;

        $codeGenerator = new CodeGenerator();
        $codes = $codeGenerator->generate($count, $length);
        $this->assertCount($count, $codes);
        foreach ($codes as $code) {
            $this->assertEquals($length, strlen($code));
            $this->assertMatchesRegularExpression('/^[' . $this->acceptableChars . ']{' . $length . '}$/', $code);
        }
    }

    public function testGeneratedCodesAreAlwaysUnique(): void
    {
        // Length 3 over the 21-character alphabet allows only
        // 21^3 = 9261 distinct codes, so 50 draws run a real chance of
        // repeating - this exercises the deduplication path rather than
        // relying on it never mattering.
        $codeGenerator = new CodeGenerator();
        $codes = $codeGenerator->generate(50, 3);

        $this->assertCount(50, $codes);
        $this->assertCount(50, array_unique($codes));
    }

    public function testThrowsWhenLengthIsBelowMinimum(): void
    {
        $codeGenerator = new CodeGenerator();

        $this->expectException(\InvalidArgumentException::class);

        $codeGenerator->generate(1, 2);
    }

    public function testThrowsRatherThanReturningFewerCodesThanRequested(): void
    {
        // Length 3 caps the alphabet at 21^3 = 9261 possible codes, so
        // asking for 10000 is impossible regardless of luck - this proves
        // generate() fails loudly on an unsatisfiable request instead of
        // silently returning an under-sized batch.
        $codeGenerator = new CodeGenerator();

        $this->expectException(\RuntimeException::class);

        $codeGenerator->generate(10000, 3);
    }
}
