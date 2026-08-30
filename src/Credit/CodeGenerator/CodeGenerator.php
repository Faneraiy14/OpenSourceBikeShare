<?php

namespace BikeShare\Credit\CodeGenerator;

class CodeGenerator implements CodeGeneratorInterface
{
    // exclude problem chars: B8G6I1l0OQDS5Z2
    private string $acceptableChars = 'ACEFHJKMNPRTUVWXY4937';

    public function generate($count, $length, $wastage = 25)
    {
        if ($length > strlen($this->acceptableChars)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot generate a code of length %d - only %d distinct characters are available.',
                $length,
                strlen($this->acceptableChars)
            ));
        }

        // $wastage extra attempts give room to still hit $count codes after
        // deduplication below removes any collisions - using array keys
        // (instead of array_unique() on the finished list) means a
        // duplicate never displaces a code that was already accepted, and
        // generation stops as soon as $count unique codes exist rather
        // than always running the full $count + $wastage iterations.
        $codes = [];
        $attempts = $count + $wastage;
        for ($i = 0; $i < $attempts && count($codes) < $count; $i++) {
            $codes[substr(str_shuffle($this->acceptableChars), 0, $length)] = true;
        }

        return array_slice(array_keys($codes), 0, $count);
    }
}
