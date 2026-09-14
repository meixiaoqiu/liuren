<?php

/** 文件作用：只读统计鬼墓课正式 matcher（A'：日鬼 AND（干墓 OR 支墓））的命中，并搜索真实生产案例。 */

use App\Services\PanCalculator;
use App\Support\PanRegression;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$calculator = new PanCalculator;

$dayGhosts = [
    0 => [8], 1 => [9], 2 => [0], 3 => [11], 4 => [2], 5 => [3],
    6 => [6], 7 => [5], 8 => [4, 10], 9 => [1, 7],
];
$stemTomb = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];
$branchTomb = [4, 4, 7, 7, 4, 10, 10, 4, 1, 1, 4, 4];

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$dizhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
$tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

$classify = static function (int $rigan, int $rizhi, int $initial) use ($dayGhosts, $stemTomb, $branchTomb): array {
    $ghosts = $dayGhosts[$rigan] ?? [];
    $isGhost = in_array($initial, $ghosts, true);
    $isStemTomb = $initial === $stemTomb[$rigan];
    $isBranchTomb = $initial === $branchTomb[$rizhi];
    if (! $isGhost) {
        return ['match' => false, 'routes' => [], 'tomb_targets' => []];
    }
    if (! ($isStemTomb || $isBranchTomb)) {
        return ['match' => false, 'routes' => ['day_ghost'], 'tomb_targets' => []];
    }
    $routes = ['day_ghost'];
    if ($isStemTomb) {
        $routes[] = 'stem_tomb';
    }
    if ($isBranchTomb) {
        $routes[] = 'branch_tomb';
    }

    return ['match' => true, 'routes' => $routes, 'tomb_targets' => array_values(array_filter([
        $isStemTomb ? 'stem_tomb' : null,
        $isBranchTomb ? 'branch_tomb' : null,
    ]))];
};

// 720 核心口径严格复用冻结 fixture 的 12 种天地盘关系 × 60 日干支生产输入。
$core = [
    'total' => 0, 'matched' => 0,
    'ghost_only' => 0, 'stem_tomb_only' => 0, 'branch_tomb_only' => 0,
    'ghost_and_stem_tomb' => 0, 'ghost_and_branch_tomb' => 0, 'all_three' => 0,
];
foreach (PanRegression::loadFixture()['cases'] as $case) {
    $pan = $calculator->calculate($case['input'])->toArray();
    $core['total']++;
    $result = $classify($pan['rigan'], $pan['rizhi'], $pan['sanchuan0']);
    if (! $result['match']) {
        if ($result['routes'] === ['day_ghost']) {
            $core['ghost_only']++;
        }

        continue;
    }
    $core['matched']++;
    $routes = $result['routes'];
    if (count($routes) === 3) {
        $core['all_three']++;
    } elseif ($routes === ['day_ghost', 'stem_tomb']) {
        $core['ghost_and_stem_tomb']++;
    } elseif ($routes === ['day_ghost', 'branch_tomb']) {
        $core['ghost_and_branch_tomb']++;
    }
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
        $pan = $calculator->calculate($datetime)->toArray();
        $year['total']++;
        $result = $classify($pan['rigan'], $pan['rizhi'], $pan['sanchuan0']);
        if (! $result['match']) {
            if ($result['routes'] === ['day_ghost']) {
                $year['ghost_only']++;
            }

            continue;
        }
        $year['matched']++;
        $routes = $result['routes'];
        $key = null;
        if (count($routes) === 3) {
            $key = 'all_three';
            $year['all_three']++;
        } elseif ($routes === ['day_ghost', 'stem_tomb']) {
            $key = 'ghost_and_stem_tomb';
            $year['ghost_and_stem_tomb']++;
        } elseif ($routes === ['day_ghost', 'branch_tomb']) {
            $key = 'ghost_and_branch_tomb';
            $year['ghost_and_branch_tomb']++;
        }
        if ($key !== null && $examples[$key] === null) {
            $examples[$key] = [
                'datetime' => $datetime,
                'day_ganzhi' => $tiangan[$pan['rigan']].$dizhi[$pan['rizhi']],
                'sanchuan' => [$dizhi[$pan['sanchuan0']], $dizhi[$pan['sanchuan1']], $dizhi[$pan['sanchuan2']]],
                'shizhi' => $dizhi[$pan['shizhi']],
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
