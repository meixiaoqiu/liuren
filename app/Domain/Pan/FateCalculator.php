<?php

namespace App\Domain\Pan;

use InvalidArgumentException;

/** 文件作用：依出生与起课的干支年序号（六十甲子 index）及性别推算本命与行年干支。 */
final class FateCalculator
{
    /**
     * 依出生、起课的干支年序号（六十甲子 index，0-59）及性别推算本命地支与行年干支。
     *
     * 行年依《观月经》「男行年一岁起丙寅顺行、女行年一岁起壬申逆行」：以出生与起课
     * 在六十甲子中的年序差（虚岁 − 1）为步数，由同一序差一次性推出行年天干与地支，
     * 保证恒为合法六十甲子。干支年序号由 Tyme 按立春分界，故元旦至立春之间的出生或
     * 起课也能与年支保持一致。
     *
     * @return array{nianming: int, xingnian: int, xingnian_gan: int}
     */
    public function calculate(int $birthYearIndex, int $currentYearIndex, string $gender): array
    {
        if (! in_array($birthYearIndex, range(0, 59), true)
            || ! in_array($currentYearIndex, range(0, 59), true)) {
            throw new InvalidArgumentException('Year indices must be valid sexagenary indices (0-59).');
        }

        $delta = ($currentYearIndex - $birthYearIndex + 60) % 60;

        $xingnianIndex = match ($gender) {
            'male' => (2 + $delta) % 60,
            'female' => (8 - $delta + 60) % 60,
            default => throw new InvalidArgumentException('Gender must be male or female.'),
        };

        return [
            'nianming' => $birthYearIndex % 12,
            'xingnian' => $xingnianIndex % 12,
            'xingnian_gan' => $xingnianIndex % 10,
        ];
    }
}
