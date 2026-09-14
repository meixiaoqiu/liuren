<?php

/** 文件作用：只读统计鬼墓课正式 matcher、三条入口的独立与组合命中，并搜索真实生产案例。 */

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

// 与 GuimuRule 同源以避免重新声明常量的同步漂移。
$dayGhosts = [
    0 => [8], 1 => [9], 2 => [0], 3 => [11], 4 => [2], 5 => [3],
    6 => [6], 7 => [5], 8 => [4, 10], 9 => [1, 7],
];
$stemTomb = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];
$branchTomb = [4, 4, 7, 7, 4, 10, 10, 4, 1, 1, 4, 4];

$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$dizhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
$tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

// 直接调用规则逻辑（不重新走 PanFacts）以减少单盘开销。
$classify = static function (int $rigan, int $rizhi, int $initial) use ($dayGhosts, $stemTomb, $branchTomb): array {
    $ghosts = $dayGhosts[$rigan] ?? [];
    $isGhost = in_array($initial, $ghosts, true);
    $isStemTomb = $initial === $stemTomb[$rigan];
    $isBranchTomb = $initial === $branchTomb[$rizhi];
    if (! ($isGhost || $isStemTomb || $isBranchTomb)) {
        return [null, false];
    }
    $routes = [];
    if ($isGhost) {
        $routes[] = 'day_ghost';
    }
    if ($isStemTomb) {
        $routes[] = 'stem_tomb';
    }
    if ($isBranchTomb) {
        $routes[] = 'branch_tomb';
    }
    $combined = $isGhost && ($isStemTomb || $isBranchTomb);

    return [$routes, $combined];
};

// 720 核心口径严格复用冻结 fixture 的 12 种天地盘关系 × 60 日干支生产输入。
$core = [
    'total' => 0, 'matched' => 0,
    'day_ghost' => 0, 'stem_tomb' => 0, 'branch_tomb' => 0,
    'day_ghost_only' => 0, 'stem_tomb_only' => 0, 'branch_tomb_only' => 0,
    'day_ghost+stem_tomb' => 0, 'day_ghost+branch_tomb' => 0, 'stem_tomb+branch_tomb' => 0,
    'all_three' => 0, 'ghost_tomb_combined' => 0,
];
foreach (PanRegression::loadFixture()['cases'] as $case) {
    $pan = $calculator->calculate($case['input'])->toArray();
    $core['total']++;
    [$routes, $combined] = $classify($pan['rigan'], $pan['rizhi'], $pan['sanchuan0']);
    if ($routes === null) {
        continue;
    }
    $core['matched']++;
    $core['day_ghost'] += (int) in_array('day_ghost', $routes, true);
    $core['stem_tomb'] += (int) in_array('stem_tomb', $routes, true);
    $core['branch_tomb'] += (int) in_array('branch_tomb', $routes, true);
    $core['ghost_tomb_combined'] += (int) $combined;
    $key = match (true) {
        count($routes) === 3 => 'all_three',
        $routes === ['day_ghost', 'stem_tomb'] => 'day_ghost+stem_tomb',
        $routes === ['day_ghost', 'branch_tomb'] => 'day_ghost+branch_tomb',
        $routes === ['stem_tomb', 'branch_tomb'] => 'stem_tomb+branch_tomb',
        $routes === ['day_ghost'] => 'day_ghost_only',
        $routes === ['stem_tomb'] => 'stem_tomb_only',
        $routes === ['branch_tomb'] => 'branch_tomb_only',
        default => null,
    };
    if ($key !== null) {
        $core[$key]++;
    }
}
$core['ratio'] = $core['total'] === 0 ? 0 : $core['matched'] / $core['total'];

// 2026 全年：4380 盘。
$year = [
    'total' => 0, 'matched' => 0,
    'day_ghost' => 0, 'stem_tomb' => 0, 'branch_tomb' => 0,
    'day_ghost_only' => 0, 'stem_tomb_only' => 0, 'branch_tomb_only' => 0,
    'day_ghost+stem_tomb' => 0, 'day_ghost+branch_tomb' => 0, 'stem_tomb+branch_tomb' => 0,
    'all_three' => 0, 'ghost_tomb_combined' => 0,
];
$examples = [
    'day_ghost_only' => null, 'stem_tomb_only' => null, 'branch_tomb_only' => null,
    'day_ghost+stem_tomb' => null, 'day_ghost+branch_tomb' => null,
    'stem_tomb+branch_tomb' => null, 'all_three' => null,
];

$cur = new DateTimeImmutable('2026-01-01');
$end = new DateTimeImmutable('2027-01-01');
while ($cur < $end) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $cur->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime)->toArray();
        $year['total']++;
        [$routes, $combined] = $classify($pan['rigan'], $pan['rizhi'], $pan['sanchuan0']);
        if ($routes === null) {
            continue;
        }
        $year['matched']++;
        $year['day_ghost'] += (int) in_array('day_ghost', $routes, true);
        $year['stem_tomb'] += (int) in_array('stem_tomb', $routes, true);
        $year['branch_tomb'] += (int) in_array('branch_tomb', $routes, true);
        $year['ghost_tomb_combined'] += (int) $combined;
        $key = match (true) {
            count($routes) === 3 => 'all_three',
            $routes === ['day_ghost', 'stem_tomb'] => 'day_ghost+stem_tomb',
            $routes === ['day_ghost', 'branch_tomb'] => 'day_ghost+branch_tomb',
            $routes === ['stem_tomb', 'branch_tomb'] => 'stem_tomb+branch_tomb',
            $routes === ['day_ghost'] => 'day_ghost_only',
            $routes === ['stem_tomb'] => 'stem_tomb_only',
            $routes === ['branch_tomb'] => 'branch_tomb_only',
            default => null,
        };
        if ($key !== null) {
            $year[$key]++;
            if ($examples[$key] === null) {
                $examples[$key] = [
                    'datetime' => $datetime,
                    'day_ganzhi' => $tiangan[$pan['rigan']].$dizhi[$pan['rizhi']],
                    'sanchuan' => [$dizhi[$pan['sanchuan0']], $dizhi[$pan['sanchuan1']], $dizhi[$pan['sanchuan2']]],
                    'shizhi' => $dizhi[$pan['shizhi']],
                    'routes' => $routes,
                ];
            }
        }
    }
    $cur = $cur->modify('+1 day');
}
$year['ratio'] = $year['total'] === 0 ? 0 : $year['matched'] / $year['total'];

echo json_encode([
    'timezone' => 'Asia/Shanghai',
    'hours' => $hours,
    'core_720' => $core,
    'year_2026' => $year,
    'case_candidates' => $examples,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
