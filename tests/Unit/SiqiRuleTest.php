<?php

/** 文件作用：锁定死奇课"天罡发用 + 辰为四课上神之一"严格口径，覆盖 4 条路线与全部 judgment 的边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\SiqiRule;
use App\Services\PanCalculator;

/**
 * 构造最小可用 PanFacts。
 *
 * - 默认 sike 已使 sike[1]=辰（命中路线 1）。
 * - 默认 sanchuan0=4（命中天罡发用）。
 * - 默认 tianpan 为恒等映射，tianjiang 全部为青龙 5。
 * - 默认 rigan=0(甲)/rizhi=0(子)/nianzhi=0/yuejiang=0(子)。
 */
function siqi_facts(array $changes = []): PanFacts
{
    $base = [
        'calculationTime' => '2000-01-01 12:00:00',
        'rigan' => 0,
        'rizhi' => 0,
        'nianzhi' => 0,
        'yuejiang' => 0,
        'sanchuan0' => 4,
        'sanchuan1' => 8,
        'sanchuan2' => 0,
        'sike' => [0, 4, 0, 5, 0, 6, 0, 7],
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 5),
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('siqi rule code is lesson.siqi and metadata is stable', function () {
    $rule = new SiqiRule;
    expect($rule->code())->toBe('lesson.siqi');
    $registry = new RuleRegistry;
    $codes = array_map(fn ($r) => $r->code(), $registry->rules());
    expect(array_search('lesson.siqi', $codes, true))->toBe(array_search('lesson.longzhan', $codes, true) + 1);
});

test('all four sike upper routes match independently', function (int $sikeIndex) {
    // 4 条路线分别命中：sike[1]/sike[3]/sike[5]/sike[7] = 辰，其他位置避免是辰。
    $sike = [0, 4, 0, 5, 0, 6, 0, 7];
    $sike[$sikeIndex] = 4;
    $sike[1] = $sikeIndex === 1 ? 4 : 0;
    $sike[3] = $sikeIndex === 3 ? 4 : 5;
    $sike[5] = $sikeIndex === 5 ? 4 : 6;
    $sike[7] = $sikeIndex === 7 ? 4 : 7;
    $match = (new SiqiRule)->match(siqi_facts(['sike' => $sike]));
    expect($match)->not->toBeNull();
    $polarities = array_map(fn ($r) => $r['polarity'], $match->evidence['routes']);
    expect($polarities)->toBe([match (true) {
        $sikeIndex === 1 => '日阳',
        $sikeIndex === 3 => '日阴',
        $sikeIndex === 5 => '辰阳',
        $sikeIndex === 7 => '辰阴',
    }]);
})->with([
    '日阳（第1课）' => [1],
    '日阴（第2课）' => [3],
    '辰阳（第3课）' => [5],
    '辰阴（第4课）' => [7],
]);

test('initial not being gang rejects the match', function () {
    expect((new SiqiRule)->match(siqi_facts(['sanchuan0' => 0])))->toBeNull();
});

test('gang in sike but not as initial rejects the match', function () {
    expect((new SiqiRule)->match(siqi_facts(['sanchuan0' => 0, 'sike' => [0, 4, 0, 5, 0, 6, 0, 7]])))->toBeNull();
});

test('initial is gang but gang is not in any sike upper rejects the match', function () {
    // sike 上神全部非辰。
    $sike = [0, 0, 0, 1, 0, 2, 0, 3];
    expect((new SiqiRule)->match(siqi_facts(['sike' => $sike])))->toBeNull();
});

test('multiple sike upper routes are preserved in evidence', function () {
    // 第 1 课 + 第 3 课同时含辰。
    $sike = [0, 4, 0, 5, 0, 4, 0, 7];
    $match = (new SiqiRule)->match(siqi_facts(['sike' => $sike]));
    expect($match)->not->toBeNull();
    expect(array_map(fn ($r) => $r['polarity'], $match->evidence['routes']))->toBe(['日阳', '辰阳']);
});

test('four simultaneous sike upper routes are all recorded', function () {
    $sike = [0, 4, 0, 4, 0, 4, 0, 4];
    $match = (new SiqiRule)->match(siqi_facts(['sike' => $sike]));
    expect($match)->not->toBeNull();
    expect(array_map(fn ($r) => $r['polarity'], $match->evidence['routes']))->toBe(['日阳', '日阴', '辰阳', '辰阴']);
});

test('gang ground position uses tianpan lookup', function () {
    // tianpan[地盘] = 天盘支；array_search(4, tianpan, true) 即天罡辰所临地盘。
    // 在 [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3] 中，辰(4) 位于 index 0（地盘子）。
    $tianpan = [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3];
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan]));
    expect($match)->not->toBeNull();
    expect($match->evidence['gang_ground'])->toBe(0);
});

