<?php

/** 文件作用：只读调用生产排盘与芜淫规则，统计 720 核心盘和 2026 全年十二时辰的冻结指标。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\WuyinRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new WuyinRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$countRange = static function (array $datetimes) use ($calculator, $rule): array {
    $counts = array_fill_keys(['three_lesson_only', 'three_lesson_with_ke', 'cross_restrain', 'cross_restrain_with_day_generation', 'bubei_cross_overlap', 'strict_union', 'wide_bubei_union'], 0);
    foreach ($datetimes as $datetime) {
        $facts = PanFacts::from($calculator->calculate($datetime));
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $sike = $facts->get('sike');
        $stemGround = $facts->stemLodgingBranch($stem);
        $pairs = [[$stemGround, $sike[1]], [$sike[2], $sike[3]], [$branch, $sike[5]], [$sike[6], $sike[7]]];
        $three = count(array_unique(array_map(fn (array $pair): string => implode(':', $pair), $pairs))) === 3;
        $match = $rule->match($facts);
        $bubeiKe = $match?->evidence['bubei_path'] ?? false;
        $cross = $match?->evidence['cross_path'] ?? false;
        $stemElement = $facts->stemElement($stem);
        $branchElement = $facts->branchElement($branch);
        $dayGeneration = ($stemElement + 1) % 5 === $branchElement
            || ($branchElement + 1) % 5 === $stemElement;

        $counts['three_lesson_only'] += (int) $three;
        $counts['three_lesson_with_ke'] += (int) $bubeiKe;
        $counts['cross_restrain'] += (int) $cross;
        $counts['cross_restrain_with_day_generation'] += (int) ($cross && $dayGeneration);
        $counts['bubei_cross_overlap'] += (int) ($bubeiKe && $cross);
        $counts['strict_union'] += (int) ($bubeiKe || $cross);
        $counts['wide_bubei_union'] += (int) ($three || $cross);
    }

    return $counts;
};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$core = array_map(
    fn (array $case): string => $case['input'],
    $fixture['cases'],
);
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
