<?php

/** 文件作用：按真实年月日时扫描第60课连珠课两条主体路线、交集与岁月日重复支命中规模。 */

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LianzhuRule;
use App\Services\PanCalculator;

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$rule = new LianzhuRule;

$counts = array_fill_keys([
    'total',
    'lianzhu',
    'meng_forward',
    'meng_reverse',
    'year_month_day_forward',
    'day_month_year_reverse',
    'both_meng_and_calendar',
    'calendar_with_repeated_branches',
], 0);
$firstMatches = [];

$start = new DateTimeImmutable("{$year}-01-01 00:00:00", $timezone);
$end = $start->modify('+1 year');

for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $facts = PanFacts::from($calculator->calculate($datetime));
        $counts['total']++;

        $match = $rule->match($facts);
        if ($match === null) {
            continue;
        }

        $counts['lianzhu']++;
        $flags = $match->evidence['route_flags'] ?? [];
        foreach (['meng_forward', 'meng_reverse', 'year_month_day_forward', 'day_month_year_reverse'] as $key) {
            if (($flags[$key] ?? false) === true) {
                $counts[$key]++;
            }
        }

        $meng = ($flags['meng_forward'] ?? false) || ($flags['meng_reverse'] ?? false);
        $calendar = ($flags['year_month_day_forward'] ?? false) || ($flags['day_month_year_reverse'] ?? false);
        if ($meng && $calendar) {
            $counts['both_meng_and_calendar']++;
        }

        if ($calendar) {
            $calendarBranches = [
                $match->evidence['year_branch'] ?? null,
                $match->evidence['month_branch'] ?? null,
                $match->evidence['day_branch'] ?? null,
            ];
            if (count(array_unique($calendarBranches, SORT_REGULAR)) < 3) {
                $counts['calendar_with_repeated_branches']++;
            }
        }

        if (count($firstMatches) < 20) {
            $firstMatches[] = [
                'datetime' => $datetime,
                'transmissions' => $match->evidence['transmissions'],
                'year_branch' => $match->evidence['year_branch'],
                'month_branch' => $match->evidence['month_branch'],
                'day_branch' => $match->evidence['day_branch'],
                'matched_routes' => $match->evidence['matched_routes'],
            ];
        }
    }
}

$ratio = static fn (int $count): float => $counts['total'] === 0 ? 0.0 : $count / $counts['total'];

$result = [
    'year' => $year,
    'timezone' => 'Asia/Shanghai',
    'representative_hours' => $hours,
    'denominator' => $counts['total'],
    'counts' => $counts,
    'ratios' => [
        'lianzhu' => $ratio($counts['lianzhu']),
        'meng_forward' => $ratio($counts['meng_forward']),
        'meng_reverse' => $ratio($counts['meng_reverse']),
        'year_month_day_forward' => $ratio($counts['year_month_day_forward']),
        'day_month_year_reverse' => $ratio($counts['day_month_year_reverse']),
    ],
    'first_matches' => $firstMatches,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
