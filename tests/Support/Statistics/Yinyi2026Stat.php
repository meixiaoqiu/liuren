<?php

/**
 * 文件作用：用生产 PanCalculator 复算并锁定 2026 年淫泆课及狡童、泆女附格统计。
 * 运行方式：php tests/Support/Statistics/Yinyi2026Stat.php
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JiaotongRule;
use App\Domain\Pan\Rules\YinvRule;
use App\Domain\Pan\Rules\YinyiRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$yinyiRule = new YinyiRule;
$jiaotongRule = new JiaotongRule;
$yinvRule = new YinvRule;
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$maoyou = [3, 9];
$houhe = [3, 11];
$counts = [
    'total' => 0,
    'initial_maoyou' => 0,
    'initial_houhe' => 0,
    'strict_yinyi' => 0,
    'any_transmission_maoyou_houhe' => 0,
    'split_maoyou_and_houhe_in_transmissions' => 0,
    'split_only_no_same_position' => 0,
    'mao_liuhe' => 0,
    'you_liuhe' => 0,
    'mao_tianhou' => 0,
    'you_tianhou' => 0,
    'jiaotong_total' => 0,
    'jiaotong_with_strict_yinyi' => 0,
    'jiaotong_without_strict_yinyi' => 0,
    'yinv_total' => 0,
    'yinv_with_strict_yinyi' => 0,
    'yinv_without_strict_yinyi' => 0,
];

for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $counts['total']++;
        $pan = $calculator->calculate($date->setTime($hour, 0)->format('Y-m-d H:i:s'));
        $facts = PanFacts::from($pan);
        $transmissions = [$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')];
        $generals = array_map(static fn (int $branch): ?int => $facts->generalRidingBranch($branch), $transmissions);
        $initialMaoyou = in_array($transmissions[0], $maoyou, true);
        $initialHouhe = in_array($generals[0], $houhe, true);
        $strict = $yinyiRule->match($facts) !== null;
        $samePosition = array_any([0, 1, 2], static fn (int $index): bool => in_array($transmissions[$index], $maoyou, true)
            && in_array($generals[$index], $houhe, true));
        $split = array_any($transmissions, static fn (int $branch): bool => in_array($branch, $maoyou, true))
            && array_any($generals, static fn (?int $general): bool => in_array($general, $houhe, true));
        $jiaotong = $jiaotongRule->match($facts) !== null;
        $yinv = $yinvRule->match($facts) !== null;

        $counts['initial_maoyou'] += (int) $initialMaoyou;
        $counts['initial_houhe'] += (int) $initialHouhe;
        $counts['strict_yinyi'] += (int) $strict;
        $counts['any_transmission_maoyou_houhe'] += (int) $samePosition;
        $counts['split_maoyou_and_houhe_in_transmissions'] += (int) $split;
        $counts['split_only_no_same_position'] += (int) ($split && ! $samePosition);
        $counts['mao_liuhe'] += (int) ($transmissions[0] === 3 && $generals[0] === 3);
        $counts['you_liuhe'] += (int) ($transmissions[0] === 9 && $generals[0] === 3);
        $counts['mao_tianhou'] += (int) ($transmissions[0] === 3 && $generals[0] === 11);
        $counts['you_tianhou'] += (int) ($transmissions[0] === 9 && $generals[0] === 11);
        $counts['jiaotong_total'] += (int) $jiaotong;
        $counts['jiaotong_with_strict_yinyi'] += (int) ($jiaotong && $strict);
        $counts['jiaotong_without_strict_yinyi'] += (int) ($jiaotong && ! $strict);
        $counts['yinv_total'] += (int) $yinv;
        $counts['yinv_with_strict_yinyi'] += (int) ($yinv && $strict);
        $counts['yinv_without_strict_yinyi'] += (int) ($yinv && ! $strict);
    }
}

$expected = [
    'total' => 4380,
    'initial_maoyou' => 685,
    'initial_houhe' => 885,
    'strict_yinyi' => 128,
    'any_transmission_maoyou_houhe' => 275,
    'split_maoyou_and_houhe_in_transmissions' => 647,
    'split_only_no_same_position' => 372,
    'mao_liuhe' => 44,
    'you_liuhe' => 42,
    'mao_tianhou' => 23,
    'you_tianhou' => 19,
    'jiaotong_total' => 99,
    'jiaotong_with_strict_yinyi' => 17,
    'jiaotong_without_strict_yinyi' => 82,
    'yinv_total' => 70,
    'yinv_with_strict_yinyi' => 4,
    'yinv_without_strict_yinyi' => 66,
];

echo json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

if ($counts !== $expected) {
    $differences = [];
    foreach ($expected as $field => $value) {
        if ($counts[$field] !== $value) {
            $differences[$field] = ['expected' => $value, 'actual' => $counts[$field]];
        }
    }
    throw new RuntimeException('Yinyi 2026 frozen statistics mismatch: '.json_encode($differences, JSON_UNESCAPED_UNICODE));
}
