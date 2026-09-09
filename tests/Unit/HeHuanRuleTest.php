<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\HeHuanRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Services\PanCalculator;

function hehuan_pan_for(string $datetime): PanResult
{
    return (new PanCalculator)->calculate($datetime);
}

function hehuan_match(PanResult $pan): mixed
{
    return (new HeHuanRule)->match(PanFacts::from($pan));
}

function hehuan_full_pan(string $panDatetime, string $birthDatetime, string $gender): PanResult
{
    $calc = new PanCalculator;
    $pan = $calc->calculate($panDatetime);
    $birthPan = $calc->calculate($birthDatetime);
    $fate = (new FateCalculator)->calculate(
        $birthPan->get('nian_index'),
        $pan->get('nian_index'),
        $gender,
    );
    $context = ['people' => [[
        'role' => 'querent',
        'birth_datetime' => $birthDatetime,
        'gender' => $gender,
        'nianming' => $fate['nianming'],
        'xingnian' => $fate['xingnian'],
        'xingnian_gan' => $fate['xingnian_gan'],
    ]]];

    return new PanResult([...$pan->toArray(), 'context' => $context]);
}

/**
 * 通用合成器：直接由日柱干支、xunIndex 自动协调。
 *
 * 参数：
 *  - $dayStem, $dayBranch: 日柱干支
 *  - $initial, $middle, $final: 三传
 *  - $dayUpper: 干上神
 *  - $nmBranch, $xnBranch: 本命/行年位置
 *  - $nmGeneral, $xnGeneral: 本命/行年天将（默认贵人、青龙）
 */
function hehuan_synthetic_pan(
    int $dayStem,
    int $dayBranch,
    int $initial,
    int $middle,
    int $final,
    int $dayUpper,
    int $nmBranch,
    int $xnBranch,
    int $nmGeneral = 0,
    int $xnGeneral = 5,
): PanResult {
    $stemLodging = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1][$dayStem];
    $tianpan = range(0, 11);
    $tianpan[$stemLodging] = $dayUpper;
    $tianpan[$nmBranch] = $nmBranch;
    $tianpan[$xnBranch] = $xnBranch;

    $fullTianjiang = array_fill(0, 12, 2);
    $fullTianjiang[$nmBranch] = $nmGeneral;
    $fullTianjiang[$xnBranch] = $xnGeneral;

    $i = $dayStem;
    while ($i % 12 !== $dayBranch) {
        $i += 10;
    }

    return new PanResult([
        'rigan' => $dayStem,
        'rizhi' => $dayBranch,
        'tianpan' => $tianpan,
        'tianjiang' => $fullTianjiang,
        'sanchuan0' => $initial,
        'sanchuan1' => $middle,
        'sanchuan2' => $final,
        'yuezhi' => 8,
        'yuejiang' => 8,
        'guirenPeriod' => 'day',
        'context' => [
            'people' => [[
                'role' => 'querent',
                'birth_datetime' => '1984-06-01T00:00:00',
                'gender' => 'male',
                'nianming' => $nmBranch,
                'xingnian' => $xnBranch,
                'xingnian_gan' => 0,
            ]],
        ],
        'nian_index' => 16,
    ]);
}

// 六合有序对 12 方向覆盖已于重构时迁移到 tests/Unit/BranchRelationsTest：
//   - liuhe contains exactly the six orthodox unordered pairs
//   - liuhe accepts all twelve directions and rejects every other ordered pair
// 本文件不再独立维护 LIUHE_PAIRS 列表。

test('he-huan reproduces the daquan wu-shen day shen-jiang case with supplied context', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $match = hehuan_match($pan);

    expect($match)->not->toBeNull()
        ->and($match->evidence['day_stem'])->toBe(4)
        ->and($match->evidence['day_branch'])->toBe(8)
        ->and($match->evidence['day_upper'])->toBe(1)
        ->and($match->evidence['day_upper_stem'])->toBe(9)
        ->and($match->evidence['day_upper_stem_hexed'])->toBeTrue()
        ->and($match->evidence['initial'])->toBe(0)
        ->and($match->evidence['middle'])->toBe(8)
        ->and($match->evidence['final'])->toBe(4)
        ->and($match->evidence['initial_hexes_upper'])->toBeTrue()
        ->and($match->evidence['sanchuan_sanhe'])->toBeTrue()
        ->and($match->evidence['sanchuan_sanhe_triple'])->toBe([0, 4, 8])
        ->and($match->evidence['nianming']['auspicious'])->toBeTrue()
        ->and($match->evidence['xingnian']['auspicious'])->toBeTrue();
});

test('he-huan rule engine integration: 1959 male case 2000-06-19 00:00 produces lesson.he_huan', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $engine = new PanRuleEngine(new RuleRegistry);
    $matches = $engine->evaluate($pan);
    $codes = array_map(fn ($m) => $m->code, $matches);

    expect($codes)->toContain('lesson.he_huan');
});

// 正文最小严格口径：六合与三合必须同时成立。

test('he-huan rejects when initial breaks 六合 (六合不成立的独立反例)', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $data = $pan->toArray();
    // 初传改为午，午与干上丑 不构成六合
    $data['sanchuan0'] = 6;
    expect(hehuan_match(new PanResult($data)))->toBeNull();
});

