<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：统一盘珠课、天心格、回还格对“四课中地支集合”、四建与三传的取值口径。 */
final class PanzhuSupport
{
    /**
     * “四课之中”按四课整体结构中的地支取值，不把第一课下位的日干 sike[0] 当作地支。
     * 第二课下位、第四课下位虽分别重复第一课、第三课上神，仍保留原始结构后再去重。
     *
     * @return array{
     *     lesson_branches: list<int>,
     *     four_establishments: array{year: int, month: int, day: int, hour: int},
     *     transmissions: list<int>
     * }|null
     */
    public static function analyze(PanFacts $facts): ?array
    {
        $year = $facts->get('nianzhi');
        $month = $facts->get('yuezhi');
        $day = $facts->get('rizhi');
        $hour = $facts->get('shizhi');
        $sike = $facts->get('sike');
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        if (! is_int($year) || ! is_int($month) || ! is_int($day) || ! is_int($hour)
            || ! is_array($sike) || count($sike) < 8
            || ! self::allValidBranches([$year, $month, $day, $hour])
            || ! self::allValidBranches($transmissions)) {
            return null;
        }

        // sike[0] 是日干；其余七个位置均按地支处理。
        $lessonBranches = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $index) {
            $branch = $sike[$index] ?? null;
            if (! is_int($branch) || $branch < 0 || $branch > 11) {
                return null;
            }
            $lessonBranches[] = $branch;
        }

        return [
            'lesson_branches' => array_values(array_unique($lessonBranches)),
            'four_establishments' => [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'hour' => $hour,
            ],
            'transmissions' => $transmissions,
        ];
    }

    /**
     * @param  list<int>  $needles
     * @param  list<int>  $haystack
     */
    public static function allIn(array $needles, array $haystack): bool
    {
        foreach ($needles as $needle) {
            if (! in_array($needle, $haystack, true)) {
                return false;
            }
        }

        return true;
    }

    /** @param list<int> $branches */
    public static function branchNames(array $branches): string
    {
        return implode('、', array_map(
            static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?',
            $branches,
        ));
    }

    /** @param array<int, mixed> $branches */
    private static function allValidBranches(array $branches): bool
    {
        foreach ($branches as $branch) {
            if (! is_int($branch) || $branch < 0 || $branch > 11) {
                return false;
            }
        }

        return true;
    }
}
