<?php

use App\Domain\Pan\BiFa\Rules\QuanSheBuZhengRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 2031 全年 4380 样本扫描：
 *   - 命中率约 1/12；
 *   - 十个天干均有命中；
 *   - 命中样本 sike[5] === DAY_LU[rigan]，非命中样本绝不被误报。
 */
test('2031 全年样本主体命中率与 1/12 接近且十干全部命中', function () {
    $calculator = new PanCalculator;
    $rule = new QuanSheBuZhengRule;

    $total = 0;
    $matched = 0;
    $byStem = array_fill(0, 10, 0);
    $patterns = [
        'plain' => 0, 'tombed' => 0, 'controlled' => 0, 'drained' => 0,
        'tombed+controlled' => 0, 'tombed+drained' => 0, 'controlled+drained' => 0,
        'tombed+controlled+drained' => 0,
    ];

    for ($day = 1; $day <= 365; $day++) {
        $date = (new DateTimeImmutable('2031-01-01'))->modify('+'.($day - 1).' days');
        foreach ([1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23] as $hour) {
            $total++;
            $dt = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $pan = $calculator->calculate($dt);
            $facts = PanFacts::from($pan);
            $match = $rule->match($facts);
            if ($match === null) {
                continue;
            }
            $matched++;
            $byStem[$facts->get('rigan')]++;
            $t = $match->evidence['lu_tombed_by_branch'];
            $c = $match->evidence['lu_controlled_by_branch'];
            $d = $match->evidence['lu_drained_by_branch'];
            $key = ($t ? 'tombed' : '')
                .($c ? ($t ? '+controlled' : 'controlled') : '')
                .($d ? ($t || $c ? '+drained' : 'drained') : '');
            if ($key === '') {
                $key = 'plain';
            }
            $patterns[$key] = ($patterns[$key] ?? 0) + 1;
        }
    }

    expect($total)->toBe(4380)
        ->and($matched)->toBeGreaterThan(360)
        ->and($matched)->toBeLessThan(370)
        ->and(abs($matched / $total - 1 / 12))->toBeLessThan(0.001, '命中率应接近 1/12');

    foreach ($byStem as $i => $c) {
        expect($c)->toBeGreaterThan(30, "stem={$i} 必须有命中");
        expect($c)->toBeLessThan(45, "stem={$i} 命中数偏高");
    }

    expect($patterns['plain'])->toBeGreaterThan(150)
        ->and($patterns['tombed'])->toBeGreaterThan(10)
        ->and($patterns['controlled'])->toBeGreaterThan(50)
        ->and($patterns['drained'])->toBeGreaterThan(60)
        ->and($patterns['tombed+controlled'])->toBeGreaterThan(0, '应至少出现墓+克重叠案例')
        ->and($patterns['tombed+drained'])->toBeGreaterThan(0, '应至少出现墓+脱重叠案例');
});

test('所有命中样本的 day_lu 都严格等于支上神', function () {
    $calculator = new PanCalculator;
    $rule = new QuanSheBuZhengRule;
    $bad = [];

    // 抽样 53 个日期 × 4 个时辰，加速到 200 余样本，验证不变量。
    for ($day = 1; $day <= 365; $day += 7) {
        $date = (new DateTimeImmutable('2031-01-01'))->modify('+'.($day - 1).' days');
        foreach ([1, 7, 13, 19] as $hour) {
            $dt = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $pan = $calculator->calculate($dt);
            $facts = PanFacts::from($pan);
            $match = $rule->match($facts);
            if ($match === null) {
                continue;
            }
            if ($match->evidence['day_lu'] !== $match->evidence['branch_upper']) {
                $bad[] = "$dt day_lu={$match->evidence['day_lu']} branch_upper={$match->evidence['branch_upper']}";
            }
        }
    }

    expect($bad)->toBe([], '命中样本必须满足 day_lu === branch_upper：'.implode(';', $bad));
});

test('非命中样本不会被规则误报', function () {
    $calculator = new PanCalculator;
    $rule = new QuanSheBuZhengRule;
    $falsePositive = 0;
    $sampled = 0;

    for ($day = 1; $day <= 365; $day += 13) {
        $date = (new DateTimeImmutable('2031-01-01'))->modify('+'.($day - 1).' days');
        foreach ([1, 7, 13, 19] as $hour) {
            $sampled++;
            $dt = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $pan = $calculator->calculate($dt);
            $facts = PanFacts::from($pan);
            $match = $rule->match($facts);
            if ($match !== null) {
                $expectedLu = [2, 3, 5, 6, 5, 6, 8, 9, 11, 0][$facts->get('rigan')];
                $actual = $facts->get('sike')[5] ?? null;
                if ($actual !== $expectedLu) {
                    $falsePositive++;
                }
            }
        }
    }

    expect($falsePositive)->toBe(0, '非命中样本被误报');
    expect($sampled)->toBeGreaterThan(0);
});