test('gang riding white tiger fires ominous judgment', function () {
    $tianjiang = array_fill(0, 12, 5);
    $tianjiang[0] = 7; // 地盘子乘白虎
    $tianpan = [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3]; // 辰临地盘子
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan, 'tianjiang' => $tianjiang]));
    $codes = array_map(fn ($j) => $j['code'], $match?->evidence['judgments'] ?? []);
    expect($codes)->toContain('gang_rides_white_tiger');
    $detail = collect($match->evidence['judgments'])->firstWhere('code', 'gang_rides_white_tiger');
    expect($detail['effect'])->toBe('ominous')->and($detail['label'])->toBe('天罡乘白虎');
});

test('gang at day stem lodging fires day judgment without changing matcher', function () {
    // rigan=甲(0) 寄寅(2)；让辰临地盘寅。
    $tianpan = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1];
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan, 'rigan' => 0]));
    $codes = array_map(fn ($j) => $j['code'], $match?->evidence['judgments'] ?? []);
    expect($codes)->toContain('gang_at_day');
    expect($match?->code)->toBe('lesson.siqi');
});

test('gang at day branch fires branch judgment', function () {
    // rizhi=子(0)；让辰临地盘子。
    $tianpan = [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3];
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan, 'rizhi' => 0]));
    $codes = array_map(fn ($j) => $j['code'], $match?->evidence['judgments'] ?? []);
    expect($codes)->toContain('gang_at_branch');
});

test('gang at year branch fires year judgment', function () {
    // nianzhi=子(0)；让辰临地盘子。
    $tianpan = [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3];
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan, 'nianzhi' => 0]));
    $codes = array_map(fn ($j) => $j['code'], $match?->evidence['judgments'] ?? []);
    expect($codes)->toContain('gang_at_year');
});

test('meng zhong ji phase judgments cover all twelve branches', function (int $ground, string $phase) {
    // 构造合法 tianpan：让辰(4) 出现在 $ground 位上，其余位置放其他不重复的天盘支。
    $tianpan = [-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1];
    $tianpan[$ground] = 4;
    $fill = 0;
    for ($i = 0; $i < 12; $i++) {
        if ($tianpan[$i] === -1) {
            while ($fill === 4 || in_array($fill, $tianpan, true)) {
                $fill++;
            }
            $tianpan[$i] = $fill++;
        }
    }
    $match = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpan]));
    expect($match)->not->toBeNull();
    $codes = array_map(fn ($j) => $j['code'], $match->evidence['judgments'] ?? []);
    expect($codes)->toContain("gang_phase_$phase");
})->with([
    '孟寅' => [2, '孟'],
    '孟巳' => [5, '孟'],
    '孟申' => [8, '孟'],
    '孟亥' => [11, '孟'],
    '仲子' => [0, '仲'],
    '仲卯' => [3, '仲'],
    '仲午' => [6, '仲'],
    '仲酉' => [9, '仲'],
    '季丑' => [1, '季'],
    '季辰' => [4, '季'],
    '季未' => [7, '季'],
    '季戌' => [10, '季'],
]);

test('yuejiang equal to gang fires siqi_huiguang auspicious judgment', function () {
    $match = (new SiqiRule)->match(siqi_facts(['yuejiang' => 4]));
    $judgment = collect($match?->evidence['judgments'] ?? [])->firstWhere('code', 'siqi_huiguang');
    expect($judgment)->not->toBeNull();
    expect($judgment['effect'])->toBe('auspicious');
    expect($judgment['label'])->toBe('死奇回光');
});

test('yuejiang not equal to gang does not fire huiguang', function () {
    expect(collect((new SiqiRule)->match(siqi_facts(['yuejiang' => 0]))?->evidence['judgments'] ?? [])
        ->pluck('code'))->not->toContain('siqi_huiguang');
});

