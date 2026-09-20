<?php

/** 文件作用：按真实年月日时扫描第64课物类课及初传地支、六亲、旺相休囚死分布。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\WuleiRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$rule = new WuleiRule;
$counts = ['denominator' => 0, 'matched' => 0];
$initialBranches = array_fill(0, 12, 0);
$initialLiuqin = array_fill_keys(array_values(PanCalculator::$liuqin), 0);
$initialStates = array_fill_keys(['旺', '相', '休', '囚', '死'], 0);
$start = new DateTimeImmutable("{$year}-01-01 00:00:00", $timezone);
$end = $start->modify('+1 year');

// 分母由 12 个代表时辰 × 该年实际天数动态计算。
// 2031 年平年 365 天 = 4380；2032 年闰年 366 天 = 4392。
$isLeap = (new DateTimeImmutable("{$year}-03-01 00:00:00", $timezone))->format('L') === '1';
$daysInYear = $isLeap ? 366 : 365;
$expectedDenominator = count($hours) * $daysInYear;

for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $counts['denominator']++;
        $match = $rule->match(PanFacts::from($calculator->calculate($datetime)));
        if ($match === null) {
            continue;
        }
        $counts['matched']++;
        $initial = $match->evidence['initial'];
        $initialBranches[$initial['branch']]++;
        $initialLiuqin[$initial['liuqin_name']]++;
        $initialStates[$initial['seasonal_state']]++;
    }
}

if ($counts['denominator'] !== $expectedDenominator) {
    throw new RuntimeException(sprintf(
        '物类课扫描分母应为 %d（%d 年%s，每天 %d 个代表时辰），实际为 %d。',
        $expectedDenominator,
        $year,
        $isLeap ? '闰年 366 天' : '平年 365 天',
        count($hours),
        $counts['denominator'],
    ));
}
if ($counts['matched'] !== $expectedDenominator) {
    throw new RuntimeException(sprintf(
        '物类课全年正常生产盘应全部命中，期望 %d，实际 %d。',
        $expectedDenominator,
        $counts['matched'],
    ));
}
if (array_sum($initialBranches) !== $counts['matched'] || array_sum($initialLiuqin) !== $counts['matched'] || array_sum($initialStates) !== $counts['matched']) {
    throw new RuntimeException('物类课初传分布统计不闭合。');
}

$namedBranches = [];
foreach ($initialBranches as $branch => $count) {
    $namedBranches[PanCalculator::$dizhi[$branch]] = $count;
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai',
    'representative_hours' => $hours,
    'is_leap_year' => $isLeap,
    'days_in_year' => $daysInYear,
    ...$counts,
    'initial_branch_distribution' => $namedBranches,
    'initial_liuqin_distribution' => $initialLiuqin,
    'initial_seasonal_state_distribution' => $initialStates,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
