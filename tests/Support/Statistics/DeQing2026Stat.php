<?php

/**
 * 文件作用：只读复现德庆课在 2026 年的候选、中等与严格口径统计。
 *
 * 运行方式：php tests/Support/Statistics/DeQing2026Stat.php
 * 本脚本不由 Pest 自动加载，避免拖慢日常测试。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;

$stemVirtues = [2, 8, 5, 11, 5, 2, 8, 5, 11, 5];
$branchVirtues = [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4];
$heavenlyVirtues = [5, 8, 7, 8, 11, 10, 11, 2, 1, 2, 5, 4];
$monthlyVirtues = [11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5, 2];
$auspiciousGenerals = [0, 3, 5, 8, 10, 11];
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$birthYearIndex = $calculator->calculate('1986-08-18 12:17:00')->get('nian_index');
$counts = [
    'total' => 0,
    'virtue_initial_candidate' => 0,
    'medium' => 0,
    'strict' => 0,
    'strict_nianming_only' => 0,
    'strict_xingnian_only' => 0,
    'strict_both' => 0,
];

$firstDay = new DateTimeImmutable('2026-01-01');

for ($day = 0; $day < 365; $day++) {
    foreach ($hours as $hour) {
        $datetime = $firstDay->modify("+{$day} days")->setTime($hour, 0);
        $pan = $calculator->calculate($datetime->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $counts['total']++;

        $initial = $pan->get('sanchuan0');
        $dayStem = $pan->get('rigan');
        $dayBranch = $pan->get('rizhi');
        $monthBranch = $pan->get('yuezhi');
        $tianpan = $pan->get('tianpan');

        if (! is_int($initial) || ! is_int($dayStem) || ! is_int($dayBranch)
            || ! is_int($monthBranch) || ! is_array($tianpan)) {
            continue;
        }

        $virtues = [
            $stemVirtues[$dayStem],
            $branchVirtues[$dayBranch],
            $heavenlyVirtues[$monthBranch],
            $monthlyVirtues[$monthBranch],
        ];

        if (! in_array($initial, $virtues, true)) {
            continue;
        }

        $counts['virtue_initial_candidate']++;
        $fate = $fateCalculator->calculate($birthYearIndex, $pan->get('nian_index'), 'male');
        $nianmingUpper = $tianpan[$fate['nianming']];
        $xingnianUpper = $tianpan[$fate['xingnian']];
        $nianmingGood = in_array($facts->generalRidingBranch($nianmingUpper), $auspiciousGenerals, true);
        $xingnianGood = in_array($facts->generalRidingBranch($xingnianUpper), $auspiciousGenerals, true);

        if ($nianmingGood || $xingnianGood) {
            $counts['medium']++;
        }

        $initialGood = in_array($facts->generalRidingBranch($initial), $auspiciousGenerals, true);
        $viaNianming = $initialGood && $nianmingUpper === $initial;
        $viaXingnian = $initialGood && $xingnianUpper === $initial;

        if (! $viaNianming && ! $viaXingnian) {
            continue;
        }

        $counts['strict']++;
        $counts[$viaNianming && $viaXingnian
            ? 'strict_both'
            : ($viaNianming ? 'strict_nianming_only' : 'strict_xingnian_only')]++;
    }
}

echo json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
