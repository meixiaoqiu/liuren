<?php

/**
 * 第六法「六阴相继尽昏迷」古籍四日四课验证与年度命中审计。
 * 运行：php tests/Support/Statistics/BiFa06Statistics.php [输出路径]
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$rule = new LiuYinXiangJiRule;
$expected = ['癸未/卯' => false, '癸巳/卯' => false, '癸卯/卯' => false, '辛卯/子' => false];
$strictCombinations = [];
$jiMaoBoundary = [];
$jiaChenBoundary = null;
$seen = [];

// 六十日 × 十二时辰形成 720 个生产排盘样本；其中有 717 个唯一日课/干上组合。
for ($date = new DateTimeImmutable('2031-01-01'); $date <= new DateTimeImmutable('2031-03-01'); $date = $date->modify('+1 day')) {
    foreach ([1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23] as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $sike = $facts->get('sike');
        if (! is_array($sike) || ! isset($sike[1])) {
            continue;
        }
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $key = implode('/', [$stem, $branch, $sike[1]]);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $match = $rule->match($facts);
        $lessonGenerations = $match?->evidence['lesson_generations'] ?? [];
        $allLessonsGenerate = $lessonGenerations === [true, true, true, true];
        $strict = in_array('source_exhausted_root_severed', $match?->matchedRoutes ?? [], true);
        if ($strict) {
            $combo = PanCalculator::$tiangan[$stem].PanCalculator::$dizhi[$branch].'/'.PanCalculator::$dizhi[$sike[1]];
            $strictCombinations[$combo] = $datetime;
            $expected[$combo] = true;
        }
        if ($stem === 5 && $branch === 3 && in_array('six_yin', $match?->matchedRoutes ?? [], true)) {
            $jiMaoBoundary[] = [
                'datetime' => $datetime, 'sike' => $sike,
                'sanchuan' => [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')],
                'allLessonsGenerate' => $allLessonsGenerate, 'strict' => $strict,
            ];
        }
        if ($stem === 0 && $branch === 4 && $sike[1] === 6) {
            $jiaChenBoundary = [
                'datetime' => $datetime, 'sike' => $sike,
                'sanchuan' => [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')],
                'lessonGenerations' => $lessonGenerations, 'allLessonsGenerate' => $allLessonsGenerate, 'strict' => $strict,
            ];
        }
    }
}

$result = compact('expected', 'strictCombinations', 'jiMaoBoundary', 'jiaChenBoundary');
$output = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
if (isset($argv[1])) {
    file_put_contents($argv[1], $output);
    echo "written to {$argv[1]}\n";
} else {
    echo $output;
}
