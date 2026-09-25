<?php

use App\Domain\Pan\BiFa\Rules\QuanSheBuZhengRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 2031 全年 4380 样本扫描：
 *   - 命中率约 1/12（已精确冻结为 365 / 4380，因生产 23:00 换日覆盖 366 个生产日柱）；
 *   - 十个天干均有命中（精确分布已锁定）；
 *   - 命中样本 sike[5] === DAY_LU[rigan]，非命中样本绝不被误报；
 *   - 八个互斥组合桶已精确冻结到总数 365。
 *
 * 数字与研究文档 docs/毕法/08-权摄不正禄临支.md 严格对齐；任何 `PanCalculator`、
 * 日界口径或 matcher 调整导致数字漂移时，本测试必须第一时间红灯，提示文档重新研究。
 */
test('2031 全年样本精确命中数 365 与十干精确分布', function () {
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

    // 总样本与总命中精确冻结。
    expect($total)->toBe(4380)
        ->and($matched)->toBe(365)
        ->and(abs($matched / $total - 1 / 12))->toBeLessThan(0.001, '命中率应接近 1/12');

    // 十干精确分布冻结（与 docs/毕法/08-权摄不正禄临支.md 第十二节一致）。
    expect($byStem)->toBe([
        0 => 36, // 甲
        1 => 37, // 乙
        2 => 37, // 丙
        3 => 36, // 丁
        4 => 36, // 戊
        5 => 36, // 己
        6 => 36, // 庚
        7 => 37, // 辛
        8 => 37, // 壬
        9 => 37, // 癸
    ]);

    // 八个互斥组合桶精确冻结；六桶合计 365 等于总命中。
    expect($patterns)->toBe([
        'plain' => 193,
        'tombed' => 13,
        'controlled' => 66,
        'drained' => 75,
        'tombed+controlled' => 6,
        'tombed+drained' => 12,
        'controlled+drained' => 0,
        'tombed+controlled+drained' => 0,
    ]);
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
