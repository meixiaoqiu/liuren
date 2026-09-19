<?php

/** 文件作用：按真实年月日时扫描第63课杂状课及纯、杂、生杂、死杂、普通杂分布。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZazhuangRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$rule = new ZazhuangRule;
$counts = ['denominator' => 0, 'matched' => 0, 'pure' => 0, 'mixed' => 0, 'birth_mixed' => 0, 'death_mixed' => 0, 'ordinary_mixed' => 0];
$initialDistribution = array_fill(0, 12, 0);
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
        $purity = $match->evidence['purity'];
        $counts[$purity]++;
        if ($purity === 'mixed') {
            $counts[$match->evidence['mixed_subtype']]++;
        }
        $initialDistribution[$match->evidence['initial']]++;
    }
}

if ($counts['matched'] !== $counts['denominator']) {
    throw new RuntimeException('杂状课正常生产盘未全部命中。');
}
if ($counts['matched'] !== $counts['pure'] + $counts['mixed']) {
    throw new RuntimeException('纯杂分类统计不闭合。');
}
if ($counts['mixed'] !== $counts['birth_mixed'] + $counts['death_mixed'] + $counts['ordinary_mixed']) {
    throw new RuntimeException('生杂、死杂、普通杂统计不闭合。');
}
if (in_array(0, $initialDistribution, true)) {
    throw new RuntimeException('存在全年不可达的初传地支分支。');
}

$namedDistribution = [];
foreach ($initialDistribution as $branch => $count) {
    $namedDistribution[PanCalculator::$dizhi[$branch]] = $count;
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai',
    'representative_hours' => $hours,
    ...$counts,
    'initial_distribution' => $namedDistribution,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
