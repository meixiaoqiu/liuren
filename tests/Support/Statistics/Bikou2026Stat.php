<?php

/**
 * 文件作用：只读统计闭口课三个正文候选入口在 720 核心盘与 2026 全年的命中规模，
 * 并搜索《六壬大全》甲申日卯时子将课例的真实日期。
 * 运行方式：php tests/Support/Statistics/Bikou2026Stat.php
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\BikouRule;
use App\Domain\Pan\Rules\YixunZhoubianRule;
use App\Services\PanCalculator;
use com\tyme\solar\SolarDay;

$calculator = new PanCalculator;
$rule = new BikouRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$countRange = static function (array $datetimes) use ($calculator, $rule): array {
    $yixunRule = new YixunZhoubianRule;
    $counts = ['total' => 0, 'hit' => 0, 'tail' => 0, 'head_xuanwu' => 0, 'head_upper_xuanwu' => 0, 'yixun_zhoubian' => 0, 'bikou_yixun_intersection' => 0, 'yixun_paths' => []];
    foreach ($datetimes as $datetime) {
        $counts['total']++;
        $pan = $calculator->calculate($datetime);
        $facts = PanFacts::from($pan);
        $isYixunZhoubian = $yixunRule->match($facts) !== null;
        $counts['yixun_zhoubian'] += (int) $isYixunZhoubian;

        $match = $rule->match($facts);
        if ($match === null) {
            continue;
        }
        $counts['hit']++;
        $counts['tail'] += (int) $match->evidence['tail_on_head'];
        $counts['head_xuanwu'] += (int) $match->evidence['head_riding_xuanwu'];
        $counts['head_upper_xuanwu'] += (int) $match->evidence['head_upper_riding_xuanwu'];
        if ($isYixunZhoubian) {
            $counts['bikou_yixun_intersection']++;
            $path = implode('+', $match->evidence['paths']);
            $counts['yixun_paths'][$path] = ($counts['yixun_paths'][$path] ?? 0) + 1;
        }
    }

    return $counts;
};

$datesByDayIndex = [];
for ($date = new DateTimeImmutable('2000-01-01'); count($datesByDayIndex) < 60; $date = $date->modify('+1 day')) {
    $cycle = SolarDay::fromYmd((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))->getLunarDay()->getSixtyCycle();
    $stem = $cycle->getHeavenStem()->getIndex();
    $branch = $cycle->getEarthBranch()->getIndex();
    for ($index = 0; $index < 60; $index++) {
        if ($index % 10 === $stem && $index % 12 === $branch) {
            $datesByDayIndex[$index] = $date->format('Y-m-d');
            break;
        }
    }
}
ksort($datesByDayIndex);
$core = [];
foreach ($datesByDayIndex as $date) {
    foreach ($hours as $hour) {
        $core[] = sprintf('%s %02d:00:00', $date, $hour);
    }
}

$year = [];
for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $year[] = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
    }
}

foreach (['720 核心盘' => $countRange($core), '2026 全年十二时辰' => $countRange($year)] as $label => $counts) {
    echo "【{$label}】\n";
    echo json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
}

$examples = [];
$yixunExamples = [];
for ($date = new DateTimeImmutable('1900-01-01'); $date <= new DateTimeImmutable('2100-12-31'); $date = $date->modify('+1 day')) {
    $cycle = SolarDay::fromYmd((int) $date->format('Y'), (int) $date->format('m'), (int) $date->format('d'))->getLunarDay()->getSixtyCycle();
    $stem = $cycle->getHeavenStem()->getIndex();
    $branch = $cycle->getEarthBranch()->getIndex();
    if (! (($stem === 0 && $branch === 8) || ($stem === 1 && $branch === 7))) {
        continue;
    }
    $datetime = $date->setTime(5, 0)->format('Y-m-d H:i:s');
    $pan = $calculator->calculate($datetime);
    if ($stem === 0 && $pan->get('yuejiang') === 0
        && $pan->get('sanchuan0') === 5 && ($pan->get('tianpan')[8] ?? null) === 5) {
        $examples[] = $datetime;
    }
    if ($stem === 1 && $pan->get('yuejiang') === 2
        && (new YixunZhoubianRule)->match(PanFacts::from($pan)) !== null) {
        $yixunExamples[] = $datetime;
    }
}
echo "【甲申日卯时子将·巳加申发用】\n";
echo '命中数: '.count($examples)."\n";
echo '最早命中: '.($examples[0] ?? '无')."\n";
echo "【乙未日卯时寅将·一旬周遍格】\n";
echo '命中数: '.count($yixunExamples)."\n";
echo '最早命中: '.($yixunExamples[0] ?? '无')."\n";
