<?php

/**
 * 第五法「六阳数足须公用」2031 年生产排盘审计统计。
 * 固定测试占人出生时间为 1986-08-01 00:00:00、男；统计不作回归基线，
 * 不得根据命中率反向修改 matcher。
 *
 * 运行：php tests/Support/Statistics/BiFa05Statistics.php [输出路径]
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\LiuYangShuZuRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$rule = new LiuYangShuZuRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$birthDatetime = '1986-08-01 00:00:00';
$birthYearIndex = $calculator->calculate($birthDatetime)->get('nian_index');
$counts = [
    'denominator' => 0, 'six_yang' => 0, 'five_yang_candidates' => 0,
    'five_yang_filled_by_person' => 0, 'pending' => 0, '悖戾格' => 0,
    '自夜传昼' => 0, 'multi_judgment' => 0,
];
$first = ['six_yang' => null, 'five_yang_filled_by_person' => null, '悖戾格' => null, '自夜传昼' => null, 'multi_judgment' => null];

for ($date = new DateTimeImmutable('2031-01-01'); $date <= new DateTimeImmutable('2031-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $data = $calculator->calculate($datetime)->toArray();
        $fate = $fateCalculator->calculate($birthYearIndex, $data['nian_index'], 'male');
        $data['context'] = ['people' => [[
            'role' => 'querent', 'birth_datetime' => $birthDatetime, 'gender' => 'male', ...$fate,
        ]]];
        $match = $rule->match(PanFacts::from(new PanResult($data)));
        $counts['denominator']++;
        if ($match === null) {
            continue;
        }
        if (($match->evidence['yang_count'] ?? null) === 5 && ($match->evidence['initial_from_lesson'] ?? false)) {
            $counts['five_yang_candidates']++;
        }
        foreach (['six_yang', 'five_yang_filled_by_person'] as $route) {
            if (in_array($route, $match->matchedRoutes, true)) {
                $counts[$route]++;
                $first[$route] ??= $datetime;
            }
        }
        if ($match->pendingRoutes !== []) {
            $counts['pending']++;
        }
        $labels = array_column($match->matchedJudgments, 'label');
        foreach (['悖戾格', '自夜传昼'] as $label) {
            if (in_array($label, $labels, true)) {
                $counts[$label]++;
                $first[$label] ??= $datetime;
            }
        }
        if (count($labels) > 1) {
            $counts['multi_judgment']++;
            $first['multi_judgment'] ??= $datetime;
        }
    }
}

$result = ['fixed_person' => ['birth_datetime' => $birthDatetime, 'gender' => 'male'], ...$counts, 'first_datetime' => $first];
$output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
if (isset($argv[1])) {
    file_put_contents($argv[1], $output);
    echo "written to {$argv[1]}\n";
} else {
    echo $output;
}
