<?php

/** 文件作用：可重复枚举励德课 60 日 × 12 天地盘偏移 × 昼夜贵共 1440 个结构状态。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LideRule;
use App\Services\PanCalculator;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$rule = new LideRule;

// 与 PanCalculator::calculate() 当前起贵人表完全一致：每项依次为昼贵、夜贵的天盘地支。
$noblemanByStem = [
    [1, 7],
    [0, 8],
    [11, 9],
    [11, 9],
    [1, 7],
    [0, 8],
    [1, 7],
    [6, 2],
    [5, 3],
    [5, 3],
];

$normalize = static fn (int $value): int => (($value % 12) + 12) % 12;

$counts = [
    'denominator' => 0,
    'lide' => 0,
    'weifu' => 0,
    'cuotuo' => 0,
    'yang_front_yin_rear' => 0,
    'yin_front_yang_rear' => 0,
    'mixed' => 0,
];

foreach (PanCalculator::$jiazi2Ganzhi as [$stem, $branch]) {
    foreach (range(0, 11) as $offset) {
        $tianpan = [];
        foreach (range(0, 11) as $ground) {
            $tianpan[$ground] = ($offset + $ground) % 12;
        }

        $dayYang = $tianpan[PanCalculator::$jigong[$stem]];
        $dayYin = $tianpan[$dayYang];
        $branchYang = $tianpan[$branch];
        $branchYin = $tianpan[$branchYang];
        $sike = [$stem, $dayYang, $dayYang, $dayYin, $branch, $branchYang, $branchYang, $branchYin];

        foreach (['day' => 0, 'night' => 1] as $period => $periodIndex) {
            $noblemanBranch = $noblemanByStem[$stem][$periodIndex];
            $noblemanGround = array_search($noblemanBranch, $tianpan, true);
            $forward = ! in_array($noblemanGround, [5, 6, 7, 8, 9, 10], true);

            $tianjiang = [];
            foreach (range(0, 11) as $ground) {
                $tianjiang[$ground] = $forward
                    ? $normalize($offset - $noblemanBranch + $ground)
                    : $normalize($noblemanBranch - $ground - $offset);
            }

            $facts = PanFacts::from(new PanResult([
                'rigan' => $stem,
                'rizhi' => $branch,
                'tianpan' => $tianpan,
                'tianjiang' => $tianjiang,
                'sike' => $sike,
                'guirenPeriod' => $period,
            ]));

            $counts['denominator']++;
            $match = $rule->match($facts);
            if ($match === null) {
                continue;
            }

            $counts['lide']++;
            $pattern = $match->evidence['pattern'] ?? null;
            if (is_string($pattern) && array_key_exists($pattern, $counts)) {
                $counts[$pattern]++;
            }
        }
    }
}

$expected = [
    'denominator' => 1440,
    'lide' => 240,
    'weifu' => 16,
    'cuotuo' => 15,
    'yang_front_yin_rear' => 20,
    'yin_front_yang_rear' => 12,
    'mixed' => 177,
];

$matchesExpected = $counts === $expected;

echo json_encode([
    'universe' => '60 sexagenary days × 12 heaven-earth offsets × day/night nobleman',
    'counts' => $counts,
    'expected' => $expected,
    'matches_expected' => $matchesExpected,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;

exit($matchesExpected ? 0 : 1);
