<?php

/** 文件作用：只读调用生产排盘、盘面事实与天网规则，统计全年正式命中及占时、初传同支分布。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\TianwangRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new TianwangRule;
$year = (int) ($argv[1] ?? 2026);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$counts = [
    'total' => 0,
    'matched' => 0,
    'matched_same_branch' => 0,
    'matched_different_branch' => 0,
];
$byDayStem = array_fill_keys(array_slice(PanCalculator::$tiangan, 0, 10), 0);
$differentBranchExample = null;

$date = new DateTimeImmutable(sprintf('%04d-01-01', $year));
$end = $date->modify('+1 year');
while ($date < $end) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $facts = PanFacts::from($calculator->calculate($datetime));
        $match = $rule->match($facts);
        $counts['total']++;
        if ($match === null) {
            continue;
        }

        $counts['matched']++;
        $same = $match->evidence['time_equals_initial'];
        $counts[$same ? 'matched_same_branch' : 'matched_different_branch']++;
        $dayStem = PanCalculator::$tiangan[$match->evidence['day_stem']];
        $byDayStem[$dayStem]++;

        if (! $same && $differentBranchExample === null) {
            $pan = $facts;
            $differentBranchExample = [
                'datetime' => $datetime,
                'day' => PanCalculator::$tiangan[$pan->get('rigan')].PanCalculator::$dizhi[$pan->get('rizhi')],
                'time_branch' => PanCalculator::$dizhi[$match->evidence['time_branch']],
                'transmissions' => array_map(
                    fn (string $key): string => PanCalculator::$dizhi[$pan->get($key)],
                    ['sanchuan0', 'sanchuan1', 'sanchuan2'],
                ),
            ];
        }
    }
    $date = $date->modify('+1 day');
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai / fixed UTC+8 production convention',
    'representative_hours' => $hours,
    ...$counts,
    'by_day_stem' => $byDayStem,
    'different_branch_example' => $differentBranchExample,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
