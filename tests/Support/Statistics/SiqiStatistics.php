<?php

/** 文件作用：固定占人出生资料，逐层统计 2026 年 4380 个代表时刻的死奇课条件与正式命中数；并提供 720 fixture 复检。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\SiqiRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;
use Illuminate\Contracts\Console\Kernel;

$year = (int) ($argv[1] ?? 2026);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$rule = new SiqiRule;

$counts = array_fill_keys([
    'total',
    'initial_gang',
    'gang_in_any_sike_upper',
    'lesson1_route',
    'lesson2_route',
    'lesson3_route',
    'lesson4_route',
    'multi_route',
    'matched',
    'gang_rides_white_tiger',
    'gang_at_day',
    'gang_at_branch',
    'gang_at_year',
    'phase_meng',
    'phase_zhong',
    'phase_ji',
    'siqi_huiguang',
], 0);
$matches = [];

$initialGang = static function (PanFacts $facts): bool {
    return $facts->get('sanchuan0') === 4;
};

$gangInUpper = static function (PanFacts $facts): bool {
    $sike = $facts->get('sike');
    if (! is_array($sike) || count($sike) < 8) {
        return false;
    }
    foreach ([1, 3, 5, 7] as $idx) {
        if (($sike[$idx] ?? null) === 4) {
            return true;
        }
    }

    return false;
};

$gangGround = static function (PanFacts $facts): ?int {
    return $facts->heavenBranchGroundPosition(4);
};

// === 2026 年代表时刻统计 ===
for ($date = new DateTimeImmutable("{$year}-01-01"), $end = $date->modify('+1 year'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $counts['total']++;

        $initialIsGang = $initialGang($facts);
        $upperHasGang = $gangInUpper($facts);
        if ($initialIsGang) {
            $counts['initial_gang']++;
        }
        if ($upperHasGang) {
            $counts['gang_in_any_sike_upper']++;
        }

        $match = $rule->match($facts);
        if ($match !== null) {
            $counts['matched']++;
            $routes = $match->evidence['routes'] ?? [];
            $routeLessons = array_map(fn ($r) => $r['lesson'], $routes);
            foreach ([1, 2, 3, 4] as $lesson) {
                if (in_array($lesson, $routeLessons, true)) {
                    $counts["lesson{$lesson}_route"]++;
                }
            }
            if (count($routeLessons) >= 2) {
                $counts['multi_route']++;
            }
            $codes = array_map(fn ($j) => $j['code'], $match->evidence['judgments'] ?? []);
            if (in_array('gang_rides_white_tiger', $codes, true)) {
                $counts['gang_rides_white_tiger']++;
            }
            if (in_array('gang_at_day', $codes, true)) {
                $counts['gang_at_day']++;
            }
            if (in_array('gang_at_branch', $codes, true)) {
                $counts['gang_at_branch']++;
            }
            if (in_array('gang_at_year', $codes, true)) {
                $counts['gang_at_year']++;
            }
            if (in_array('gang_phase_孟', $codes, true)) {
                $counts['phase_meng']++;
            }
            if (in_array('gang_phase_仲', $codes, true)) {
                $counts['phase_zhong']++;
            }
            if (in_array('gang_phase_季', $codes, true)) {
                $counts['phase_ji']++;
            }
            if (in_array('siqi_huiguang', $codes, true)) {
                $counts['siqi_huiguang']++;
            }
            if (count($matches) < 10) {
                $matches[] = [
                    'datetime' => $datetime,
                    'rizhi' => $facts->get('rizhi'),
                    'shizhi' => $facts->get('shizhi'),
                    'yuejiang' => $facts->get('yuejiang'),
                    'routes' => array_map(fn ($r) => $r['polarity'], $routes),
                    'gang_ground' => $match->evidence['gang_ground'] ?? null,
                ];
            }
        }
    }
}

// === 720 fixture 复检 ===
$fixture = PanRegression::loadFixture();
$fixtureCases = $fixture['cases'] ?? [];
$regressionCounts = array_fill_keys([
    'total',
    'initial_gang',
    'gang_in_any_sike_upper',
    'matched',
    'lesson1_route',
    'lesson2_route',
    'lesson3_route',
    'lesson4_route',
], 0);

foreach ($fixtureCases as $case) {
    $expected = $case['expected'] ?? [];
    $regressionCounts['total']++;
    if (($expected['sanchuan0'] ?? null) === 4) {
        $regressionCounts['initial_gang']++;
    }
    $sike = [
        $expected['sike0'] ?? null,
        $expected['sike1'] ?? null,
        $expected['sike2'] ?? null,
        $expected['sike3'] ?? null,
        $expected['sike4'] ?? null,
        $expected['sike5'] ?? null,
        $expected['sike6'] ?? null,
        $expected['sike7'] ?? null,
    ];
    $hasGang = false;
    foreach ([1, 3, 5, 7] as $idx) {
        if ($sike[$idx] === 4) {
            $hasGang = true;
            break;
        }
    }
    if ($hasGang) {
        $regressionCounts['gang_in_any_sike_upper']++;
    }
    if (($expected['sanchuan0'] ?? null) === 4 && $hasGang) {
        $regressionCounts['matched']++;
        foreach ([1, 2, 3, 4] as $lesson) {
            if ($sike[($lesson * 2) - 1] === 4) {
                $regressionCounts["lesson{$lesson}_route"]++;
            }
        }
    }
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai / 北京固定 UTC+8',
    'hours' => $hours,
    'counts' => $counts,
    'first_matches' => $matches,
    'regression_720' => $regressionCounts,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