test('additive ominous/auspicious conditions do not change matcher', function () {
    // 死奇课 matcher 仅依赖"初传=辰"与"辰为四课上神"；
    // 白虎、临日辰岁、回光等附加凶吉条件全部不参与 matcher。
    // 任何在 siqi_facts() 默认基础上附加触发 judgment 的字段，都不应改变"命中 + code"。

    $baseline = (new SiqiRule)->match(siqi_facts());
    expect($baseline)->not->toBeNull();
    expect($baseline->code)->toBe('lesson.siqi');

    // 关闭白虎的版本不应改变 matcher。
    $tianjiangNoTiger = array_fill(0, 12, 5); // 全部青龙，无白虎
    $matchNoTiger = (new SiqiRule)->match(siqi_facts(['tianjiang' => $tianjiangNoTiger]));
    expect($matchNoTiger?->code)->toBe('lesson.siqi');
    $codesNoTiger = array_map(fn ($j) => $j['code'], $matchNoTiger->evidence['judgments'] ?? []);
    expect($codesNoTiger)->not->toContain('gang_rides_white_tiger');

    // 临日 / 临辰 / 临岁触发与否都不影响 matcher。
    // rigan=甲(0) 寄寅(2)；让辰临地盘寅 → 临日。
    $tianpanAtDay = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1];
    $matchAtDay = (new SiqiRule)->match(siqi_facts(['tianpan' => $tianpanAtDay, 'rigan' => 0]));
    expect($matchAtDay?->code)->toBe('lesson.siqi');
    $codesAtDay = array_map(fn ($j) => $j['code'], $matchAtDay->evidence['judgments'] ?? []);
    expect($codesAtDay)->toContain('gang_at_day');

    // 月将=辰 触发回光，但 matcher 仍只检查"初传=辰 + 辰为四课上神"。
    $matchHuiguang = (new SiqiRule)->match(siqi_facts(['yuejiang' => 4]));
    expect($matchHuiguang?->code)->toBe('lesson.siqi');
    $codesHuiguang = array_map(fn ($j) => $j['code'], $matchHuiguang->evidence['judgments'] ?? []);
    expect($codesHuiguang)->toContain('siqi_huiguang');

    // 移除白虎 + 月将不变回光 → 仅 phase judgment 必然存在。
    $matchOnly = (new SiqiRule)->match(siqi_facts(['tianjiang' => $tianjiangNoTiger, 'yuejiang' => 0]));
    expect($matchOnly?->code)->toBe('lesson.siqi');
    // 任何合法死奇盘必含一个 phase judgment（孟仲季覆盖十二支）
    $phaseCodes = ['gang_phase_孟', 'gang_phase_仲', 'gang_phase_季'];
    $codesOnly = array_map(fn ($j) => $j['code'], $matchOnly->evidence['judgments'] ?? []);
    expect(array_intersect($phaseCodes, $codesOnly))->not->toBeEmpty();
});

test('evidence structure includes all required keys', function () {
    $match = (new SiqiRule)->match(siqi_facts());
    expect($match?->evidence)->toHaveKeys([
        'initial', 'sike_upper_branches', 'routes', 'route_indexes', 'gang_ground', 'foundations', 'judgments', 'uncovered',
    ]);
});

test('production calculator can locate classic calendar prerequisites', function () {
    $calculator = new PanCalculator;
    $rule = new SiqiRule;
    // 寻找 2000-2026 期间真正同时满足"甲子日 + 丑时 + 月将=辰"的可执行现代日期；
    // 仅证明古例干支 + 节气组合可被生产算法定位到。
    // 由于生产盘不一定命中死奇课（《大全》古例为"辰加子为用、三传辰申子"，本测试不强求逐位一致），
    // 因此 Catalog 已将该 case 标为 reference_only，本测试不复现"古例三传"。
    $found = null;
    for ($year = 2000; $year <= 2026 && $found === null; $year++) {
        for ($month = 9; $month <= 10 && $found === null; $month++) {
            $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
            for ($day = 1; $day <= $daysInMonth && $found === null; $day++) {
                for ($hour = 1; $hour <= 3 && $found === null; $hour++) {
                    $datetime = sprintf('%04d-%02d-%02d %02d:00:00', $year, $month, $day, $hour);
                    $pan = $calculator->calculate($datetime);
                    $facts = PanFacts::from($pan);
                    if ($facts->get('rigan') === 0 && $facts->get('rizhi') === 0
                        && $facts->get('shizhi') === 1 && $facts->get('yuejiang') === 4) {
                        $found = ['datetime' => $datetime, 'match' => $rule->match($facts)];
                    }
                }
            }
        }
    }
    expect($found)->not->toBeNull();
    // 只验证：日历前提（甲子日、丑时、月将=辰）真实可达。
    // 不再断言"古例三传辰申子"已复现；那需要专门校勘并独立加 executable 测试。
    expect($found)->toHaveKeys(['datetime', 'match']);
});

test('production calculator reproduces lesson 2 route via authentic pan', function () {
    $calculator = new PanCalculator;
    $rule = new SiqiRule;
    // 寻找一条生产盘覆盖第二课（sike[3]=辰）路线。
    $found = null;
    for ($year = 2000; $year <= 2026 && $found === null; $year++) {
        for ($month = 1; $month <= 12 && $found === null; $month++) {
            $daysInMonth = (int) date('t', strtotime("$year-$month-01"));
            for ($day = 1; $day <= $daysInMonth && $found === null; $day++) {
                for ($hour = 0; $hour < 24 && $found === null; $hour++) {
                    $datetime = sprintf('%04d-%02d-%02d %02d:00:00', $year, $month, $day, $hour);
                    $pan = $calculator->calculate($datetime);
                    $facts = PanFacts::from($pan);
                    $match = $rule->match($facts);
                    if ($match !== null) {
                        $lessons = array_map(fn ($r) => $r['lesson'], $match->evidence['routes']);
                        if (in_array(2, $lessons, true)) {
                            $found = ['datetime' => $datetime, 'match' => $match];
                        }
                    }
                }
            }
        }
    }
    expect($found)->not->toBeNull();
    $polarities = array_map(fn ($r) => $r['polarity'], $found['match']->evidence['routes']);
    expect($polarities)->toContain('日阴');
});
