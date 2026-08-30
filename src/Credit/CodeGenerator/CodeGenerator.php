<?php

declare(strict_types=1);

namespace BikeShare\Credit\CodeGenerator;

class CodeGenerator implements CodeGeneratorInterface
{
    private const MIN_LENGTH = 3;
    private const WASTAGE = 25;

    // exclude problem chars: B8G6I1l0OQDS5Z2
    private string $acceptableChars = 'ACEFHJKMNPRTUVWXY4937';

    public function generate(int $count, int $length): array
    {
        if ($length < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Length must be at least %d, got %d.', self::MIN_LENGTH, $length)
            );
        }

        $maxAttempts = $count + self::WASTAGE;
        $codes = [];
        for ($attempt = 0; $attempt < $maxAttempts && count($codes) < $count; $attempt++) {
            $codes[$this->randomCode($length)] = true;
        }

        if (count($codes) < $count) {
            throw new \RuntimeException(sprintf(
                'Could not generate %d unique codes of length %d after %d attempts.',
                $count,
                $length,
                $maxAttempts
            ));
        }

        return array_slice(array_keys($codes), 0, $count);
    }

    private function randomCode(int $length): string
    {
        $code = '';
        $lastIndex = strlen($this->acceptableChars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $this->acceptableChars[random_int(0, $lastIndex)];
        }

        return $code;
    }
}
