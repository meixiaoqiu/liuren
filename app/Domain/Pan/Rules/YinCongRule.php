<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断前后夹拱干支、两贵、年命、日禄、昼夜贵所成的引从课。 */
final class YinCongRule implements PanRule
{
    protected const RULE_CODE = 'lesson.yincong';

    protected const NAME = '引从课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日辰干支前后上神发用为初末传，前后夹拱干支、两贵、年命、日禄或昼夜贵。';

    protected const GUA = '涣';

    protected const GUA_SYMBOL = '䷺';

    protected const XIANG = '拱夹支干，仕人佳兆，官职升迁，名利荣耀，孕生英儿，婚招金玉，出行取财，干贵欢笑。';

    /** @var list<string> 尚未覆盖的原文判断项 */
    private const UNCOVERED = [
        '日辰上乘墓鬼、六处遇冲克而凶散（未实现）',
        '干支并初中及中末拱地贵（未实现）',
    ];

    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var array<int, int> 十干昼贵：甲戊庚丑、乙己子、丙丁亥、六辛午、壬癸巳 */
    private const DAY_NOBLE = [1, 0, 11, 11, 1, 0, 1, 6, 5, 5];

    /** @var array<int, int> 十干夜贵：甲戊庚未、乙己申、丙丁酉、六辛寅、壬癸卯 */
    private const NIGHT_NOBLE = [7, 8, 9, 9, 7, 8, 7, 2, 3, 3];

    /** @var array<int, int> 十干禄（临官）：甲寅、乙卯、丙巳、丁午、戊巳、己午、庚申、辛酉、壬亥、癸子 */
    private const DAY_PROSPERITY = [2, 3, 5, 6, 5, 6, 8, 9, 11, 0];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $final = $facts->get('sanchuan2');
        $tianpan = $facts->get('tianpan');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');

