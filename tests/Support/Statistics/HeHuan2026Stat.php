<?php

/**
 * 文件作用：只读复现合欢课在 2026 年的正文最小严格口径命中数。
 *
 * 运行方式：php tests/Support/Statistics/HeHuan2026Stat.php
 * 本脚本不由 Pest 自动加载，避免拖慢日常测试。
 *
 * 出生时间固定为 1959-06-21 12:00:00（男，己亥年生人）。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;

$auspiciousGenerals = [0, 3, 5, 8, 10, 11];

$liuhe = [];
foreach ([[0, 1], [10, 11], [8, 9], [4, 5], [2, 3], [6, 7]] as $p) {
    $liuhe[$p[0].','.$p[1]] = true;
    $liuhe[$p[1].','.$p[0]] = true;
}

$sanhetu = [
    [0, 4, 8],
    [1, 5, 9],
    [2, 6, 10],
    [3, 7, 11],
];

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$birth = '1959-06-21 12:00:00';
$birthPan = $calculator->calculate($birth);
$birthYearIndex = $birthPan->get('nian_index');

$counts = [
    'total' => 0,
    'stem_hex_candidate' => 0,
    'a_strict' => 0,
    'b_liuhe_only' => 0,
    'b_sanhe_only' => 0,
    'b_or' => 0,
];

$firstDay = new DateTimeImmutable('2026-01-01');

for ($day = 0; $day < 365; $day++) {
    foreach ($hours as $hour) {
        $datetime = $firstDay->modify("+{$day} days")->setTime($hour, 0);
        $pan = $calculator->calculate($datetime->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $counts['total']++;

        $dayStem = $pan->get('rigan');
        $dayBranch = $pan->get('rizhi');
        $tianpan = $pan->get('tianpan');
        $initial = $pan->get('sanchuan0');
        $middle = $pan->get('sanchuan1');
        $final = $pan->get('sanchuan2');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($tianpan)
            || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            continue;
        }

        $dayStemLodging = $facts->stemLodgingBranch($dayStem);
        if (! is_int($dayStemLodging) || ! isset($tianpan[$dayStemLodging]) || ! is_int($tianpan[$dayStemLodging])) {
            continue;
        }
        $dayUpper = $tianpan[$dayStemLodging];

        $dayIndex = $facts->sexagenaryDayIndex();
        if ($dayIndex === null) {
            continue;
        }
        $xunIndex = intdiv($dayIndex, 10);
        $xunHeads = [0, 10, 8, 6, 4, 2];
        if (! isset($xunHeads[$xunIndex])) {
            continue;
        }
        $xunHead = $xunHeads[$xunIndex];
        $offset = ($dayUpper - $xunHead + 12) % 12;
        if ($offset > 9) {
            continue;
        }
        $dayUpperStem = $offset;

        $stemHexed = ($dayUpperStem + 5) % 10 === $dayStem;
        if (! $stemHexed) {
            continue;
        }
        $counts['stem_hex_candidate']++;

        $initialHexesUpper = $liuhe[$initial.','.$dayUpper] ?? false;

        $sanchuanSanhe = false;
        foreach ([[$dayBranch, $initial, $middle], [$dayBranch, $initial, $final]] as $branches) {
            sort($branches);
            if (in_array($branches, $sanhetu, true)) {
                $sanchuanSanhe = true;
                break;
            }
        }

        if (! ($initialHexesUpper || $sanchuanSanhe)) {
            continue;
        }
        if ($initialHexesUpper && ! $sanchuanSanhe) {
            $counts['b_liuhe_only']++;
        }
        if (! $initialHexesUpper && $sanchuanSanhe) {
            $counts['b_sanhe_only']++;
        }
        $counts['b_or']++;

        if (! ($initialHexesUpper && $sanchuanSanhe)) {
            continue;
        }
        $counts['a_strict']++;

        $fate = $fateCalculator->calculate($birthYearIndex, $pan->get('nian_index'), 'male');
        $merged = new PanResult([...$pan->toArray(), ...$fate]);
        $factsMerged = PanFacts::from($merged);

        $nmUpper = $tianpan[$fate['nianming']];
        $xnUpper = $tianpan[$fate['xingnian']];
        $nmAuspicious = in_array($factsMerged->generalRidingBranch($nmUpper), $auspiciousGenerals, true);
        $xnAuspicious = in_array($factsMerged->generalRidingBranch($xnUpper), $auspiciousGenerals, true);

        if (! ($nmAuspicious && $xnAuspicious)) {
            // 正文最小严格口径还需要年命俱乘吉将，未满足时移除候选计数。
            $counts['a_strict']--;
        }
    }
}

echo json_encode($counts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
