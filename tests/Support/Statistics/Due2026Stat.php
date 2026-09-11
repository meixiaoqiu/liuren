<?php

/** 文件作用：用生产 PanCalculator 复算并锁定度厄课在 720 核心盘与 2026 全年十二时辰的统计。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Services\PanCalculator;

$calculator = new PanCalculator;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$countRange = static function (array $datetimes) use ($calculator): array {
    $counts = array_fill_keys(['you_due', 'chang_due', 'due_union', 'four_up_restrain', 'four_down_restrain', 'at_least_three_union'], 0);
    foreach ($datetimes as $datetime) {
        $pan = $calculator->calculate($datetime);
        $relations = array_map(
            fn (int $index): int => $pan->get('wuxingShengke'.$index)[0],
            range(0, 3),
        );
        $up = count(array_filter($relations, fn (int $value): bool => $value === 1));
        $down = count(array_filter($relations, fn (int $value): bool => $value === -1));
        $you = $up === 3;
        $chang = $down === 3;
        $counts['you_due'] += (int) $you;
        $counts['chang_due'] += (int) $chang;
        $counts['due_union'] += (int) ($you || $chang);
        $counts['four_up_restrain'] += (int) ($up === 4);
        $counts['four_down_restrain'] += (int) ($down === 4);
        $counts['at_least_three_union'] += (int) ($up >= 3 || $down >= 3);
    }

    return $counts;
};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$core = array_map(fn (array $case): string => $case['input'], $fixture['cases']);
$year = [];
for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $year[] = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
    }
}

foreach ([['720', $core], ['2026', $year]] as [$label, $datetimes]) {
    echo "[{$label}] ".count($datetimes).PHP_EOL;
    foreach ($countRange($datetimes) as $metric => $count) {
        printf("%-42s %d / %d\n", $metric, $count, count($datetimes));
    }
}
