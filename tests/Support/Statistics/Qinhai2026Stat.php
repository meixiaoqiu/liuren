<?php

/** 文件作用：用生产 PanCalculator 复算侵害课在 720 核心盘与 2026 全年十二时辰的统计。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\QinhaiRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new QinhaiRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$branches = PanCalculator::$dizhi;

$summarize = static function (array $datetimes) use ($calculator, $rule, $branches): array {
    $result = [
        'total' => count($datetimes), 'matches' => 0,
        'routes' => ['stem_only' => 0, 'branch_only' => 0, 'both' => 0],
        'initial_roles' => ['upper' => 0, 'lower' => 0],
        'pairs' => [], 'methods' => [], 'months' => array_fill(1, 12, 0),
        'plate_offsets' => [], 'fuyin' => 0, 'fanyin' => 0, 'samples' => [],
    ];
    foreach ($datetimes as $datetime) {
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $match = $rule->match($facts);
        if ($match === null) {
            continue;
        }
        $result['matches']++;
        $routes = $match->evidence['routes'];
        $routeKey = count($routes) === 2 ? 'both' : $routes[0]['route'].'_only';
        $result['routes'][$routeKey]++;
        foreach (array_values(array_unique(array_column($routes, 'initial_role'))) as $role) {
            $result['initial_roles'][$role]++;
        }
        $pairs = [];
        foreach ($routes as $route) {
            $pairs[] = $branches[$route['hai_pair'][0]].$branches[$route['hai_pair'][1]];
        }
        foreach (array_values(array_unique($pairs)) as $pair) {
            $result['pairs'][$pair] = ($result['pairs'][$pair] ?? 0) + 1;
        }
        $method = $facts->chuchuanMethod() ?? '未记录';
        $result['methods'][$method] = ($result['methods'][$method] ?? 0) + 1;
        $month = (int) substr($datetime, 5, 2);
        $result['months'][$month]++;
        $tianpan = $pan->get('tianpan');
        $offset = is_array($tianpan) && isset($tianpan[0]) ? ($tianpan[0] + 12) % 12 : -1;
        $result['plate_offsets'][$offset] = ($result['plate_offsets'][$offset] ?? 0) + 1;
        $result['fuyin'] += (int) ($offset === 0);
        $result['fanyin'] += (int) ($offset === 6);
        if (count($result['samples']) < 8) {
            $result['samples'][] = [
                'datetime' => $datetime, 'day' => PanCalculator::$tiangan[$pan->get('rigan')].$branches[$pan->get('rizhi')],
                'routes' => $routes, 'initial' => $branches[$pan->get('sanchuan0')], 'method' => $method,
            ];
        }
    }
    ksort($result['pairs']);
    ksort($result['methods']);
    ksort($result['plate_offsets']);

    return $result;
};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$core = array_map(fn (array $case): string => $case['input'], $fixture['cases']);
$year = [];
for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $year[] = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
    }
}

echo json_encode(['720' => $summarize($core), '2026' => $summarize($year)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
