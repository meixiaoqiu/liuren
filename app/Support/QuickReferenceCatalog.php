<?php

namespace App\Support;

use App\Domain\Pan\BranchRelations;
use App\Services\PanCalculator;

/**
 * 文件作用：为前台速查页整理干支、五行和常用关系的只读展示数据。
 *
 * 边界：关系值复用生产领域定义，不在本目录内另建六冲、六破、六合或三合规则来源。
 */
final class QuickReferenceCatalog
{
    public static function categories(): array
    {
        return [
            ['name' => '四孟', 'branches' => ['寅', '巳', '申', '亥']],
            ['name' => '四仲', 'branches' => ['子', '卯', '午', '酉']],
            ['name' => '四季', 'branches' => ['辰', '未', '戌', '丑']],
        ];
    }

    public static function stems(): array
    {
        return array_map(fn (string $stem, int $index): array => [
            'name' => $stem,
            'polarity' => PanCalculator::$yinyangTian[$index] === 1 ? '阳' : '阴',
            'element' => PanCalculator::$wuxing[PanCalculator::$wuxingTian[$index]],
        ], array_slice(PanCalculator::$tiangan, 0, 10), array_keys(array_slice(PanCalculator::$tiangan, 0, 10)));
    }

    public static function branches(): array
    {
        return array_map(fn (string $branch, int $index): array => [
            'name' => $branch,
            'polarity' => PanCalculator::$yinyangDi[$index] === 1 ? '阳' : '阴',
            'element' => PanCalculator::$wuxing[PanCalculator::$wuxingDi[$index]],
        ], PanCalculator::$dizhi, array_keys(PanCalculator::$dizhi));
    }

    public static function generatingCycle(): array
    {
        return ['木', '火', '土', '金', '水', '木'];
    }

    public static function overcomingCycle(): array
    {
        return ['木', '土', '水', '火', '金', '木'];
    }

    public static function stemCombinations(): array
    {
        return ['甲己', '乙庚', '丙辛', '丁壬', '戊癸'];
    }

    public static function stemLodgings(): array
    {
        return array_map(fn (string $stem, int $index): array => [
            'stem' => $stem,
            'branch' => PanCalculator::$dizhi[PanCalculator::$jigong[$index]],
        ], array_slice(PanCalculator::$tiangan, 0, 10), array_keys(PanCalculator::$jigong));
    }

    public static function liuhe(): array
    {
        return self::branchGroups(BranchRelations::LIUHE_PAIRS);
    }

    public static function clashes(): array
    {
        $pairs = [];
        foreach (BranchRelations::CHONG as $from => $to) {
            if ($from < $to) {
                $pairs[] = [$from, $to];
            }
        }

        return self::branchGroups($pairs);
    }

    public static function harms(): array
    {
        return ['子未', '丑午', '寅巳', '卯辰', '申亥', '酉戌'];
    }

    public static function breaks(): array
    {
        $pairs = [];
        foreach (BranchRelations::PO as $from => $to) {
            if ($from < $to) {
                $pairs[] = [$from, $to];
            }
        }

        return self::branchGroups($pairs);
    }

    public static function sanhe(): array
    {
        $elements = ['水局', '金局', '火局', '木局'];
        $customOrder = [[8, 0, 4], [11, 3, 7], [2, 6, 10], [5, 9, 1]];

        return array_map(fn (array $branches): array => [
            'branches' => self::branchGroup($branches),
            'element' => $elements[array_search(self::sorted($branches), BranchRelations::SANHE_TRIPLES, true)],
        ], $customOrder);
    }

    public static function punishments(): array
    {
        $labels = [
            '子卯：无礼之刑' => [0, 3],
            '寅巳申：无恩之刑' => [2, 5, 8],
            '丑戌未：恃势之刑' => [1, 10, 7],
            '辰午酉亥：自刑' => [4, 6, 9, 11],
        ];

        return array_map(function (array $sources, string $label): array {
            return [
                'label' => $label,
                'relations' => array_map(fn (int $source): string => PanCalculator::$dizhi[$source].' → '.PanCalculator::$dizhi[PanCalculator::$xing[$source]], $sources),
            ];
        }, $labels, array_keys($labels));
    }

    public static function prosperity(): array
    {
        return [
            ['season' => '春', '旺' => '木', '相' => '火', '休' => '水', '囚' => '金', '死' => '土'],
            ['season' => '夏', '旺' => '火', '相' => '土', '休' => '木', '囚' => '水', '死' => '金'],
            ['season' => '秋', '旺' => '金', '相' => '水', '休' => '土', '囚' => '火', '死' => '木'],
            ['season' => '冬', '旺' => '水', '相' => '木', '休' => '金', '囚' => '土', '死' => '火'],
            ['season' => '四季', 'note' => '辰、未、戌、丑土旺阶段', '旺' => '土', '相' => '金', '休' => '火', '囚' => '木', '死' => '水'],
        ];
    }

    public static function voids(): array
    {
        return [
            ['旬' => '甲子旬', '空' => '戌亥空'], ['旬' => '甲戌旬', '空' => '申酉空'],
            ['旬' => '甲申旬', '空' => '午未空'], ['旬' => '甲午旬', '空' => '辰巳空'],
            ['旬' => '甲辰旬', '空' => '寅卯空'], ['旬' => '甲寅旬', '空' => '子丑空'],
        ];
    }

    public static function monthGenerals(): array
    {
        return array_map(function (string $definition): array {
            [$branch, $name, $range] = explode('/', $definition);

            return compact('branch', 'name', 'range');
        }, PanCalculator::$yuejiang);
    }

    public static function heavenlyGenerals(): array
    {
        return PanCalculator::$tianjiang;
    }

    private static function branchGroups(array $groups): array
    {
        return array_map(self::branchGroup(...), $groups);
    }

    private static function branchGroup(array $indexes): string
    {
        return implode('', array_map(fn (int $index): string => PanCalculator::$dizhi[$index], $indexes));
    }

    private static function sorted(array $indexes): array
    {
        sort($indexes);

        return $indexes;
    }
}
