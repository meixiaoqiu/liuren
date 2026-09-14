<?php

/** 文件作用：只读统计励德课在真实生产排盘中的命中率与四种完整分型，不修改任何 fixture。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LideRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$calculator = new PanCalculator;
$rule = new LideRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$counts = [
    'total' => 0,
    'lide' => 0,
    'weifu' => 0,
    'cuotuo' => 0,
    'yang_front_yin_rear' => 0,
    'yin_front_yang_rear' => 0,
    'mixed' => 0,
];
$examples = [
    'weifu' => null,
    'cuotuo' => null,
    'yang_front_yin_rear' => null,
    'yin_front_yang_rear' => null,
    'mixed' => null,
];

$cur = new DateTimeImmutable('2026-01-01');
$end = new DateTimeImmutable('2027-01-01');

while ($cur < $end) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $cur->format('Y-m-d'), $hour);
        $panResult = $calculator->calculate($datetime);
        $facts = PanFacts::from($panResult);
        $counts['total']++;

        $match = $rule->match($facts);
        if ($match === null) {
            continue;
        }

        $counts['lide']++;
        $pattern = $match->evidence['pattern'] ?? null;
        if (is_string($pattern) && array_key_exists($pattern, $counts)) {
            $counts[$pattern]++;
        }

        if (is_string($pattern) && array_key_exists($pattern, $examples) && $examples[$pattern] === null) {
            $pan = $panResult->toArray();
            $examples[$pattern] = [
                'datetime' => $datetime,
                'day' => (PanCalculator::$tiangan[$pan['rigan']] ?? '?').(PanCalculator::$dizhi[$pan['rizhi']] ?? '?'),
                'nobleman_ground' => PanCalculator::$dizhi[$match->evidence['nobleman_ground']] ?? '?',
                'pattern' => $pattern,
            ];
        }
    }

    $cur = $cur->modify('+1 day');
}

$counts['ratio'] = $counts['total'] === 0 ? 0 : $counts['lide'] / $counts['total'];

echo json_encode([
    'timezone' => 'Asia/Shanghai / production Beijing daylight boundary',
    'hours' => $hours,
    'year_2026' => $counts,
    'first_examples' => $examples,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
