<?php

/** 文件作用：只读扫描 2026 年 4380 个北京代表时刻，统计殃咎七路、交集及神将克战严格/宽/混合候选。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\YangjiuRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

$year = (int) ($argv[1] ?? 2026);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$rule = new YangjiuRule;
$routeKeys = [
    'forward_recursive_overcoming', 'reverse_recursive_overcoming',
    'initial_transmission_sandwiched_overcoming', 'all_three_external_battle',
    'all_three_internal_battle', 'stem_branch_riding_tombs', 'stem_branch_sitting_on_tombs',
];
$counts = array_fill_keys($routeKeys, 0);
$exclusive = array_fill_keys($routeKeys, 0);
$intersections = [];
$multiRoute = 0;
$totalMatched = 0;
$strict = 0;
$broad = 0;
$mixed = 0;
$firstByRoute = [];
$classic = [];

for ($date = new DateTimeImmutable("{$year}-01-01"), $end = $date->modify('+1 year'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $facts = PanFacts::from($calculator->calculate($datetime));
        $analysis = $rule->analyze($facts);
        if ($analysis === null) {
            continue;
        }
        $matched = $analysis['matched_routes'];
        foreach ($matched as $key) {
            $counts[$key]++;
            $firstByRoute[$key] ??= $datetime;
        }
        if ($matched !== []) {
            $totalMatched++;
            sort($matched);
            $intersections[implode('+', $matched)] = ($intersections[implode('+', $matched)] ?? 0) + 1;
            if (count($matched) === 1) {
                $exclusive[$matched[0]]++;
            } else {
                $multiRoute++;
            }
        }

        $battleRows = array_map(static fn (array $route): bool => $route['matched'], [
            $analysis['routes']['all_three_external_battle'], $analysis['routes']['all_three_internal_battle'],
        ]);
        $strictHit = in_array(true, $battleRows, true);
        $battleDetails = [];
        foreach (['sanchuan0', 'sanchuan1', 'sanchuan2'] as $i => $key) {
            $branch = $facts->get($key);
            $general = $facts->get($key.'tianjiang');
            $branchElement = is_int($branch) ? $facts->branchElement($branch) : null;
            $generalElement = is_int($general) ? $facts->generalElement($general) : null;
            $external = $generalElement !== null && $branchElement !== null && ($generalElement + 2) % 5 === $branchElement;
            $internal = $generalElement !== null && $branchElement !== null && ($branchElement + 2) % 5 === $generalElement;
            $battleDetails[] = $external || $internal;
        }
        $broadHit = in_array(true, $battleDetails, true);
        $mixedHit = ! in_array(false, $battleDetails, true);
        $strict += (int) $strictHit;
        $broad += (int) $broadHit;
        $mixed += (int) $mixedHit;

        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
        $generals = [$facts->get('sanchuan0tianjiang'), $facts->get('sanchuan1tianjiang'), $facts->get('sanchuan2tianjiang')];
        $targets = [
            '己巳日巳申寅' => $stem === 5 && $branch === 5 && $transmissions === [5, 8, 2],
            '丙子日子未寅' => $stem === 2 && $branch === 0 && $transmissions === [0, 7, 2],
            '壬子日午加亥乘玄武' => $stem === 8 && $branch === 0 && $facts->get('sanchuan0') === 6 && $facts->heavenBranchGroundPosition(6) === 11 && $generals[0] === 9,
            '壬申日亥加辰申加丑' => $stem === 8 && $branch === 8 && ($facts->get('tianpan')[4] ?? null) === 11 && ($facts->get('tianpan')[1] ?? null) === 8,
            '丙寅日干上戌支上未' => $stem === 2 && $branch === 2 && ($facts->get('sike')[1] ?? null) === 10 && ($facts->get('sike')[5] ?? null) === 7,
            '庚午日戌午寅外战' => $stem === 6 && $branch === 6 && $transmissions === [10, 6, 2] && $generals === [3, 11, 7],
            '己酉日酉丑巳内战' => $stem === 5 && $branch === 9 && $transmissions === [9, 1, 5] && $generals === [3, 11, 7],
        ];
        foreach ($targets as $label => $hit) {
            if ($hit && ! isset($classic[$label])) {
                $classic[$label] = $datetime;
            }
        }
    }
}

ksort($intersections);
echo json_encode([
    'year' => $year, 'timezone' => 'Asia/Shanghai / 北京固定 UTC+8', 'hours' => $hours,
    'total' => (($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0)) ? 366 : 365) * 12,
    'routes' => $counts, 'exclusive' => $exclusive, 'intersections' => $intersections,
    'multi_route' => $multiRoute, 'matched' => $totalMatched,
    'matched_ratio' => $totalMatched / 4380,
    'battle_candidates' => ['strict' => $strict, 'broad' => $broad, 'mixed_all_three' => $mixed],
    'first_by_route' => $firstByRoute, 'classic_examples_found_in_year' => $classic,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
