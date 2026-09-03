<?php

namespace App\Domain\Pan;

use InvalidArgumentException;

/** 文件作用：依出生年支、太岁年支与性别推算大六壬本命和行年地支。 */
final class FateCalculator
{
    /** @return array{nianming: int, xingnian: int} */
    public function calculate(int $birthYearBranch, int $currentYearBranch, string $gender): array
    {
        if (! in_array($birthYearBranch, range(0, 11), true)
            || ! in_array($currentYearBranch, range(0, 11), true)) {
            throw new InvalidArgumentException('Birth and current year branches must be valid earthly branches.');
        }

        $annualFate = match ($gender) {
            'male' => $this->normalize(2 + $currentYearBranch - $birthYearBranch),
            'female' => $this->normalize(8 + $birthYearBranch - $currentYearBranch),
            default => throw new InvalidArgumentException('Gender must be male or female.'),
        };

        return [
            'nianming' => $birthYearBranch,
            'xingnian' => $annualFate,
        ];
    }

    private function normalize(int $branch): int
    {
        return ($branch % 12 + 12) % 12;
    }
}
