<?php

/**
 * 文件作用：只读复现斩关课在 720 核心盘与 2026 年十二时辰下的命中数。
 *
 * 运行方式：php tests/Support/Statistics/ZhanGuan2026Stat.php
 * 本脚本不由 Pest 自动加载，避免拖慢日常测试。
 *
 * 直接调用正式 `ZhanGuanRule`（OR 主体口径），不复用探查式临时分支。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZhanGuanRule;
use App\Services\PanCalculator;
use com\tyme\solar\SolarDay;

$calculator = new PanCalculator;
$rule = new ZhanGuanRule;

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

/** 720 fixture：12 天地盘关系 × 60 日干支 */
$jigong = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];
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

$counts = [
    'total' => 0,
    'hit' => 0,
    'tian_gang' => 0,
    'tian_kui' => 0,
    'on_day_stem' => 0,
    'on_day_branch' => 0,
];

foreach ($jiaziKeys as $dayIndex => $date) {
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
        if (! empty($ev['is_tian_gang'])) {
            $counts['tian_gang']++;
        }
        if (! empty($ev['is_tian_kui'])) {
            $counts['tian_kui']++;
        }
        if (! empty($ev['on_day_stem'])) {
            $counts['on_day_stem']++;
        }
        if (! empty($ev['on_day_branch'])) {
            $counts['on_day_branch']++;
        }
    }
}

echo "【斩关课 720 核心盘统计】\n";
echo "总数: {$counts['total']}\n";
echo "命中: {$counts['hit']}\n";
echo '占比: '.sprintf('%.2f%%', $counts['hit'] / $counts['total'] * 100)."\n";
echo "天罡发用: {$counts['tian_gang']}\n";
echo "天魁发用: {$counts['tian_kui']}\n";
echo "via 日干: {$counts['on_day_stem']}\n";
echo "via 日支: {$counts['on_day_branch']}\n";

echo "\n【斩关课 2026 全年十二时辰统计】\n";

$counts = [
    'total' => 0,
    'hit' => 0,
    'tian_gang' => 0,
    'tian_kui' => 0,
    'on_day_stem' => 0,
    'on_day_branch' => 0,
    'hit_days' => 0,
];

$firstDay2026 = new DateTimeImmutable('2026-01-01');
$end2026 = new DateTimeImmutable('2026-12-31');
$cur = $firstDay2026;
$lastDayHit = false;
while ($cur <= $end2026) {
    $dayHit = false;
    foreach ($hours as $hour) {
        $dt = $cur->setTime($hour, 0);
        $pan = $calculator->calculate($dt->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $match = $rule->match($facts);
        $counts['total']++;
        if ($match === null) {
            continue;
        }
        $counts['hit']++;
        $dayHit = true;
        $ev = $match->evidence;
        if (! empty($ev['is_tian_gang'])) {
            $counts['tian_gang']++;
        }
        if (! empty($ev['is_tian_kui'])) {
            $counts['tian_kui']++;
        }
        if (! empty($ev['on_day_stem'])) {
            $counts['on_day_stem']++;
        }
        if (! empty($ev['on_day_branch'])) {
            $counts['on_day_branch']++;
        }
    }
    if ($dayHit) {
        $counts['hit_days']++;
    }
    $cur = $cur->modify('+1 day');
}

echo "总数: {$counts['total']}\n";
echo "命中: {$counts['hit']}\n";
echo '占比: '.sprintf('%.2f%%', $counts['hit'] / $counts['total'] * 100)."\n";
echo "天罡发用: {$counts['tian_gang']}\n";
echo "天魁发用: {$counts['tian_kui']}\n";
echo "via 日干: {$counts['on_day_stem']}\n";
echo "via 日支: {$counts['on_day_branch']}\n";
echo "命中天数: {$counts['hit_days']} / 365\n";

echo "\n【斩关课 1900-2100 正文甲寅课例搜索】\n";

$jiaYinDays = 0;
$exactCases = 0;
$firstExactCase = null;
$cur = new DateTimeImmutable('1900-01-01');
$end = new DateTimeImmutable('2100-12-31');

while ($cur <= $end) {
    $solar = SolarDay::fromYmd(
        (int) $cur->format('Y'),
        (int) $cur->format('m'),
        (int) $cur->format('d')
    );
    $sixty = $solar->getLunarDay()->getSixtyCycle();

    if ($sixty->getHeavenStem()->getIndex() === 0
        && $sixty->getEarthBranch()->getIndex() === 2) {
        $jiaYinDays++;
        $datetime = $cur->setTime(21, 0)->format('Y-m-d H:i:s');
        $pan = $calculator->calculate($datetime);
        $tianpan = $pan->get('tianpan');

        if ($pan->get('yuejiang') === 7
            && $pan->get('sanchuan0') === 10
            && $pan->get('sanchuan1') === 6
            && $pan->get('sanchuan2') === 2
            && is_array($tianpan)
            && ($tianpan[2] ?? null) === 10) {
            $exactCases++;
            $firstExactCase ??= $datetime;
        }
    }

    $cur = $cur->modify('+1 day');
}

echo "甲寅日总数: {$jiaYinDays}\n";
echo "甲寅日 + 亥时 + 未将 + 戌加寅发用: {$exactCases}\n";
echo "最早命中: {$firstExactCase}\n";
