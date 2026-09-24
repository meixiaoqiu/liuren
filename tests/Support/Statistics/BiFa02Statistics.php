<?php

/**
 * 文件作用：只读统计第二法「首尾相见始终宜」4 条程序 route 在 2031 年
 * 12 个代表时辰的命中规模及各 route 重叠情况。
 *
 * 运行方式：php tests/Support/Statistics/BiFa02Statistics.php
 *
 * 注意：
 *  - 仅做统计与命中模式观测，不得据此修改 matcher 以迎合命中率；
 *  - 输出的全部数据都来自生产 ShouWeiXiangJianRule，不调用任何课经 matcher；
 *  - 程序仅作研究验证，不可作为回归基线。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\BiFa\Rules\ShouWeiXiangJianRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new ShouWeiXiangJianRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$denominator = 0;
$bifa02Total = 0;
$perRoute = [
    'xun_tail_on_stem_xun_head_on_branch' => 0,
    'xun_head_on_stem_xun_tail_on_branch' => 0,
    'tianxin_four_establishments_in_lessons' => 0,
    'huihuan_transmissions_in_lessons' => 0,
];
$overlaps = [
    'A_only' => 0,
    'B_only' => 0,
    'C_only' => 0,
    'D_only' => 0,
    'A_B' => 0,
    'A_D' => 0,
    'B_D' => 0,
    'C_D' => 0,
    'A_C' => 0,
    'B_C' => 0,
    'A_B_D' => 0,
    'A_C_D' => 0,
    'B_C_D' => 0,
    'A_B_C_D' => 0,
];
$examples = [
    'A' => [],
    'B' => [],
    'C' => [],
    'D' => [],
];

for ($date = new DateTimeImmutable('2031-01-01'); $date <= new DateTimeImmutable('2031-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $match = $rule->match($facts);
        $denominator++;

        if ($match === null) {
            continue;
        }

        $bifa02Total++;
        $matched = [];
        foreach ($match->matchedRoutes as $route) {
            $perRoute[$route] = ($perRoute[$route] ?? 0) + 1;
            $matched[] = $route;
        }

        $hasA = in_array('xun_tail_on_stem_xun_head_on_branch', $matched, true);
        $hasB = in_array('xun_head_on_stem_xun_tail_on_branch', $matched, true);
        $hasC = in_array('tianxin_four_establishments_in_lessons', $matched, true);
        $hasD = in_array('huihuan_transmissions_in_lessons', $matched, true);

        // 互斥单路
        if ($hasA && ! $hasB && ! $hasC && ! $hasD) {
            $overlaps['A_only']++;
        } elseif ($hasB && ! $hasA && ! $hasC && ! $hasD) {
            $overlaps['B_only']++;
        } elseif ($hasC && ! $hasA && ! $hasB && ! $hasD) {
            $overlaps['C_only']++;
        } elseif ($hasD && ! $hasA && ! $hasB && ! $hasC) {
            $overlaps['D_only']++;
        }
        // 两两重叠
        if ($hasA && $hasB && ! $hasC && ! $hasD) {
            $overlaps['A_B']++;
        } elseif ($hasA && $hasD && ! $hasB && ! $hasC) {
            $overlaps['A_D']++;
        } elseif ($hasB && $hasD && ! $hasA && ! $hasC) {
            $overlaps['B_D']++;
        } elseif ($hasC && $hasD && ! $hasA && ! $hasB) {
            $overlaps['C_D']++;
        } elseif ($hasA && $hasC && ! $hasB && ! $hasD) {
            $overlaps['A_C']++;
        } elseif ($hasB && $hasC && ! $hasA && ! $hasD) {
            $overlaps['B_C']++;
        }
        // 三组合
        if ($hasA && $hasB && $hasD && ! $hasC) {
            $overlaps['A_B_D']++;
        } elseif ($hasA && $hasC && $hasD && ! $hasB) {
            $overlaps['A_C_D']++;
        } elseif ($hasB && $hasC && $hasD && ! $hasA) {
            $overlaps['B_C_D']++;
        } elseif ($hasA && $hasB && $hasC && $hasD) {
            $overlaps['A_B_C_D']++;
        }

        if ($hasA && count($examples['A']) < 3) {
            $examples['A'][] = [
                'datetime' => $datetime,
                'rigan' => $pan->get('rigan'),
                'rizhi' => $pan->get('rizhi'),
            ];
        }
        if ($hasB && count($examples['B']) < 3) {
            $examples['B'][] = [
                'datetime' => $datetime,
                'rigan' => $pan->get('rigan'),
                'rizhi' => $pan->get('rizhi'),
            ];
        }
        if ($hasC && count($examples['C']) < 3) {
            $examples['C'][] = [
                'datetime' => $datetime,
                'rigan' => $pan->get('rigan'),
                'rizhi' => $pan->get('rizhi'),
            ];
        }
        if ($hasD && count($examples['D']) < 3) {
            $examples['D'][] = [
                'datetime' => $datetime,
                'rigan' => $pan->get('rigan'),
                'rizhi' => $pan->get('rizhi'),
            ];
        }
    }
}

echo "【2031 全年十二代表时辰 · 第二法命中规模】\n";
echo json_encode([
    'denominator' => $denominator,
    'bifa02_total' => $bifa02Total,
    'route_counts' => $perRoute,
    'route_overlap' => $overlaps,
    'examples' => $examples,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
