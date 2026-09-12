<?php

/** 文件作用：只读调用生产排盘、行年与解离规则，统计解离课冻结口径及研究比较项。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\JieliRule;
use App\Services\PanCalculator;

$rule = new JieliRule;
$elementFacts = PanFacts::from(new PanResult([]));
$hasKe = static function (int $a, int $b) use ($elementFacts): bool {
    $ae = $elementFacts->branchElement($a);
    $be = $elementFacts->branchElement($b);
    $restrains = static fn (?int $source, ?int $target): bool => $source !== null && $target !== null && ($source + 2) % 5 === $target;

    return $restrains($ae, $be) || $restrains($be, $ae);
};

$structure = array_fill_keys([
    'total', 'first_condition', 'strict_jieli', 'cross_fu_lower_qi_upper_only',
    'cross_fu_upper_qi_lower_only', 'both_cross', 'strict_plus_upper_upper_required',
    'cross_or_upper_upper_wide',
], 0);

for ($fu = 0; $fu < 12; $fu++) {
    for ($qi = 0; $qi < 12; $qi++) {
        if ($fu === $qi) {
            continue;
        }
        for ($pointer = 0; $pointer < 12; $pointer++) {
            $structure['total']++;
            $tianpan = array_map(fn (int $branch): int => ($branch + $pointer) % 12, range(0, 11));
            $fuUpper = $tianpan[$fu];
            $qiUpper = $tianpan[$qi];
            $first = BranchRelations::clashOf($fu) === $qi || $hasKe($fu, $qi);
            $crossA = $hasKe($fu, $qiUpper);
            $crossB = $hasKe($fuUpper, $qi);
            $upperUpper = $hasKe($fuUpper, $qiUpper);
            $strict = $first && ($crossA || $crossB);

            $structure['first_condition'] += (int) $first;
            $structure['strict_jieli'] += (int) $strict;
            $structure['cross_fu_lower_qi_upper_only'] += (int) ($strict && $crossA && ! $crossB);
            $structure['cross_fu_upper_qi_lower_only'] += (int) ($strict && ! $crossA && $crossB);
            $structure['both_cross'] += (int) ($strict && $crossA && $crossB);
            $structure['strict_plus_upper_upper_required'] += (int) ($strict && $upperUpper);
            $structure['cross_or_upper_upper_wide'] += (int) ($first && ($crossA || $crossB || $upperUpper));
        }
    }
}

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$husbandBirthIndex = $calculator->calculate('1986-08-01 00:00:00')->get('nian_index');
$wifeBirthIndex = $calculator->calculate('1994-08-01 00:00:00')->get('nian_index');
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$yearTotal = 0;
$yearStrict = 0;

for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $pan = $calculator->calculate($date->setTime($hour, 0)->format('Y-m-d H:i:s'));
        $husbandFate = $fateCalculator->calculate($husbandBirthIndex, $pan->get('nian_index'), 'male');
        $wifeFate = $fateCalculator->calculate($wifeBirthIndex, $pan->get('nian_index'), 'female');
        $result = new PanResult([
            ...$pan->toArray(),
            'context' => ['people' => [
                ['role' => 'querent', 'gender' => 'male', 'birth_datetime' => '1986-08-01T00:00', ...$husbandFate],
                ['role' => 'spouse', 'gender' => 'female', 'birth_datetime' => '1994-08-01T00:00', ...$wifeFate],
            ]],
        ]);
        $yearTotal++;
        $yearStrict += (int) ((new JieliRule)->match(PanFacts::from($result)) !== null);
    }
}

echo '[structure]'.PHP_EOL;
foreach ($structure as $metric => $count) {
    printf("%-42s %d / %d\n", $metric, $count, $structure['total']);
}

echo '[2026 fixed couple]'.PHP_EOL;
printf("%-42s %d / %d\n", 'strict_jieli', $yearStrict, $yearTotal);
