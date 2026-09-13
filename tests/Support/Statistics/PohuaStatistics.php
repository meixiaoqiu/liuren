<?php

/** 文件作用：只读调用生产排盘、盘面事实与魄化正式规则，分别统计无人物与固定占人的全年命中、路线及课义判断。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PohuaRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new PohuaRule;
$year = (int) ($argv[1] ?? 2026);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$birth = '1986-08-01T00:00';
$gender = 'male';
$routeKeys = ['day', 'branch', 'xingnian', 'initial'];
$judgmentKeys = ['tiger_restrains_day', 'tiger_restrains_branch', 'tiger_restrains_xingnian', 'tiger_seasonal_qiu', 'tiger_seasonal_si', 'upper_restrains_lower', 'lower_restrains_upper', 'tiger_yang', 'tiger_yin'];
$blank = static fn (): array => [
    'total' => 0, 'matched' => 0, 'death_spirit' => 0, 'death_qi' => 0,
    'routes' => array_fill_keys($routeKeys, 0), 'multiple_routes' => 0,
    'only_day' => 0, 'only_branch' => 0, 'only_xingnian' => 0, 'only_initial' => 0,
    'judgments' => array_fill_keys($judgmentKeys, 0),
];
$plain = $blank();
$person = $blank();
$fateCalculator = new FateCalculator;
$birthYearIndex = $calculator->calculate(str_replace('T', ' ', $birth).':00')->get('nian_index');

$record = static function (array &$counts, $match) use ($routeKeys, $judgmentKeys): void {
    $counts['matched']++;
    $counts[$match->evidence['tiger_type']]++;
    $routes = $match->evidence['matched_routes'];
    foreach ($routeKeys as $route) {
        if (in_array($route, $routes, true)) {
            $counts['routes'][$route]++;
        }
    }
    if (count($routes) > 1) {
        $counts['multiple_routes']++;
    } elseif (count($routes) === 1) {
        $counts['only_'.$routes[0]]++;
    }
    $codes = array_column($match->evidence['judgments'], 'code');
    foreach ($judgmentKeys as $code) {
        if (in_array($code, $codes, true)) {
            $counts['judgments'][$code]++;
        }
    }
};

$date = new DateTimeImmutable(sprintf('%04d-01-01', $year));
$end = $date->modify('+1 year');
while ($date < $end) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime);
        $plain['total']++;
        $plainMatch = $rule->match(PanFacts::from($pan));
        if ($plainMatch !== null) {
            $record($plain, $plainMatch);
        }

        $person['total']++;
        $fate = $fateCalculator->calculate($birthYearIndex, $pan->get('nian_index'), $gender);
        $personPan = new PanResult([...$pan->toArray(), 'context' => ['people' => [['role' => 'querent', 'birth_datetime' => $birth, 'gender' => $gender, ...$fate]]]]);
        $personMatch = $rule->match(PanFacts::from($personPan));
        if ($personMatch !== null) {
            $record($person, $personMatch);
        }
    }
    $date = $date->modify('+1 day');
}

echo json_encode([
    'year' => $year, 'timezone' => 'Asia/Shanghai / 北京固定 UTC+8', 'representative_hours' => $hours,
    'without_person' => $plain,
    'fixed_querent' => ['birth_datetime' => $birth, 'gender' => $gender, ...$person, 'xingnian_only_added' => $person['only_xingnian']],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
