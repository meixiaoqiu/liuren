<?php

/**
 * 文件作用：只读枚举 2026 全年十二代表时辰的三交基础结构与阴合检索口径。
 * 运行方式：php tests/Support/Statistics/Sanjiao2026Stat.php
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$zhong = [0, 3, 6, 9];
$yinHe = [10, 3];
$counts = [
    'total' => 0,
    'base_zhong_structure' => 0,
    'initial_yin_he' => 0,
    'transmission_yin_he' => 0,
    'lesson_or_transmission_zhong_yin_he' => 0,
    'lesson_or_transmission_any_yin_he' => 0,
    'lesson_only_zhong_yin_he' => 0,
    'excluded_non_zhong_yin_he' => 0,
];

for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $counts['total']++;
        $pan = $calculator->calculate($date->setTime($hour, 0)->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $sike = $pan->get('sike');
        $transmissions = [$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')];
        $base = in_array($pan->get('rizhi'), $zhong, true)
            && in_array($sike[5] ?? null, $zhong, true)
            && in_array($sike[7] ?? null, $zhong, true)
            && array_all($transmissions, static fn (mixed $branch): bool => in_array($branch, $zhong, true));

        if (! $base) {
            continue;
        }

        $counts['base_zhong_structure']++;
        $initialYinHe = in_array($facts->generalRidingBranch($transmissions[0]), $yinHe, true);
        $transmissionYinHe = array_any(
            $transmissions,
            static fn (int $branch): bool => in_array($facts->generalRidingBranch($branch), $yinHe, true),
        );
        $lessonOrTransmissionBranches = array_merge([$sike[1], $sike[3], $sike[5], $sike[7]], $transmissions);
        $lessonOrTransmissionZhongYinHe = array_any(
            $lessonOrTransmissionBranches,
            static fn (int $branch): bool => in_array($branch, $zhong, true)
                && in_array($facts->generalRidingBranch($branch), $yinHe, true),
        );
        $lessonOrTransmissionAnyYinHe = array_any(
            $lessonOrTransmissionBranches,
            static fn (int $branch): bool => in_array($facts->generalRidingBranch($branch), $yinHe, true),
        );
        $counts['initial_yin_he'] += (int) $initialYinHe;
        $counts['transmission_yin_he'] += (int) $transmissionYinHe;
        $counts['lesson_or_transmission_zhong_yin_he'] += (int) $lessonOrTransmissionZhongYinHe;
        $counts['lesson_or_transmission_any_yin_he'] += (int) $lessonOrTransmissionAnyYinHe;
        $counts['lesson_only_zhong_yin_he'] += (int) ($lessonOrTransmissionZhongYinHe && ! $transmissionYinHe);
        $counts['excluded_non_zhong_yin_he'] += (int) ($lessonOrTransmissionAnyYinHe && ! $lessonOrTransmissionZhongYinHe);
    }
}

echo json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
