<?php

/** 文件作用：只读调用生产 PanCalculator、PanFacts 与 TianhuoRule，统计真实四立日天祸课。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\TianhuoRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new TianhuoRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$startYear = (int) ($argv[1] ?? 1900);
$endYear = (int) ($argv[2] ?? 2100);
$compact = ($argv[3] ?? null) === '--compact';
$fourLiDays = [];

foreach (range($startYear, $endYear) as $year) {
    foreach ([[2, 3, 5], [5, 4, 6], [8, 6, 8], [11, 6, 8]] as [$month, $firstDay, $lastDay]) {
        foreach (range($firstDay, $lastDay) as $day) {
            $datetime = sprintf('%04d-%02d-%02d 12:00:00', $year, $month, $day);
            $pan = $calculator->calculate($datetime);
            $fourLi = PanFacts::from($pan)->fourLiDay();
            if ($fourLi !== null) {
                $fourLiDays[$fourLi['date']] = $fourLi;
            }
        }
    }
}

$days = [];
$matches = [];
$directions = ['today_on_yesterday' => 0, 'yesterday_on_today' => 0];
foreach ($fourLiDays as $date => $fourLi) {
    $dayPan = $calculator->calculate($date.' 12:00:00');
    $day = PanCalculator::$tiangan[$dayPan->get('rigan')].PanCalculator::$dizhi[$dayPan->get('rizhi')];
    $days[] = ['date' => $date, 'four_li' => $fourLi['name'], 'day' => $day];

    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date, $hour);
        $match = $rule->match(PanFacts::from($calculator->calculate($datetime)));
        if ($match === null) {
            continue;
        }

        foreach ($match->evidence['matched_directions'] as $direction) {
            $directions[$direction]++;
        }
        $matches[] = [
            'datetime' => $datetime,
            'four_li' => $match->evidence['four_li']['name'],
            'today' => $match->evidence['today'],
            'yesterday' => $match->evidence['yesterday'],
            'directions' => $match->evidence['matched_directions'],
        ];
    }
}

echo json_encode([
    'years' => [$startYear, $endYear],
    'four_li_day_count' => count($fourLiDays),
    'four_li_days' => $compact ? 'compact mode: omitted; rerun without --compact for every day' : $days,
    'representative_hours' => $hours,
    'scanned_pans' => count($fourLiDays) * count($hours),
    'strict_matches' => count($matches),
    'direction_counts' => $directions,
    'matches' => $compact ? array_slice($matches, 0, 20) : $matches,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
