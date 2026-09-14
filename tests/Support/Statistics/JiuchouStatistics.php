<?php

/** 文件作用：只读统计九丑正式 matcher、丑发用及 2026 真实时间中的正文严格元素。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JiuchouRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$calculator = new PanCalculator;
$rule = new JiuchouRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$scan = static function (DateTimeImmutable $start, DateTimeImmutable $end) use ($calculator, $rule, $hours): array {
    $counts = ['total' => 0, 'matched' => 0, 'four_zhong_time' => 0, 'chou_fayong' => 0, 'strict' => 0, 'non_strict' => 0];
    $examples = ['strict' => null, 'non_strict' => null];
    for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
        foreach ($hours as $hour) {
            $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
            $counts['total']++;
            $match = $rule->match(PanFacts::from($calculator->calculate($datetime)));
            if ($match === null) {
                continue;
            }
            $counts['matched']++;
            $counts['four_zhong_time'] += (int) $match->evidence['four_zhong_time'];
            $counts['chou_fayong'] += (int) $match->evidence['chou_fayong'];
            $counts['strict'] += (int) $match->evidence['strict_daquan_form'];
            $counts['non_strict'] += (int) ! $match->evidence['strict_daquan_form'];
            $key = $match->evidence['strict_daquan_form'] ? 'strict' : 'non_strict';
            $examples[$key] ??= ['datetime' => $datetime, 'day_ganzhi' => $match->evidence['day_ganzhi'], 'hour' => $match->evidence['hour_branch'], 'initial' => $match->evidence['initial']];
        }
    }
    $counts['ratio'] = $counts['total'] === 0 ? 0 : $counts['matched'] / $counts['total'];

    return ['counts' => $counts, 'examples' => $examples];
};

// 720 核心口径严格复用冻结 fixture 的 12 种天地盘关系 × 60 日干支生产输入。
$core = ['total' => 0, 'matched' => 0, 'chou_fayong' => 0];
foreach (PanRegression::loadFixture()['cases'] as $case) {
    $core['total']++;
    $match = $rule->match(PanFacts::from($calculator->calculate($case['input'])));
    if ($match !== null) {
        $core['matched']++;
        $core['chou_fayong'] += (int) $match->evidence['chou_fayong'];
    }
}

$year = $scan(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2027-01-01'));
echo json_encode(['timezone' => 'Asia/Shanghai', 'hours' => $hours, 'core_720' => $core, 'year_2026' => $year, 'case_candidates' => $year['examples']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
