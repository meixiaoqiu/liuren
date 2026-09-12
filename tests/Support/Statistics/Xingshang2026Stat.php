<?php

/** 文件作用：通过生产 PanCalculator、FateCalculator 与 XingshangRule 统计刑伤课。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\XingshangRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$rule = new XingshangRule;
$branches = PanCalculator::$dizhi;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$birthYearIndexes = [];
$withQuerent = static function (PanResult $pan, string $birth, string $gender, ?string $current = null) use ($calculator, $fateCalculator, &$birthYearIndexes): PanResult {
    $birthYearIndexes[$birth] ??= $calculator->calculate($birth)->get('nian_index');
    $currentYearIndex = $current === null ? $pan->get('nian_index') : $calculator->calculate($current)->get('nian_index');
    $fate = $fateCalculator->calculate($birthYearIndexes[$birth], $currentYearIndex, $gender);
    $data = $pan->toArray();
    $data['context'] = ['people' => [['role' => 'querent', 'birth_datetime' => $birth, 'gender' => $gender, ...$fate]]];

    return new PanResult($data);
};

$panCache = [];
$summarize = static function (array $datetimes, ?array $profile = null) use ($calculator, $rule, $withQuerent, $branches, &$panCache): array {
    $out = ['total' => count($datetimes), 'matches' => 0, 'route_counts' => [], 'route_combinations' => [], 'initials' => [], 'punished_targets' => [], 'methods' => [], 'months' => array_fill(1, 12, 0), 'samples' => []];
    foreach ($datetimes as $datetime) {
        $panCache[$datetime] ??= $calculator->calculate($datetime);
        $pan = $panCache[$datetime];
        if ($profile !== null) {
            $pan = $withQuerent($pan, $profile['birth'], $profile['gender'], $profile['current'] ?? null);
        }
        $match = $rule->match(PanFacts::from($pan));
        if ($match === null) {
            continue;
        }
        $out['matches']++;
        $routes = $match->evidence['matched_routes'];
        foreach ($routes as $route) {
            $out['route_counts'][$route] = ($out['route_counts'][$route] ?? 0) + 1;
        }
        $combo = implode('+', $routes);
        $out['route_combinations'][$combo] = ($out['route_combinations'][$combo] ?? 0) + 1;
        $initial = $branches[$match->evidence['initial']];
        $punished = $branches[$match->evidence['punished_branch']];
        $out['initials'][$initial] = ($out['initials'][$initial] ?? 0) + 1;
        $out['punished_targets'][$punished] = ($out['punished_targets'][$punished] ?? 0) + 1;
        $method = PanFacts::from($pan)->chuchuanMethod() ?? '未记录';
        $out['methods'][$method] = ($out['methods'][$method] ?? 0) + 1;
        $month = (int) substr($datetime, 5, 2);
        $out['months'][$month]++;
        if (count($out['samples']) < 8) {
            $out['samples'][] = ['datetime' => $datetime, 'day' => PanCalculator::$tiangan[$pan->get('rigan')].$branches[$pan->get('rizhi')], 'initial' => $initial, 'punished' => $punished, 'routes' => $routes, 'method' => $method];
        }
    }
    foreach (['route_counts', 'route_combinations', 'initials', 'punished_targets', 'methods'] as $key) {
        ksort($out[$key]);
    }
    $out['percent'] = round($out['matches'] * 100 / max(1, $out['total']), 2);

    return $out;
};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$core = array_map(fn (array $case): string => $case['input'], $fixture['cases']);
$year = [];
for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $year[] = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
    }
}
$profile = ['birth' => '1986-08-01 00:00:00', 'gender' => 'male'];
$coreProfile = [...$profile, 'current' => '2026-06-01 12:00:00'];

$currentYearIndex = $calculator->calculate('2026-06-01 12:00:00')->get('nian_index');
$referenceBirthIndex = $calculator->calculate($profile['birth'])->get('nian_index');
$referenceFate = $fateCalculator->calculate($referenceBirthIndex, $currentYearIndex, $profile['gender']);
$sensitivity = [];
foreach (['male', 'female'] as $gender) {
    $contexts = [];
    for ($birthYearIndex = 0; $birthYearIndex < 60; $birthYearIndex++) {
        $fate = $fateCalculator->calculate($birthYearIndex, $currentYearIndex, $gender);
        $contexts[$fate['nianming'].':'.$fate['xingnian']] = $fate;
    }
    $counts = [];
    foreach ($contexts as $fate) {
        $matches = 0;
        foreach ($core as $datetime) {
            $panCache[$datetime] ??= $calculator->calculate($datetime);
            $data = $panCache[$datetime]->toArray();
            $data['context'] = ['people' => [['role' => 'querent', 'gender' => $gender, ...$fate]]];
            $matches += (int) ($rule->match(PanFacts::from(new PanResult($data))) !== null);
        }
        $counts[] = $matches;
    }
    $sensitivity[$gender] = [
        'reachable_contexts' => count($contexts),
        'min' => min($counts),
        'max' => max($counts),
        'average' => round(array_sum($counts) / count($counts), 2),
        'average_percent' => round(array_sum($counts) * 100 / count($counts) / 720, 2),
    ];
}

echo json_encode([
    'reference_person_after_2026_lichun' => [...$profile, ...$referenceFate],
    '720_without_person' => $summarize($core),
    '720_reference_person' => $summarize($core, $coreProfile),
    '2026_without_person' => $summarize($year),
    '2026_reference_person' => $summarize($year, $profile),
    '2026_reachable_context_sensitivity_on_720' => $sensitivity,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
