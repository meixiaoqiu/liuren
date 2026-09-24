<?php

/**
 * 文件作用：只读统计第四法「催官使者赴官期」4 条程序 route 在 2031 年
 * 12 个代表时辰的命中规模、各 route 重叠情况，以及催官使者空亡 /
 * 返本煞 / 返吟附加的出现次数。
 *
 * 运行方式：
 *   php tests/Support/Statistics/BiFa04Statistics.php
 *   php tests/Support/Statistics/BiFa04Statistics.php storage/app/private/dev/bifa04-stats.json
 *   php tests/Support/Statistics/BiFa04Statistics.php storage/app/private/dev/bifa04-stats.json 2031-01-01 2031-12-31 with_test_person
 *
 * 注意：
 *  - 仅做统计与命中模式观测，不得据此修改 matcher 以迎合命中率；
 *  - 输出的全部数据都来自生产 CuiGuanShiZheRule，不调用任何课经 matcher；
 *  - 程序仅作研究验证，不可作为回归基线。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$birthDatetime = '1985-08-01 00:00:00';
$birthYearIndex = $calculator->calculate($birthDatetime)->get('nian_index');
$rule = new CuiGuanShiZheRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$routes = ['cui_guan_messenger', 'cui_guan_talisman', 'patron_parent_line', 'patron_noble_as_growth'];

$start = new DateTimeImmutable($argv[2] ?? '2031-01-01');
$end = new DateTimeImmutable($argv[3] ?? '2031-12-31');
$selectedMode = $argv[4] ?? null;

$modes = [
    'without_people' => null,
    'with_test_person' => true,
];
if (is_string($selectedMode) && array_key_exists($selectedMode, $modes)) {
    $modes = [$selectedMode => $modes[$selectedMode]];
}

$results = [];

foreach ($modes as $mode => $usePerson) {
    $counts = array_fill_keys($routes, 0);
    $examples = array_fill_keys($routes, []);
    $overlap = [];
    $total = 0;
    $denominator = 0;
    $messengerVoid = 0;
    $fanbenHits = 0;
    $fanyinAttached = 0;
    $pendingTotal = 0;

    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        foreach ($hours as $hour) {
            $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $data = $calculator->calculate($datetime)->toArray();
            $fate = $usePerson === null ? null : $fateCalculator->calculate($birthYearIndex, $data['nian_index'], 'male');
            $data['context'] = ['people' => $fate === null ? [] : [[
                'role' => 'querent',
                'birth_datetime' => $birthDatetime,
                'gender' => 'male',
                ...$fate,
            ]]];
            $match = $rule->match(PanFacts::from(new PanResult($data)));
            $denominator++;
            if ($match === null) {
                continue;
            }
            if ($match->matchedRoutes === [] && $match->pendingRoutes === []) {
                continue;
            }
            $total++;
            if ($match->pendingRoutes !== []) {
                $pendingTotal++;
            }
            $key = implode('+', $match->matchedRoutes);
            if ($key !== '') {
                $overlap[$key] = ($overlap[$key] ?? 0) + 1;
            }
            foreach ($match->matchedRoutes as $route) {
                $counts[$route]++;
                if (count($examples[$route]) < 1) {
                    $examples[$route][] = $datetime;
                }
            }
            if (in_array('cui_guan_messenger', $match->matchedRoutes, true)
                && ($match->evidence['messenger_is_void'] ?? false) === true) {
                $messengerVoid++;
            }
            if (($match->evidence['fanben_hit'] ?? false) === true) {
                $fanbenHits++;
            }
            if (($match->evidence['fanyin'] ?? false) === true
                && $match->matchedRoutes !== []) {
                $fanyinAttached++;
            }
        }
    }
    ksort($overlap);
    $results[$mode] = compact(
        'denominator',
        'total',
        'counts',
        'overlap',
        'examples',
        'messenger_void',
        'fanben_hits',
        'fanyin_attached',
        'pending_total',
    );
}

$output = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
if (($argv[1] ?? null) !== null) {
    file_put_contents($argv[1], $output);
    echo "written to {$argv[1]}\n";
} else {
    echo $output;
}
