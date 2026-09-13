<?php

/** 文件作用：统计 720 基础结构、2026 男女合法出生组合下的严格龙战及宽集合对照。 */

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
foreach (range(1950, 2025) as $year) {
    $birth = "{$year}-06-01 12:00:00";
    $births[] = ['datetime' => $birth, 'year_index' => $calculator->calculate($birth)->get('nian_index')];
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

echo json_encode([
    'timezone' => 'Asia/Shanghai / 北京固定 UTC+8', 'hours' => $hours,
    '720_structure' => $counts,
    'people_sampling' => ['births' => '1950-2025 每年6月1日12:00（76个合法出生样本）', 'year' => 2026, 'by_gender' => $people],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
