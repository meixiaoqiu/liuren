<?php

/** 文件作用：只读统计鬼墓课正式 matcher（A'：日鬼 AND（干墓 OR 支墓））的命中，并搜索真实生产案例。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\GuimuRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$calculator = new PanCalculator;
$rule = new GuimuRule;

// 仅供统计被 A' 排除的旁路；正式 matched 与路线一律取 GuimuRule::match()。
$dayGhosts = [
    0 => [8], 1 => [9], 2 => [0], 3 => [11], 4 => [2], 5 => [3],
    6 => [6], 7 => [5], 8 => [4, 10], 9 => [1, 7],
];
$stemTomb = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];
$branchTomb = [4, 4, 7, 7, 4, 10, 10, 4, 1, 1, 4, 4];

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$dizhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
$tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

$diagnose = static function (int $rigan, int $rizhi, int $initial) use ($dayGhosts, $stemTomb, $branchTomb): array {
    $ghosts = $dayGhosts[$rigan] ?? [];
    $isGhost = in_array($initial, $ghosts, true);
    $isStemTomb = $initial === $stemTomb[$rigan];
    $isBranchTomb = $initial === $branchTomb[$rizhi];

    return compact('isGhost', 'isStemTomb', 'isBranchTomb');
};

$record = static function (array &$stats, PanResult $pan) use ($rule, $diagnose): ?array {
    $panData = $pan->toArray();
    $facts = PanFacts::from(new PanResult($panData));
    $match = $rule->match($facts);
    $diagnostic = $diagnose($panData['rigan'], $panData['rizhi'], $panData['sanchuan0']);

    if ($match === null) {
        if ($diagnostic['isGhost'] && ! $diagnostic['isStemTomb'] && ! $diagnostic['isBranchTomb']) {
            $stats['ghost_only']++;
        }
        if (! $diagnostic['isGhost'] && $diagnostic['isStemTomb'] && ! $diagnostic['isBranchTomb']) {
            $stats['stem_tomb_only']++;
        }
        if (! $diagnostic['isGhost'] && ! $diagnostic['isStemTomb'] && $diagnostic['isBranchTomb']) {
            $stats['branch_tomb_only']++;
        }

        return null;
    }

    $stats['matched']++;
    $routes = $match->evidence['matched_routes'];
    if ($routes === ['day_ghost', 'stem_tomb', 'branch_tomb']) {
        $stats['all_three']++;
    } elseif ($routes === ['day_ghost', 'stem_tomb']) {
        $stats['ghost_and_stem_tomb']++;
    } elseif ($routes === ['day_ghost', 'branch_tomb']) {
        $stats['ghost_and_branch_tomb']++;
    }

    return $routes;
};

// 720 核心口径严格复用冻结 fixture 的 12 种天地盘关系 × 60 日干支生产输入。
$core = [
    'total' => 0, 'matched' => 0,
    'ghost_only' => 0, 'stem_tomb_only' => 0, 'branch_tomb_only' => 0,
    'ghost_and_stem_tomb' => 0, 'ghost_and_branch_tomb' => 0, 'all_three' => 0,
];
foreach (PanRegression::loadFixture()['cases'] as $case) {
    $pan = $calculator->calculate($case['input']);
    $core['total']++;
    $record($core, $pan);
}
$core['ratio'] = $core['total'] === 0 ? 0 : $core['matched'] / $core['total'];

// 2026 全年：4380 盘。
$year = [
    'total' => 0, 'matched' => 0,
    'ghost_only' => 0, 'stem_tomb_only' => 0, 'branch_tomb_only' => 0,
    'ghost_and_stem_tomb' => 0, 'ghost_and_branch_tomb' => 0, 'all_three' => 0,
];
$examples = [
    'ghost_and_stem_tomb' => null,
    'ghost_and_branch_tomb' => null,
    'all_three' => null,
];

$cur = new DateTimeImmutable('2026-01-01');
$end = new DateTimeImmutable('2027-01-01');
while ($cur < $end) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $cur->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime);
        $year['total']++;
        $routes = $record($year, $pan);
        if ($routes === null) {
            continue;
        }
        $key = null;
        if ($routes === ['day_ghost', 'stem_tomb', 'branch_tomb']) {
            $key = 'all_three';
        } elseif ($routes === ['day_ghost', 'stem_tomb']) {
            $key = 'ghost_and_stem_tomb';
        } elseif ($routes === ['day_ghost', 'branch_tomb']) {
            $key = 'ghost_and_branch_tomb';
        }
        if ($key !== null && $examples[$key] === null) {
            $panData = $pan->toArray();
            $examples[$key] = [
                'datetime' => $datetime,
                'day_ganzhi' => $tiangan[$panData['rigan']].$dizhi[$panData['rizhi']],
                'sanchuan' => [$dizhi[$panData['sanchuan0']], $dizhi[$panData['sanchuan1']], $dizhi[$panData['sanchuan2']]],
                'shizhi' => $dizhi[$panData['shizhi']],
                'routes' => $routes,
            ];
        }
    }
    $cur = $cur->modify('+1 day');
}
$year['ratio'] = $year['total'] === 0 ? 0 : $year['matched'] / $year['total'];

echo json_encode([
    'matcher' => "A'：in_array(initial, DAY_GHOSTS[rigan]) AND (initial === stemElementTomb(rigan) OR initial === branchElementTomb(rizhi))",
    'timezone' => 'Asia/Shanghai',
    'hours' => $hours,
    'core_720' => $core,
    'year_2026' => $year,
    'case_candidates' => $examples,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
