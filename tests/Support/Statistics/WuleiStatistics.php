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

if ($counts !== ['denominator' => 4380, 'matched' => 4380]) {
    throw new RuntimeException('物类课全年正常生产盘应全部命中且分母应为4380。');
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
    ...$counts,
    'initial_branch_distribution' => $namedBranches,
    'initial_liuqin_distribution' => $initialLiuqin,
    'initial_seasonal_state_distribution' => $initialStates,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
