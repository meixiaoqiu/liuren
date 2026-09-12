<?php

/** 文件作用：只读调用生产 PanCalculator 与 PanFacts，按真实时令统计天狱课 A/B/C 候选及差集。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\TianyuRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$startYear = (int) ($argv[1] ?? 2000);
$endYear = (int) ($argv[2] ?? $startYear);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$counts = ['A' => 0, 'B' => 0, 'C' => 0, 'A_minus_B' => 0, 'B_minus_A' => 0, 'A_xor_C' => 0];
$periods = [];
$aOnly = [];
$routeExamples = [];
$scanned = 0;

for ($year = $startYear; $year <= $endYear; $year++) {
    $date = new DateTimeImmutable("{$year}-01-01 00:00:00", new DateTimeZone('Asia/Shanghai'));
    $end = new DateTimeImmutable(($year + 1).'-01-01 00:00:00', new DateTimeZone('Asia/Shanghai'));

    for (; $date < $end; $date = $date->modify('+1 day')) {
        foreach ($hours as $hour) {
            $datetime = $date->format('Y-m-d').' '.sprintf('%02d:00:00', $hour);
            $facts = PanFacts::from($calculator->calculate($datetime));
            $dayStem = $facts->get('rigan');
            $initial = $facts->get('sanchuan0');
            $tianpan = $facts->get('tianpan');
            $period = $facts->seasonalPeriod();
            if (! is_int($dayStem) || ! is_int($initial) || ! is_array($tianpan) || $period === null) {
                continue;
            }

            $scanned++;
            $state = $facts->branchSeasonalState($initial);
            $dou = ($tianpan[TianyuRule::DAY_ORIGIN[$dayStem]] ?? null) === 4;
            $qiuSi = $state === '囚' || $state === '死';
            $grave = $initial === TianyuRule::DAY_GRAVE[$dayStem];
            $routeKey = implode('+', array_filter([
                $state === '囚' ? '囚' : null,
                $state === '死' ? '死' : null,
                $grave ? '墓' : null,
            ]));
            $matches = [
                'A' => $dou && ($qiuSi || $grave),
                'B' => $dou && $qiuSi,
                'C' => $dou && $qiuSi && $grave,
            ];
            $key = $period['key'];
            $periods[$key] ??= ['name' => $period['name'], 'A' => 0, 'B' => 0, 'C' => 0, 'A_minus_B' => 0];
            foreach (['A', 'B', 'C'] as $candidate) {
                $counts[$candidate] += (int) $matches[$candidate];
                $periods[$key][$candidate] += (int) $matches[$candidate];
            }
            $counts['A_minus_B'] += (int) ($matches['A'] && ! $matches['B']);
            $counts['B_minus_A'] += (int) ($matches['B'] && ! $matches['A']);
            $counts['A_xor_C'] += (int) ($matches['A'] !== $matches['C']);
            $periods[$key]['A_minus_B'] += (int) ($matches['A'] && ! $matches['B']);

            if ($matches['A'] && ! $matches['B'] && count($aOnly) < 30) {
                $aOnly[] = [
                    'datetime' => $datetime,
                    'day' => PanCalculator::$tiangan[$dayStem].PanCalculator::$dizhi[$facts->get('rizhi')],
                    'initial' => TianyuRule::BRANCH_NAMES[$initial],
                    'state' => $state,
                    'period' => $period['name'],
                    'origin' => TianyuRule::BRANCH_NAMES[TianyuRule::DAY_ORIGIN[$dayStem]],
                    'grave' => TianyuRule::BRANCH_NAMES[TianyuRule::DAY_GRAVE[$dayStem]],
                ];
            }

            if ($matches['A'] && ! isset($routeExamples[$routeKey])) {
                $routeExamples[$routeKey] = [
                    'datetime' => $datetime,
                    'day' => PanCalculator::$tiangan[$dayStem].PanCalculator::$dizhi[$facts->get('rizhi')],
                    'initial' => TianyuRule::BRANCH_NAMES[$initial],
                    'state' => $state,
                    'period' => $period['name'],
                    'origin' => TianyuRule::BRANCH_NAMES[TianyuRule::DAY_ORIGIN[$dayStem]],
                    'grave' => TianyuRule::BRANCH_NAMES[TianyuRule::DAY_GRAVE[$dayStem]],
                ];
            }
        }
    }
}

echo json_encode([
    'years' => [$startYear, $endYear],
    'timezone' => 'Asia/Shanghai (UTC+8)',
    'sampling' => '每天十二个双数时辰代表点：23,1,3,...,21整点；23:00按生产换日口径计算',
    'scanned_pans' => $scanned,
    'counts' => $counts,
    'periods' => $periods,
    'representative_a_only' => $aOnly,
    'representative_routes' => $routeExamples,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
