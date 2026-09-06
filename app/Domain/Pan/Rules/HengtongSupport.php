<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为亨通课及其递生、俱生、互生、俱旺、互旺五格提供共享的五行相生、帝旺判断与成格说明。 */
trait HengtongSupport
{
    /** @var list<string> */
    protected const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var list<string> */
    protected const STEM_NAMES = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    /** @var array<int, int> 五行帝旺之地支（木卯、火午、土午、金酉、水子；土随火） */
    protected const ELEMENT_WANG_BRANCH = [3, 6, 6, 9, 0];

    /** 地支 a 生地支 b。 */
    protected static function branchGenerates(PanFacts $facts, int $a, int $b): bool
    {
        $ea = $facts->branchElement($a);
        $eb = $facts->branchElement($b);

        return $ea !== null && $eb !== null && $eb === ($ea + 1) % 5;
    }

    /** 地支 a 生天干 s。 */
    protected static function branchGeneratesStem(PanFacts $facts, int $a, int $s): bool
    {
        $ea = $facts->branchElement($a);
        $es = $facts->stemElement($s);

        return $ea !== null && $es !== null && $es === ($ea + 1) % 5;
    }

    /** 天干 s 的帝旺之地支。 */
    protected static function wangBranchOfStem(PanFacts $facts, int $s): ?int
    {
        $element = $facts->stemElement($s);

        return $element === null ? null : self::ELEMENT_WANG_BRANCH[$element];
    }

    /** 地支 b 的帝旺之地支。 */
    protected static function wangBranchOfBranch(PanFacts $facts, int $b): ?int
    {
        $element = $facts->branchElement($b);

        return $element === null ? null : self::ELEMENT_WANG_BRANCH[$element];
    }

    /** @return array{initial: int, middle: int, final: int, rigan: int, rizhi: int, stemUpper: int, branchUpper: int}|null */
    protected static function hengtongData(PanFacts $facts): ?array
    {
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $sike = $facts->get('sike');

        if (! is_int($initial) || ! is_int($middle) || ! is_int($final)
            || ! is_int($rigan) || ! is_int($rizhi) || ! is_array($sike)) {
            return null;
        }

        $stemUpper = $sike[1] ?? null;
        $branchUpper = $sike[5] ?? null;

        if (! is_int($stemUpper) || ! is_int($branchUpper)) {
            return null;
        }

        return [
            'initial' => $initial,
            'middle' => $middle,
            'final' => $final,
            'rigan' => $rigan,
            'rizhi' => $rizhi,
            'stemUpper' => $stemUpper,
            'branchUpper' => $branchUpper,
        ];
    }

    /** 递生格成格说明；未命中返回 null。 */
    protected static function diShengDetail(PanFacts $facts): ?string
    {
        $data = self::hengtongData($facts);

        if ($data === null) {
            return null;
        }

        [$initial, $middle, $final, $rigan] = [$data['initial'], $data['middle'], $data['final'], $data['rigan']];

        if (self::branchGenerates($facts, $initial, $middle)
            && self::branchGenerates($facts, $middle, $final)
            && self::branchGeneratesStem($facts, $final, $rigan)) {
            return '三传'.self::BRANCH_NAMES[$initial].'、'.self::BRANCH_NAMES[$middle].'、'.self::BRANCH_NAMES[$final].'，初生中、中生末、末生日干'.self::STEM_NAMES[$rigan].'。';
        }

        if (self::branchGenerates($facts, $final, $middle)
            && self::branchGenerates($facts, $middle, $initial)
            && self::branchGeneratesStem($facts, $initial, $rigan)) {
            return '三传'.self::BRANCH_NAMES[$initial].'、'.self::BRANCH_NAMES[$middle].'、'.self::BRANCH_NAMES[$final].'，末生中、中生初、初生日干'.self::STEM_NAMES[$rigan].'。';
        }

        return null;
    }

    /** 俱生格成格说明；未命中返回 null。 */
    protected static function juShengDetail(PanFacts $facts): ?string
    {
        $data = self::hengtongData($facts);

        if ($data === null) {
            return null;
        }

        if (! self::branchGeneratesStem($facts, $data['stemUpper'], $data['rigan'])
            || ! self::branchGenerates($facts, $data['branchUpper'], $data['rizhi'])) {
            return null;
        }

        return '干上'.self::BRANCH_NAMES[$data['stemUpper']].'生日干'.self::STEM_NAMES[$data['rigan']].'，支上'.self::BRANCH_NAMES[$data['branchUpper']].'生日支'.self::BRANCH_NAMES[$data['rizhi']].'。';
    }

    /** 互生格成格说明；未命中返回 null。 */
    protected static function huShengDetail(PanFacts $facts): ?string
    {
        $data = self::hengtongData($facts);

        if ($data === null) {
            return null;
        }

        if (! self::branchGenerates($facts, $data['stemUpper'], $data['rizhi'])
            || ! self::branchGeneratesStem($facts, $data['branchUpper'], $data['rigan'])) {
            return null;
        }

        return '干上'.self::BRANCH_NAMES[$data['stemUpper']].'生日支'.self::BRANCH_NAMES[$data['rizhi']].'，支上'.self::BRANCH_NAMES[$data['branchUpper']].'生日干'.self::STEM_NAMES[$data['rigan']].'。';
    }

    /** 俱旺格成格说明；未命中返回 null。 */
    protected static function juWangDetail(PanFacts $facts): ?string
    {
        $data = self::hengtongData($facts);

        if ($data === null) {
            return null;
        }

        if ($data['stemUpper'] !== self::wangBranchOfStem($facts, $data['rigan'])
            || $data['branchUpper'] !== self::wangBranchOfBranch($facts, $data['rizhi'])) {
            return null;
        }

        return '干上'.self::BRANCH_NAMES[$data['stemUpper']].'为日干'.self::STEM_NAMES[$data['rigan']].'之旺神，支上'.self::BRANCH_NAMES[$data['branchUpper']].'为日支'.self::BRANCH_NAMES[$data['rizhi']].'之旺神。';
    }

    /** 互旺格成格说明；未命中返回 null。 */
    protected static function huWangDetail(PanFacts $facts): ?string
    {
        $data = self::hengtongData($facts);

        if ($data === null) {
            return null;
        }

        if ($data['stemUpper'] !== self::wangBranchOfBranch($facts, $data['rizhi'])
            || $data['branchUpper'] !== self::wangBranchOfStem($facts, $data['rigan'])) {
            return null;
        }

        return '干上'.self::BRANCH_NAMES[$data['stemUpper']].'为日支'.self::BRANCH_NAMES[$data['rizhi']].'之旺神，支上'.self::BRANCH_NAMES[$data['branchUpper']].'为日干'.self::STEM_NAMES[$data['rigan']].'之旺神。';
    }

    /** 用神生日（初传生日干）成课说明；未命中返回 null。 */
    protected static function yongshenShengRiDetail(PanFacts $facts): ?string
    {
        $initial = $facts->get('sanchuan0');
        $rigan = $facts->get('rigan');

        if (! is_int($initial) || ! is_int($rigan)) {
            return null;
        }

        if (! self::branchGeneratesStem($facts, $initial, $rigan)) {
            return null;
        }

        return '初传'.self::BRANCH_NAMES[$initial].'生日干'.self::STEM_NAMES[$rigan].'。';
    }
}
