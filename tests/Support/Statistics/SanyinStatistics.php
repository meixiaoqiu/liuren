<?php

/** 文件作用：固定占人出生资料，逐层统计 2026 年 4380 个代表时刻的三阴课条件与正式命中数。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\SanyinRule;
use App\Services\PanCalculator;

$year = (int) ($argv[1] ?? 2026);
$birth = $argv[2] ?? '1959-08-01T00:00';
$gender = $argv[3] ?? 'male';
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$rule = new SanyinRule;
$birthYearIndex = $calculator->calculate(str_replace('T', ' ', $birth).':00')->get('nian_index');
$counts = array_fill_keys([
    'total', 'nobleman_reverse', 'reverse_and_stem_rear', 'reverse_and_both_rear',
    'initial_qiu', 'initial_si', 'initial_white_tiger', 'initial_black_tortoise',
    'time_restrains_xingnian', 'reverse_both_rear_and_qiu_si',
    'reverse_both_rear_qiu_si_and_initial_xuanhu', 'matched',
], 0);
$matches = [];

for ($date = new DateTimeImmutable("{$year}-01-01"), $end = $date->modify('+1 year'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $pan = $calculator->calculate($datetime);
        $fate = $fateCalculator->calculate($birthYearIndex, $pan->get('nian_index'), $gender);
        $pan = new PanResult([...$pan->toArray(), 'context' => ['people' => [[
            'role' => 'querent', 'birth_datetime' => $birth, 'gender' => $gender, ...$fate,
        ]]]]);
        $facts = PanFacts::from($pan);
        $counts['total']++;
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $time = $facts->get('shizhi');
        $lodging = is_int($stem) ? $facts->stemLodgingBranch($stem) : null;
        $reverse = $facts->isNoblemanMovingBackward();
        $stemRear = is_int($lodging) && $facts->isGroundPositionRidingNoblemanRearGeneral($lodging);
        $branchRear = is_int($branch) && $facts->isGroundPositionRidingNoblemanRearGeneral($branch);
        $state = is_int($initial) ? $facts->branchSeasonalState($initial) : null;
        $general = is_int($initial) ? $facts->generalRidingBranch($initial) : null;
        $timeElement = is_int($time) ? $facts->branchElement($time) : null;
        $yearElement = $facts->branchElement($fate['xingnian']);
        $timeRestrains = is_int($timeElement) && is_int($yearElement) && $yearElement === ($timeElement + 2) % 5;
        if ($reverse) {
            $counts['nobleman_reverse']++;
        }
        if ($reverse && $stemRear) {
            $counts['reverse_and_stem_rear']++;
        }
        if ($reverse && $stemRear && $branchRear) {
            $counts['reverse_and_both_rear']++;
        }
        if ($state === '囚') {
            $counts['initial_qiu']++;
        }
        if ($state === '死') {
            $counts['initial_si']++;
        }
        if ($general === 7) {
            $counts['initial_white_tiger']++;
        }
        if ($general === 9) {
            $counts['initial_black_tortoise']++;
        }
        if ($timeRestrains) {
            $counts['time_restrains_xingnian']++;
        }
        $qiuSi = in_array($state, ['囚', '死'], true);
        if ($reverse && $stemRear && $branchRear && $qiuSi) {
            $counts['reverse_both_rear_and_qiu_si']++;
        }
        $throughXuanHu = $reverse && $stemRear && $branchRear && $qiuSi && in_array($general, [7, 9], true);
        if ($throughXuanHu) {
            $counts['reverse_both_rear_qiu_si_and_initial_xuanhu']++;
        }
        if (($match = $rule->match($facts)) !== null) {
            $counts['matched']++;
            if (count($matches) < 10) {
                $matches[] = ['datetime' => $datetime, 'day' => [$stem, $branch], 'initial' => $initial, 'general' => $general, 'state' => $state, 'xingnian' => $fate['xingnian']];
            }
        }
    }
}

echo json_encode(['year' => $year, 'timezone' => 'Asia/Shanghai / 北京固定 UTC+8', 'hours' => $hours, 'querent' => ['birth_datetime' => $birth, 'gender' => $gender], 'counts' => $counts, 'first_matches' => $matches], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
