<?php

/** 文件作用：只读调用生产排盘、分至事实、天寇规则与静态月宿表，统计正式规则及被淘汰的四离候选。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Astronomy\MoonPalaceTable;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\TiankouRule;
use App\Services\PanCalculator;
use com\tyme\solar\SolarTime;

$calculator = new PanCalculator;
$table = new MoonPalaceTable;
$rule = new TiankouRule($table);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$startYear = (int) ($argv[1] ?? 2000);
$endYear = (int) ($argv[2] ?? 2030);
$compact = ($argv[3] ?? null) === '--compact';
$fenZhiDays = [];

if (($argv[3] ?? null) === '--ancient') {
    $targets = [
        6 => ['label' => '《大全》癸卯春分辰加寅', 'stem' => 9, 'branch' => 3, 'moon_palace' => 4],
        18 => ['label' => '《订讹》丁酉秋分月宿临申', 'stem' => 3, 'branch' => 9, 'moon_palace' => null],
    ];
    $candidateDays = [];
    $reproductions = [];

    for ($year = $startYear; $year <= $endYear; $year++) {
        $term = SolarTime::fromYmdHms($year, 1, 1, 0, 0, 0)->getTerm();
        for ($offset = 0; $offset <= 24; $offset++) {
            $candidate = $term->next($offset);
            $target = $targets[$candidate->getIndex()] ?? null;
            if ($target === null) {
                continue;
            }

            $solar = $candidate->getJulianDay()->getSolarTime();
            if ($solar->getYear() !== $year) {
                continue;
            }
            $date = sprintf('%04d-%02d-%02d', $solar->getYear(), $solar->getMonth(), $solar->getDay());
            $facts = PanFacts::from($calculator->calculate($date.' 12:00:00'));
            if ($facts->get('rigan') !== $target['stem'] || $facts->get('rizhi') !== $target['branch']) {
                continue;
            }

            $candidateDays[] = ['source_structure' => $target['label'], 'date' => $date];
            foreach ($hours as $hour) {
                $datetime = sprintf('%s %02d:00:00', $date, $hour);
                $match = $rule->match(PanFacts::from($calculator->calculate($datetime)));
                if ($match === null
                    || $match->evidence['day_stem'] !== $target['stem']
                    || $match->evidence['day_branch'] !== $target['branch']
                    || ($target['moon_palace'] !== null && $match->evidence['moon_palace'] !== $target['moon_palace'])) {
                    continue;
                }
                $reproductions[] = [
                    'source_structure' => $target['label'],
                    'datetime' => $datetime,
                    'day' => $match->evidence['day'],
                    'previous_day' => $match->evidence['previous_day'],
                    'li_branch' => PanCalculator::$dizhi[$match->evidence['li_branch']],
                    'moon_palace' => PanCalculator::$dizhi[$match->evidence['moon_palace']],
                    'moon_ground' => PanCalculator::$dizhi[$match->evidence['moon_palace_ground']],
                ];
            }
        }
    }

    echo json_encode([
        'years' => [$startYear, $endYear],
        'mode' => 'ancient-structure-modern-reproduction',
        'candidate_days_with_matching_term_and_day_ganzhi' => $candidateDays,
        'reproductions' => $reproductions,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
    exit(0);
}

for ($year = $startYear; $year <= $endYear; $year++) {
    foreach ([[3, 19, 21], [6, 20, 22], [9, 22, 24], [12, 21, 23]] as [$month, $firstDay, $lastDay]) {
        foreach (range($firstDay, $lastDay) as $day) {
            $datetime = sprintf('%04d-%02d-%02d 12:00:00', $year, $month, $day);
            $facts = PanFacts::from($calculator->calculate($datetime));
            $fenZhi = $facts->fenZhiDay();
            if ($fenZhi !== null) {
                $fenZhiDays[$fenZhi['date']] = $fenZhi;
            }
        }
    }
}

$counts = ['A' => 0, 'B' => 0, 'intersection' => 0, 'A_only' => 0, 'B_only' => 0];
$byTerm = ['春分' => 0, '夏至' => 0, '秋分' => 0, '冬至' => 0];
$matches = [];
$candidateMatches = [];
$dates = [];
foreach ($fenZhiDays as $date => $fenZhi) {
    $dates[$date] = ['type' => 'B', 'fen_zhi' => $fenZhi];
    $eve = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
    $dates[$eve] = ['type' => 'A', 'fen_zhi' => $fenZhi];
}

foreach ($dates as $date => $meta) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date, $hour);
        $facts = PanFacts::from($calculator->calculate($datetime));
        $moonPalace = $table->palaceAt(new DateTimeImmutable($datetime, new DateTimeZone('+08:00')));
        $moonGround = $facts->heavenBranchGroundPosition($moonPalace);
        $civilDayIndex = $facts->civilDaySexagenaryDayIndex();
        $dayBranch = is_int($civilDayIndex) ? $civilDayIndex % 12 : null;
        $candidateA = $meta['type'] === 'A' && is_int($dayBranch) && $moonGround === $dayBranch;
        $match = $rule->match($facts);
        $candidateB = $match !== null;

        $counts['A'] += (int) $candidateA;
        $counts['B'] += (int) $candidateB;
        $counts['intersection'] += (int) ($candidateA && $candidateB);
        $counts['A_only'] += (int) ($candidateA && ! $candidateB);
        $counts['B_only'] += (int) ($candidateB && ! $candidateA);

        if ($candidateA) {
            $candidateMatches[] = ['datetime' => $datetime, 'day_branch' => PanCalculator::$dizhi[$dayBranch], 'moon_palace' => PanCalculator::$dizhi[$moonPalace], 'moon_ground' => PanCalculator::$dizhi[$moonGround]];
        }
        if ($match !== null) {
            $byTerm[$match->evidence['fen_zhi_name']]++;
            $matches[] = [
                'datetime' => $datetime,
                'fen_zhi' => $match->evidence['fen_zhi_name'],
                'term_time' => $match->evidence['fen_zhi_term_time'],
                'day' => $match->evidence['day'],
                'previous_day' => $match->evidence['previous_day'],
                'li_branch' => PanCalculator::$dizhi[$match->evidence['li_branch']],
                'moon_palace' => PanCalculator::$dizhi[$match->evidence['moon_palace']],
                'moon_ground' => PanCalculator::$dizhi[$match->evidence['moon_palace_ground']],
            ];
        }
    }
}

echo json_encode([
    'years' => [$startYear, $endYear],
    'fen_zhi_day_count' => count($fenZhiDays),
    'representative_hours' => $hours,
    'formal_scanned_pans' => count($fenZhiDays) * count($hours),
    'comparison_universe_pans' => count($dates) * count($hours),
    'formal_matches_by_term' => $byTerm,
    'candidate_comparison' => $counts,
    'formal_matches' => $compact ? array_slice($matches, 0, 20) : $matches,
    'rejected_candidate_matches' => $compact ? array_slice($candidateMatches, 0, 20) : $candidateMatches,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
