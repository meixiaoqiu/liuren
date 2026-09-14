<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：为励德课、微服格、蹉跎格统一解释贵人临卯酉，以及四课阴阳神所乘天将在贵前、贵后或居中的位置。
 *
 * 规则边界：
 * 1. 励德课级成立只看天乙贵人是否临地盘卯或酉；
 * 2. 「前后」严格按天将固定序列判断：贵前五将=螣蛇、朱雀、六合、勾陈、青龙；贵后六将=天空、白虎、太常、玄武、太阴、天后；
 * 3. 贵人自身居中，不归入贵前或贵后；
 * 4. 日阳、日阴、辰阳、辰阴分别对应第一、二、三、四课上神。
 */
final class LideSupport
{
    /** @var list<int> */
    public const FRONT_GENERALS = [1, 2, 3, 4, 5];

    /** @var list<int> */
    public const REAR_GENERALS = [6, 7, 8, 9, 10, 11];

    public static function noblemanGround(PanFacts $facts): ?int
    {
        $ground = $facts->noblemanGroundPosition();

        return in_array($ground, [3, 9], true) ? $ground : null;
    }

    /**
     * @return array{
     *   day_yang: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
     *   day_yin: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
     *   branch_yang: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
     *   branch_yin: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string}
     * }|null
     */
    public static function fourGods(PanFacts $facts): ?array
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $sike = $facts->get('sike');
        $tianpan = $facts->get('tianpan');

        if (! is_int($stem) || $stem < 0 || $stem > 9
            || ! is_int($branch) || $branch < 0 || $branch > 11
            || ! is_array($sike) || ! array_is_list($sike) || count($sike) < 8
            || ! is_array($tianpan) || ! array_is_list($tianpan) || count($tianpan) !== 12) {
            return null;
        }

        $stemLodging = $facts->stemLodgingBranch($stem);
        if ($stemLodging === null) {
            return null;
        }

        $raw = [
            'day_yang' => ['label' => '日阳', 'lesson' => 1, 'ground' => $stemLodging, 'upper' => $sike[1] ?? null],
            'day_yin' => ['label' => '日阴', 'lesson' => 2, 'ground' => $sike[1] ?? null, 'upper' => $sike[3] ?? null],
            'branch_yang' => ['label' => '辰阳', 'lesson' => 3, 'ground' => $branch, 'upper' => $sike[5] ?? null],
            'branch_yin' => ['label' => '辰阴', 'lesson' => 4, 'ground' => $sike[5] ?? null, 'upper' => $sike[7] ?? null],
        ];

        foreach ($raw as $key => $item) {
            $ground = $item['ground'];
            $upper = $item['upper'];

            if (! is_int($ground) || $ground < 0 || $ground > 11
                || ! is_int($upper) || $upper < 0 || $upper > 11
                || ($tianpan[$ground] ?? null) !== $upper) {
                return null;
            }

            $general = $facts->generalAtGroundPosition($ground);
            $side = is_int($general) ? self::side($general) : null;

            if ($side === null) {
                return null;
            }

            $raw[$key]['general'] = $general;
            $raw[$key]['side'] = $side;
        }

        /** @var array{
         *   day_yang: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
         *   day_yin: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
         *   branch_yang: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string},
         *   branch_yin: array{label: string, lesson: int, ground: int, upper: int, general: int, side: string}
         * } $raw
         */
        return $raw;
    }

    public static function side(int $general): ?string
    {
        if ($general === 0) {
            return 'center';
        }

        if (in_array($general, self::FRONT_GENERALS, true)) {
            return 'front';
        }

        if (in_array($general, self::REAR_GENERALS, true)) {
            return 'rear';
        }

        return null;
    }

    /** @param array<string, array{side: string}> $gods */
    public static function pattern(array $gods): ?string
    {
        foreach (['day_yang', 'day_yin', 'branch_yang', 'branch_yin'] as $key) {
            if (! isset($gods[$key]['side'])) {
                return null;
            }
        }

        $dayYang = $gods['day_yang']['side'];
        $dayYin = $gods['day_yin']['side'];
        $branchYang = $gods['branch_yang']['side'];
        $branchYin = $gods['branch_yin']['side'];

        if ($dayYang === 'rear' && $dayYin === 'rear' && $branchYang === 'rear' && $branchYin === 'rear') {
            return 'weifu';
        }

        if ($dayYang === 'front' && $dayYin === 'front' && $branchYang === 'front' && $branchYin === 'front') {
            return 'cuotuo';
        }

        if ($dayYang === 'front' && $branchYang === 'front' && $dayYin === 'rear' && $branchYin === 'rear') {
            return 'yang_front_yin_rear';
        }

        if ($dayYin === 'front' && $branchYin === 'front' && $dayYang === 'rear' && $branchYang === 'rear') {
            return 'yin_front_yang_rear';
        }

        return 'mixed';
    }

    public static function patternLabel(?string $pattern): string
    {
        return match ($pattern) {
            'weifu' => '微服格',
            'cuotuo' => '蹉跎格',
            'yang_front_yin_rear' => '阳前阴后',
            'yin_front_yang_rear' => '阴前阳后',
            'mixed' => '未落入四种完整分型',
            default => '分型资料不足',
        };
    }

    public static function sideLabel(string $side): string
    {
        return match ($side) {
            'front' => '贵前',
            'rear' => '贵后',
            'center' => '贵人居中',
            default => '未知',
        };
    }

    /** @param array<string, array{label: string, upper: int, general: int, side: string}> $gods */
    public static function describe(array $gods): string
    {
        $parts = [];

        foreach (['day_yang', 'day_yin', 'branch_yang', 'branch_yin'] as $key) {
            if (! isset($gods[$key])) {
                continue;
            }

            $item = $gods[$key];
            $branch = PanCalculator::$dizhi[$item['upper']] ?? '?';
            $general = PanCalculator::$tianjiang[$item['general']] ?? '?';
            $parts[] = "{$item['label']}{$branch}乘{$general}（".self::sideLabel($item['side']).'）';
        }

        return implode('；', $parts);
    }
}
