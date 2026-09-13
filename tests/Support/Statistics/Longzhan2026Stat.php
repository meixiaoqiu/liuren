<?php

/** 文件作用：统计 720 基础结构、全部60出生年柱状态下的2026男女严格龙战及宽集合对照。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\LongzhanRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$rule = new LongzhanRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$births = [];
foreach (range(1900, 2025) as $year) {
    $birth = "{$year}-06-01 12:00:00";
    $yearIndex = $calculator->calculate($birth)->get('nian_index');
    if (is_int($yearIndex) && ! isset($births[$yearIndex])) {
        $births[$yearIndex] = ['datetime' => $birth, 'year_index' => $yearIndex];
    }
}
ksort($births);
if (array_keys($births) !== range(0, 59)) {
    throw new RuntimeException('无法为全部60个出生年柱状态找到唯一真实出生日期。');
}

$counts = ['total_720' => 0, 'mao_structure' => 0, 'you_structure' => 0, 'structure_total' => 0];
for ($day = 0; $day < 60; $day++) {
    for ($shift = 0; $shift < 12; $shift++) {
        $date = (new DateTimeImmutable('2026-01-01'))->modify("+{$day} days");
        $pan = $calculator->calculate($date->format('Y-m-d').' '.sprintf('%02d:00:00', $hours[$shift]));
        $counts['total_720']++;
        if ($pan->get('rizhi') === 3 && $pan->get('sanchuan0') === 3) {
            $counts['mao_structure']++;
        }
        if ($pan->get('rizhi') === 9 && $pan->get('sanchuan0') === 9) {
            $counts['you_structure']++;
        }
    }
}
$counts['structure_total'] = $counts['mao_structure'] + $counts['you_structure'];

$annualPans = [];
for ($date = new DateTimeImmutable('2026-01-01'), $end = new DateTimeImmutable('2027-01-01'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $annualPans[] = $calculator->calculate($date->format('Y-m-d').' '.sprintf('%02d:00:00', $hour));
    }
}

$people = [];
foreach (['male', 'female'] as $gender) {
    $strict = $wide = $wideOnly = $samples = 0;
    foreach ($births as $birth) {
        foreach ($annualPans as $pan) {
            $fate = $fateCalculator->calculate($birth['year_index'], $pan->get('nian_index'), $gender);
            $withPerson = new PanResult([...$pan->toArray(), 'context' => ['people' => [['role' => 'querent', ...$fate]]]]);
            $facts = PanFacts::from($withPerson);
            $isStrict = $rule->match($facts) !== null;
            $isWide = in_array($pan->get('rizhi'), [3, 9], true)
                && in_array($pan->get('sanchuan0'), [3, 9], true)
                && in_array($fate['xingnian'], [3, 9], true);
            $samples++;
            $strict += (int) $isStrict;
            $wide += (int) $isWide;
            $wideOnly += (int) ($isWide && ! $isStrict);
        }
    }
    $people[$gender] = compact('samples', 'strict', 'wide', 'wideOnly');
}

$expectedSamples = 60 * 365 * 12;
if ($people['male']['samples'] !== $expectedSamples || $people['female']['samples'] !== $expectedSamples) {
    throw new RuntimeException('人物组合统计分母不等于60×365×12。');
}
foreach (['male', 'female'] as $gender) {
    if ($people[$gender]['wideOnly'] !== $people[$gender]['wide'] - $people[$gender]['strict']) {
        throw new RuntimeException("{$gender} 的宽口径差集统计不闭合。");
    }
}
if ($people['male'] !== $people['female']) {
    throw new RuntimeException('完整60年柱状态枚举后，男女统计应完全相等。');
}

echo json_encode([
    'timezone' => 'Asia/Shanghai / 北京固定 UTC+8', 'hours' => $hours,
    '720_structure' => $counts,
    'people_sampling' => [
        'birth_year_indexes' => array_keys($births),
        'representative_births' => array_values($births),
        'year' => 2026,
        'by_gender' => $people,
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