        if (! is_int($initial) || ! is_int($final) || ! is_array($tianpan) || ! is_int($rigan) || ! is_int($rizhi)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($rigan);
        $dayNoble = self::DAY_NOBLE[$rigan] ?? null;
        $nightNoble = self::NIGHT_NOBLE[$rigan] ?? null;
        $lu = self::DAY_PROSPERITY[$rigan] ?? null;
        $nianming = $facts->get('nianming');
        $fuyin = $facts->hasPlatePattern('fuyin');

        // 拱天干：日干寄宫前一宫位上的上神发用作初传，后一宫位上的上神发用作末传。
        $gongGan = $lodging !== null
            && $initial === ($tianpan[($lodging + 1) % 12] ?? null)
            && $final === ($tianpan[($lodging + 11) % 12] ?? null);

        // 拱地支：日支前一宫位上的上神发用作初传，后一宫位上的上神发用作末传。
        $gongZhi = $initial === ($tianpan[($rizhi + 1) % 12] ?? null)
            && $final === ($tianpan[($rizhi + 11) % 12] ?? null);

        // 贵临干支拱年命：昼夜二贵分别加临日干寄宫与日支（方向不限），且干支前后夹拱年命。
        $gongNianming = $lodging !== null && is_int($nianming) && is_int($dayNoble) && is_int($nightNoble)
            && self::flanks($lodging, $rizhi, $nianming)
            && ((($tianpan[$lodging] ?? null) === $nightNoble && ($tianpan[$rizhi] ?? null) === $dayNoble)
                || (($tianpan[$lodging] ?? null) === $dayNoble && ($tianpan[$rizhi] ?? null) === $nightNoble));

        // 干支拱日禄（伏吟）：日干寄宫与日支前后夹拱日禄。
        $gongLu = $fuyin && $lodging !== null && is_int($lu)
            && self::flanks($lodging, $rizhi, $lu);

        // 干支拱夜贵（伏吟）：日干寄宫与日支前后夹拱夜贵。
        $gongNightNoble = $fuyin && $lodging !== null && is_int($nightNoble)
            && self::flanks($lodging, $rizhi, $nightNoble);

        // 干支拱昼贵（伏吟）：日干寄宫与日支前后夹拱昼贵。
        $gongDayNoble = $fuyin && $lodging !== null && is_int($dayNoble)
            && self::flanks($lodging, $rizhi, $dayNoble);

        if (! $gongGan && ! $gongZhi && ! $gongNianming && ! $gongLu && ! $gongNightNoble && ! $gongDayNoble) {
            return null;
        }

        $foundations = [];

        if ($gongGan) {
            // 两贵引从：拱天干的同时，初末传又恰好分别为昼夜二贵。
            $twoNobles = is_int($dayNoble) && is_int($nightNoble)
                && $initial === $dayNoble
                && $final === $nightNoble;

            // 拱贵：拱天干的同时，日干寄宫上神又乘昼夜贵。
            $stemCarriesNoble = self::isNoble($tianpan[$lodging] ?? null, $dayNoble, $nightNoble);

            $foundations[] = [
                'title' => '拱天干',
                'detail' => '日干寄宫'.self::BRANCH_NAMES[$lodging].'前一宫'.self::BRANCH_NAMES[($lodging + 1) % 12].'之上神'.self::BRANCH_NAMES[$initial].'发用作初传，后一宫'.self::BRANCH_NAMES[($lodging + 11) % 12].'之上神'.self::BRANCH_NAMES[$final].'作末传，前后夹拱天干。'
                    .($twoNobles ? '又为两贵引从：初末传恰为昼夜二贵。' : '')
                    .($stemCarriesNoble ? '又为拱贵：日干寄宫'.self::BRANCH_NAMES[$lodging].'上乘'.self::BRANCH_NAMES[$tianpan[$lodging]].'为贵。' : ''),
            ];
        }

        if ($gongZhi) {
            // 拱贵：拱地支的同时，日支上神又乘昼夜贵。
            $branchCarriesNoble = self::isNoble($tianpan[$rizhi] ?? null, $dayNoble, $nightNoble);

            $foundations[] = [
                'title' => '拱地支',
                'detail' => '日支'.self::BRANCH_NAMES[$rizhi].'前一宫'.self::BRANCH_NAMES[($rizhi + 1) % 12].'之上神'.self::BRANCH_NAMES[$initial].'发用作初传，后一宫'.self::BRANCH_NAMES[($rizhi + 11) % 12].'之上神'.self::BRANCH_NAMES[$final].'作末传，前后夹拱地支。'
                    .($branchCarriesNoble ? '又为拱贵：日支'.self::BRANCH_NAMES[$rizhi].'上乘'.self::BRANCH_NAMES[$tianpan[$rizhi]].'为贵。' : ''),
            ];
        }

        if ($gongNianming) {
            // 两贵方向：夜贵临干、昼贵临支，或昼贵临干、夜贵临支。
            $nightOnStem = ($tianpan[$lodging] ?? null) === $nightNoble;
            $stemNoble = $nightOnStem ? $nightNoble : $dayNoble;
            $branchNoble = $nightOnStem ? $dayNoble : $nightNoble;
            $stemLabel = $nightOnStem ? '夜贵' : '昼贵';
            $branchLabel = $nightOnStem ? '昼贵' : '夜贵';

            $foundations[] = [
                'title' => '贵临干支拱年命',
                'detail' => $stemLabel.self::BRANCH_NAMES[$stemNoble].'加临日干寄宫'.self::BRANCH_NAMES[$lodging].'，'.$branchLabel.self::BRANCH_NAMES[$branchNoble].'加临日支'.self::BRANCH_NAMES[$rizhi].'，干支前后夹拱年命'.self::BRANCH_NAMES[$nianming].'。',
            ];
        }

        if ($gongLu) {
            $foundations[] = [
                'title' => '干支拱日禄',
                'detail' => '日干寄宫'.self::BRANCH_NAMES[$lodging].'与日支'.self::BRANCH_NAMES[$rizhi].'前后夹拱日禄'.self::BRANCH_NAMES[$lu].'。',
            ];
        }

        if ($gongNightNoble) {
            $foundations[] = [
                'title' => '干支拱夜贵',
                'detail' => '日干寄宫'.self::BRANCH_NAMES[$lodging].'与日支'.self::BRANCH_NAMES[$rizhi].'前后夹拱夜贵'.self::BRANCH_NAMES[$nightNoble].'。',
            ];
        }

        if ($gongDayNoble) {
            $foundations[] = [
                'title' => '干支拱昼贵',
                'detail' => '日干寄宫'.self::BRANCH_NAMES[$lodging].'与日支'.self::BRANCH_NAMES[$rizhi].'前后夹拱昼贵'.self::BRANCH_NAMES[$dayNoble].'。',
            ];
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'foundations' => $foundations,
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /** 判断某上神是否为昼夜贵之一。 */
    private static function isNoble(?int $branch, ?int $dayNoble, ?int $nightNoble): bool
    {
        return $branch !== null
            && ($branch === $dayNoble || $branch === $nightNoble);
    }

    /** 判断 a、b 两个宫位是否前后夹拱 target（即分别位于 target 的前一宫与后一宫）。 */
    private static function flanks(int $a, int $b, int $target): bool
    {
        $front = ($target + 1) % 12;
        $back = ($target + 11) % 12;

        return ($a === $front && $b === $back) || ($a === $back && $b === $front);
    }
}