test('he-huan rejects when day chen breaks 三合 (三合不成立的独立反例)', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $data = $pan->toArray();
    // 三传改为 子卯未（不构成完整三合）
    $data['sanchuan0'] = 0;
    $data['sanchuan1'] = 3;
    $data['sanchuan2'] = 7;
    expect(hehuan_match(new PanResult($data)))->toBeNull();
});

test('he-huan rejects when 命/行年 lack auspicious generals', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $data = $pan->toArray();
    $data['tianjiang'] = [2, 4, 6, 1, 2, 4, 6, 1, 2, 4, 6, 1];
    expect(hehuan_match(new PanResult($data)))->toBeNull();
});

test('he-huan rejects when day stem does not produce hex via upper branch', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $data = $pan->toArray();
    $data['rigan'] = 0;
    expect(hehuan_match(new PanResult($data)))->toBeNull();
});

test('he-huan requiredContext reports nianming and xingnian fields', function () {
    $rule = new HeHuanRule;

    expect($rule->requiredContext())->toContain('people.querent.nianming', 'people.querent.xingnian')
        ->and($rule->notEvaluatedInfo())->toMatchArray([
            'name' => '合欢课',
            'notice' => '需要占人本命与行年信息（出生时间与性别），当前未进行判断。',
        ]);
});

test('he-huan returns null when person context missing (querent absent)', function () {
    $pan = (new PanCalculator)->calculate('2000-06-19 00:00:00');
    expect(hehuan_match($pan))->toBeNull();
});

test('he-huan evidence includes required keys and uncovered list non-empty', function () {
    $pan = hehuan_full_pan('2000-06-19 00:00:00', '1959-06-21 12:00:00', 'male');
    $match = hehuan_match($pan);

    expect($match)->not->toBeNull()
        ->and($match->evidence)->toHaveKeys([
            'day_stem', 'day_branch', 'day_stem_lodging_branch',
            'day_upper', 'day_upper_stem', 'day_upper_stem_hexed',
            'initial', 'middle', 'final',
            'initial_hexes_upper', 'sanchuan_sanhe', 'sanchuan_sanhe_triple',
            'nianming', 'xingnian', 'uncovered',
        ])
        ->and($match->evidence['uncovered'])->toBeArray()
        ->and($match->evidence['uncovered'])->not->toBeEmpty();
});

// 支三合发用：初传必须参与，第三支可来自中传或末传。

test('he-huan matches when day branch initial and middle form 申子辰', function () {
    // 戊申日 索引 44, xunIndex=4, xunHead=4 (甲辰旬)
    // 戊寄巳(5), 干上=丑(1): 寄宫=(1-4+12)%12=9=癸 → 戊癸合 ✓
    // 初传=子(0): 子丑六合 ✓
    // 日支申 + 初传子 + 中传辰 → 申子辰；末传申不参与。
    // 命=亥(11), 行=未(7): 调 tianjiang[11]=0 (贵人), tianjiang[7]=5 (青龙)
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 0, middle: 4, final: 8,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    $match = hehuan_match($pan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['sanchuan_sanhe_triple'])->toBe([0, 4, 8])
        ->and($match->evidence['initial_hexes_upper'])->toBeTrue();
});

test('he-huan matches when day branch initial and final form 申子辰', function () {
    // 日支申 + 初传子 + 末传辰 → 申子辰；中传午不参与。
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 0, middle: 6, final: 4,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );

    $match = hehuan_match($pan);

    expect($match)->not->toBeNull()
        ->and($match->evidence['sanchuan_sanhe_triple'])->toBe([0, 4, 8]);
});

// P1 算法边界：三合自成一局但日支不在 → 必须拒
test('he-huan algorithm rejects 寅午戌 (P1 边界: 三合自成一局但日支不在)', function () {
    // 三传=寅午戌（完整三合）但日支=申 → 申不在 寅午戌 中
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 2, middle: 6, final: 10,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});

test('he-huan algorithm rejects 亥卯未 (P1 边界)', function () {
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 3, middle: 7, final: 11,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});

test('he-huan algorithm rejects 巳酉丑 (P1 边界: SANHE_TRIPLES 已规范化)', function () {
    // P1 bug 修复前：SANHE_TRIPLES 中 [5, 9, 1] 永远不可能命中。
    // 修复后 [1, 5, 9]，但本测试是"拒"——三传=巳酉丑 配 日支=申 → 应拒
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 1, middle: 5, final: 9,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});

test('he-huan algorithm rejects 申子辰 三合 配 非申日支 (P1 边界)', function () {
    // 三传=申子辰 但日支=寅 → 寅不在申子辰中
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 2,
        initial: 0, middle: 4, final: 8,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});

// AND 逻辑独立正例
test('he-huan rejects when only 六合 holds (三传与日支不成三合)', function () {
    // 子丑六合 OK，但日支=申 + 三传=子午卯（不构成三合）→ AND 拒
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 0, middle: 6, final: 3,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});

test('he-huan rejects when only 三合 holds (initial 不与 dayUpper 六合)', function () {
    // 三传=申子辰 OK，但初传=寅(2) + 干上=丑(1) 不构成六合 → AND 拒
    $pan = hehuan_synthetic_pan(
        dayStem: 4, dayBranch: 8,
        initial: 2, middle: 0, final: 4,
        dayUpper: 1,
        nmBranch: 11, xnBranch: 7,
    );
    expect(hehuan_match($pan))->toBeNull();
});
