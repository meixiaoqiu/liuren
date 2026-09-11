<?php

/**
 * 文件作用：只读枚举 2026 全年十二代表时辰及 720 核心 fixture 的冲破课候选口径。
 * 运行方式：php tests/Support/Statistics/Chongpo2026Stat.php
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ChongpoRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$counts = [
    'total' => 0,
    'strict_path_a' => 0,
    'temporal_initial_loose_or' => 0,
    'temporal_any_transmission_loose_or' => 0,
    'same_base_chong_po_pair_in_transmissions' => 0,
    'same_base_pair_with_initial_participating' => 0,
];

for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $counts['total']++;
        $pan = $calculator->calculate($date->setTime($hour, 0)->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $initial = $pan->get('sanchuan0');
        $transmissions = [$initial, $pan->get('sanchuan1'), $pan->get('sanchuan2')];
        $temporalBases = array_values(array_unique([
            $facts->stemLodgingBranch($pan->get('niangan')),
            $pan->get('nianzhi'),
            $facts->stemLodgingBranch($pan->get('yuegan')),
            $pan->get('yuezhi'),
            $facts->stemLodgingBranch($pan->get('rigan')),
            $pan->get('rizhi'),
            $facts->stemLodgingBranch($pan->get('shigan')),
            $pan->get('shizhi'),
        ]));
        $looseRelations = [];
        $pairBases = [];
        foreach ($temporalBases as $base) {
            if (! is_int($base)) {
                continue;
            }
            $baseClash = BranchRelations::clashOf($base);
            $baseBreak = BranchRelations::breakOf($base);
            $looseRelations[] = $baseClash;
            $looseRelations[] = $baseBreak;
            if (in_array($baseClash, $transmissions, true) && in_array($baseBreak, $transmissions, true)) {
                $pairBases[] = $base;
            }
        }

        $counts['strict_path_a'] += (int) ((new ChongpoRule)->match($facts) !== null);
        $counts['temporal_initial_loose_or'] += (int) in_array($initial, $looseRelations, true);
        $counts['temporal_any_transmission_loose_or'] += (int) array_any($transmissions, static fn (int $branch): bool => in_array($branch, $looseRelations, true));
        $counts['same_base_chong_po_pair_in_transmissions'] += (int) ($pairBases !== []);
        $counts['same_base_pair_with_initial_participating'] += (int) array_any(
            $pairBases,
            static fn (int $base): bool => $initial === BranchRelations::clashOf($base)
                || $initial === BranchRelations::breakOf($base),
        );
    }
}

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$coreStrict = 0;
foreach ($fixture['cases'] as $case) {
    $pan = $calculator->calculate($case['input']);
    $coreStrict += (int) ((new ChongpoRule)->match(PanFacts::from($pan)) !== null);
}

echo json_encode(['year_2026' => $counts, 'core_720_strict_path_a' => $coreStrict], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
