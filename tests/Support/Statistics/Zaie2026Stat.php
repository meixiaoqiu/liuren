<?php

/** 文件作用：固定占人出生资料，逐层统计 2026 年 4380 个代表时刻的灾厄课九种神煞分别发用、命中与重叠情况。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';
require dirname(__DIR__, 3).'/bootstrap/app.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZaieRule;
use App\Domain\Pan\Shensha\ZaieShensha;
use App\Services\PanCalculator;

$year = (int) ($argv[1] ?? 2026);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$rule = new ZaieRule;

$counts = [
    'total' => 0,
    'matched' => 0,
    'only_one' => 0,
    'two_overlap' => 0,
    'three_overlap' => 0,
    'four_or_more_overlap' => 0,
    'max_overlap' => 0,
];
$perShensha = array_fill_keys(ZaieShensha::orderedKeys(), 0);

for ($date = new DateTimeImmutable("{$year}-01-01"), $end = $date->modify('+1 year'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $counts['total']++;

        $match = $rule->match($facts);
        if ($match === null) {
            continue;
        }

        $counts['matched']++;
        $hits = $match->evidence['matched_keys'] ?? [];
        $overlap = count($hits);
        if ($overlap === 1) {
            $counts['only_one']++;
        } elseif ($overlap === 2) {
            $counts['two_overlap']++;
        } elseif ($overlap === 3) {
            $counts['three_overlap']++;
        } else {
            $counts['four_or_more_overlap']++;
        }
        $counts['max_overlap'] = max($counts['max_overlap'], $overlap);

        foreach ($hits as $key) {
            if (isset($perShensha[$key])) {
                $perShensha[$key]++;
            }
        }
    }
}

$expectedTotal = 365 * 12;
if ($counts['total'] !== $expectedTotal) {
    throw new RuntimeException("代表时刻总数 {$counts['total']} 不等于 {$expectedTotal}。");
}

$sumOverlaps = $counts['only_one'] + $counts['two_overlap'] + $counts['three_overlap'] + $counts['four_or_more_overlap'];
if ($sumOverlaps !== $counts['matched']) {
    throw new RuntimeException("按重叠层级求和 {$sumOverlaps} 与 matched {$counts['matched']} 不一致。");
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai / 北京固定 UTC+8',
    'hours' => $hours,
    'total' => $counts['total'],
    'matched' => $counts['matched'],
    'hit_rate_percent' => round($counts['matched'] * 100 / $counts['total'], 2),
    'overlap_distribution' => [
        'only_one' => $counts['only_one'],
        'two_overlap' => $counts['two_overlap'],
        'three_overlap' => $counts['three_overlap'],
        'four_or_more_overlap' => $counts['four_or_more_overlap'],
        'max_overlap' => $counts['max_overlap'],
    ],
    'per_shensha' => $perShensha,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
