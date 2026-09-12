<?php

/** 文件作用：用生产 PanCalculator 复算并锁定无禄绝嗣课在 720 核心盘与 2026 全年十二时辰的统计。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Services\PanCalculator;

$calculator = new PanCalculator;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$countRange = static function (array $datetimes) use ($calculator): array {
    $counts = array_fill_keys(['wulu', 'juesi', 'union'], 0);
    foreach ($datetimes as $datetime) {
        $pan = $calculator->calculate($datetime);
        $relations = array_map(
            fn (int $index): int => $pan->get('wuxingShengke'.$index)[0],
            range(0, 3),
        );
        $wulu = count(array_filter($relations, fn (int $value): bool => $value === 1)) === 4;
        $juesi = count(array_filter($relations, fn (int $value): bool => $value === -1)) === 4;
        $counts['wulu'] += (int) $wulu;
        $counts['juesi'] += (int) $juesi;
        $counts['union'] += (int) ($wulu || $juesi);
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
        printf("%-10s %d / %d\n", $metric, $count, count($datetimes));
    }
}
