<?php

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\LianMuGuiRenRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$birthDatetime = '1985-08-01 00:00:00';
$birthYearIndex = $calculator->calculate($birthDatetime)->get('nian_index');
$rule = new LianMuGuiRenRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$routes = ['curtain_noble_on_stem_or_fate', 'xun_head_as_curtain_noble', 'chen_xu_xun_head_on_stem_or_fate', 'dou_gui_on_stem_or_fate', 'ya_kui_you_on_stem_or_fate', 'day_virtue_enters_heaven_gate', 'true_vermilion_bird', 'two_nobles_flank_fate'];
$start = new DateTimeImmutable($argv[2] ?? '2031-01-01');
$end = new DateTimeImmutable($argv[3] ?? '2031-12-31');
$selectedMode = $argv[4] ?? null;
$results = [];
$modes = ['without_people' => null, 'with_test_person' => true];
if (is_string($selectedMode) && array_key_exists($selectedMode, $modes)) {
    $modes = [$selectedMode => $modes[$selectedMode]];
}
foreach ($modes as $mode => $person) {
    $counts = array_fill_keys($routes, 0);
    $examples = array_fill_keys($routes, []);
    $overlap = [];
    $total = 0;
    $denominator = 0;
    for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
        foreach ($hours as $hour) {
            $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $data = $calculator->calculate($datetime)->toArray();
            $fate = $person === null ? null : $fateCalculator->calculate($birthYearIndex, $data['nian_index'], 'male');
            $data['context'] = ['people' => $fate === null ? [] : [[
                'role' => 'querent', 'birth_datetime' => $birthDatetime, 'gender' => 'male', ...$fate,
            ]]];
            $match = $rule->match(PanFacts::from(new PanResult($data)));
            $denominator++;
            if ($match === null || $match->matchedRoutes === []) {
                continue;
            }
            $total++;
            $key = implode('+', $match->matchedRoutes);
            $overlap[$key] = ($overlap[$key] ?? 0) + 1;
            foreach ($match->matchedRoutes as $route) {
                $counts[$route]++;
                if (count($examples[$route]) < 1) {
                    $examples[$route][] = $datetime;
                }
            }
        }
    }
    ksort($overlap);
    $results[$mode] = compact('denominator', 'total', 'counts', 'overlap', 'examples');
}
$output = json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
if (($argv[1] ?? null) !== null) {
    file_put_contents($argv[1], $output);
} else {
    echo $output;
}
