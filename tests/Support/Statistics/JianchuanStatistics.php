<?php

/** 文件作用：按真实年月日时扫描第61课间传课总命中、顺逆方向及二十四格分布。 */

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JianchuanRule;
use App\Services\PanCalculator;

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$rule = new JianchuanRule;

$counts = [
    'total' => 0,
    'jianchuan' => 0,
    'forward' => 0,
    'reverse' => 0,
    'day_initial_wang_xiang' => 0,
    'day_initial_xiu_qiu' => 0,
];
$subtypes = [];
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

        $counts['jianchuan']++;
        $direction = $match->evidence['direction'] ?? null;
        if ($direction === 'forward' || $direction === 'reverse') {
            $counts[$direction]++;
        }

        $subtype = (string) ($match->evidence['subtype'] ?? 'unknown');
        $subtypes[$subtype] = ($subtypes[$subtype] ?? 0) + 1;

        $judgments = collect($match->evidence['judgments'] ?? [])->keyBy('code');
        foreach (['day_initial_wang_xiang', 'day_initial_xiu_qiu'] as $code) {
            if (($judgments[$code]['matched'] ?? false) === true) {
                $counts[$code]++;
            }
        }

        if (count($firstMatches) < 24) {
            $firstMatches[] = [
                'datetime' => $datetime,
                'transmissions' => $match->evidence['transmissions'],
                'direction' => $direction,
                'subtype' => $subtype,
                'subtype_label' => $match->evidence['subtype_label'],
            ];
        }
    }
}

ksort($subtypes);

$ratio = static fn (int $count): float => $counts['total'] === 0 ? 0.0 : $count / $counts['total'];

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai',
    'representative_hours' => $hours,
    'denominator' => $counts['total'],
    'counts' => $counts,
    'ratios' => [
        'jianchuan' => $ratio($counts['jianchuan']),
        'forward' => $ratio($counts['forward']),
        'reverse' => $ratio($counts['reverse']),
    ],
    'subtypes' => $subtypes,
    'first_matches' => $firstMatches,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
