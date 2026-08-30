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
        $wastage = 25;

        $codeGenerator = new CodeGenerator();
        $codes = $codeGenerator->generate($count, $length, $wastage);
        $this->assertCount($count, $codes);
        foreach ($codes as $code) {
            $this->assertEquals($length, strlen($code));
            $this->assertMatchesRegularExpression('/^[' . $this->acceptableChars . ']{' . $length . '}$/', $code);
        }
    }

    public function testGeneratedCodesAreAlwaysUnique(): void
    {
        // A short length (3) over the 21-character alphabet leaves only
        // 21*20*19 = 7980 possible codes, so 75 attempts (count 50 +
        // wastage 25) run a real chance of colliding - this actually
        // exercises the deduplication path rather than relying on luck
        // to avoid it, while the assertion itself holds regardless of
        // whether a collision happened to occur on this particular run.
        $codeGenerator = new CodeGenerator();
        $codes = $codeGenerator->generate(50, 3, 25);

        $this->assertCount(50, $codes);
        $this->assertCount(50, array_unique($codes));
    }

    public function testThrowsWhenRequestedLengthExceedsTheAvailableAlphabet(): void
    {
        // Without this guard, substr(str_shuffle($this->acceptableChars), 0, $length)
        // can never produce more than strlen($this->acceptableChars) characters,
        // so a too-large $length used to silently return shorter-than-requested
        // codes instead of failing loudly.
        $codeGenerator = new CodeGenerator();

        $this->expectException(\InvalidArgumentException::class);

        $codeGenerator->generate(1, strlen($this->acceptableChars) + 1, 25);
    }
}
