<?php

/**
 * 文件作用：只读复现和美课在 720 核心盘与 2026 年十二时辰下的命中数。
 *
 * 运行方式：php tests/Support/Statistics/HeMei2026Stat.php
 * 本脚本不由 Pest 自动加载，避免拖慢日常测试。
 *
 * 直接调用正式 `HeMeiRule`（最小闭合正文口径），不复用探查式临时分支。
 *
 * 本脚本直接计算两组统计：
 *   (1) 720 fixture 段：60 日干支 × 12 时辰 = 720 盘（与 fixture 1:1 相同输入空间）。
 *   (2) 2026 全年段：365 个真实日期 × 12 时辰 = 4380 盘，逐盘调用正式规则。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HeMeiRule;
use App\Services\PanCalculator;
use com\tyme\solar\SolarDay;

$calculator = new PanCalculator;
$rule = new HeMeiRule;

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

/** 720 fixture 段：60 日干支 × 12 时辰 = 720 盘，用 2026 年实际日期作为 datetime 载体 */
$jiazi = PanCalculator::$jiazi2Ganzhi;

$firstDay = new DateTimeImmutable('2000-01-01');
$endDate = new DateTimeImmutable('2060-12-31T23:59:59');
$jiaziKeys = [];
$cur = $firstDay;
while ($cur <= $endDate) {
    $solar = SolarDay::fromYmd(
        (int) $cur->format('Y'),
        (int) $cur->format('m'),
        (int) $cur->format('d')
    );
    $sixty = $solar->getLunarDay()->getSixtyCycle();
    $gan = $sixty->getHeavenStem()->getIndex();
    $zhi = $sixty->getEarthBranch()->getIndex();
    foreach ($jiazi as $idx => [$g, $z]) {
        if ($g === $gan && $z === $zhi) {
            $jiaziKeys[$idx] = $cur->format('Y-m-d');
            break;
        }
    }
    $cur = $cur->modify('+1 day');
    if (count($jiaziKeys) >= 60) {
        break;
    }
}
ksort($jiaziKeys);

function runSegment(
    string $label,
    array $items,
    PanCalculator $calculator,
    HeMeiRule $rule,
    array $hours
): void {
    $counts = ['total' => 0, 'hit' => 0];
    $pathDetails = [];

    foreach ($items as $date) {
        foreach ($hours as $hour) {
            $datetime = sprintf('%s %02d:00:00', $date, $hour);
            $pan = $calculator->calculate($datetime);
            $facts = PanFacts::from($pan);
            $match = $rule->match($facts);
            $counts['total']++;
            if ($match === null) {
                continue;
            }
            $counts['hit']++;
            $ev = $match->evidence;
            $paths = [];
            if (! empty($ev['cross_liuhe'])) {
                $paths[] = 'cross_liuhe';
            }
            if (! empty($ev['same_liuhe'])) {
                $paths[] = 'same_liuhe';
            }
            if (! empty($ev['upper_sanhe_transmission'])) {
                $paths[] = 'upper_sanhe_transmission';
            }
            sort($paths);
            $key = implode('+', $paths);
            $pathDetails[$key] = ($pathDetails[$key] ?? 0) + 1;
        }
    }

    ksort($pathDetails);
    echo "【{$label}】\n";
    echo "总数: {$counts['total']}\n";
    echo "命中: {$counts['hit']}\n";
    echo '占比: '.sprintf('%.2f%%', $counts['hit'] / $counts['total'] * 100)."\n";
    echo "分路（可重叠）:\n";
    foreach ($pathDetails as $key => $n) {
        echo "  $key : $n\n";
    }
    echo "\n";
}

// (1) 720 fixture 段
runSegment(
    '和美课 720 核心盘（60 日干支 × 12 时辰）',
    array_values($jiaziKeys),
    $calculator,
    $rule,
    $hours
);

// (2) 2026 全年实跑（365 × 12 = 4380 盘真实日期）
$yearItems = [];
$cur = new DateTimeImmutable('2026-01-01');
$endYear = new DateTimeImmutable('2026-12-31');
while ($cur <= $endYear) {
    $yearItems[] = $cur->format('Y-m-d');
    $cur = $cur->modify('+1 day');
}
runSegment(
    '和美课 2026 全年（365 × 12 = 4380 盘）',
    $yearItems,
    $calculator,
    $rule,
    $hours
);
