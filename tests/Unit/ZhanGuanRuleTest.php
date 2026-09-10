<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\ZhanGuanRule;
use App\Services\PanCalculator;

function zhanguan_match(PanResult $pan): mixed
{
    return (new ZhanGuanRule)->match(PanFacts::from($pan));
}

function zhanguan_synthetic(
    int $stem,
    int $branch,
    int $initial,
    int $middle,
    int $final,
    int $dayUpper,
    int $branchUpper,
): PanResult {
    $lodgings = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];
    $tianpan = range(0, 11);
    $tianpan[$lodgings[$stem]] = $dayUpper;
    $tianpan[$branch] = $branchUpper;

    return new PanResult([
        'rigan' => $stem,
        'rizhi' => $branch,
        'tianpan' => $tianpan,
        'sanchuan0' => $initial,
        'sanchuan1' => $middle,
        'sanchuan2' => $final,
    ]);
}

test('zhan-guan matches the modern yang-hai zi-shi reproducible case', function () {
    $pan = (new PanCalculator)->calculate('2026-01-01 01:00:00');
    $match = zhanguan_match($pan);

    expect($match)->not->toBeNull()
        ->and($match->evidence['day_stem'])->toBe(1)   // 乙
        ->and($match->evidence['day_branch'])->toBe(11) // 亥
        ->and($match->evidence['day_stem_lodging_branch'])->toBe(4) // 辰
        ->and($match->evidence['initial'])->toBe(4)   // 辰
        ->and($match->evidence['is_tian_gang'])->toBeTrue()
        ->and($match->evidence['is_tian_kui'])->toBeFalse()
        ->and($match->evidence['on_day_stem'])->toBeTrue()
        ->and($match->evidence['on_day_branch'])->toBeFalse();
});

test('zhan-guan rule engine integration: 2026-01-01 01:00 produces lesson.zhan_guan', function () {
    $pan = (new PanCalculator)->calculate('2026-01-01 01:00:00');
    $engine = new PanRuleEngine(new RuleRegistry);
    $matches = $engine->evaluate($pan);
    $codes = array_map(fn ($m) => $m->code, $matches);

    expect($codes)->toContain('lesson.zhan_guan');
});

test('zhan-guan matches via day stem when tian-gang rides the stem lodging', function () {
    // 戊日 寄巳(5); 让天盘巳位 = 辰(天罡): 干上 = 辰 = 天罡
    // 支=午, 让天盘午位 = 午(非天罡非天魁, 不影响路径)
    $pan = zhanguan_synthetic(4, 6, 4, 8, 0, 4, 6);

    $match = zhanguan_match($pan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['is_tian_gang'])->toBeTrue()
        ->and($match->evidence['on_day_stem'])->toBeTrue()
        ->and($match->evidence['on_day_branch'])->toBeFalse();
});

test('zhan-guan matches via day branch when tian-kui rides the day branch', function () {
    // 戊日 寄巳(5); 让天盘巳位 = 午(非魁罡): 干上 = 午
    // 支=午, 让天盘午位 = 戌(天魁): 支上 = 戌
    // 初传=戌
    $pan = zhanguan_synthetic(4, 6, 10, 9, 8, 6, 10);

    $match = zhanguan_match($pan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['is_tian_kui'])->toBeTrue()
        ->and($match->evidence['on_day_branch'])->toBeTrue()
        ->and($match->evidence['on_day_stem'])->toBeFalse();
});

test('zhan-guan matches both day stem and day branch simultaneously', function () {
    // 乙日 寄辰(4); 干上 = 辰, 支=辰, 支上 = 辰
    // 初传=辰
    $pan = zhanguan_synthetic(1, 4, 4, 4, 4, 4, 4);

    $match = zhanguan_match($pan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['is_tian_gang'])->toBeTrue()
        ->and($match->evidence['on_day_stem'])->toBeTrue()
        ->and($match->evidence['on_day_branch'])->toBeTrue();
});

test('zhan-guan rejects when initial is not kui or gang', function () {
    // 初传=午（普通支）→ 必须拒
    $pan = zhanguan_synthetic(4, 6, 6, 0, 4, 6, 10);
    expect(zhanguan_match($pan))->toBeNull();
});

test('zhan-guan rejects when tian-gang does not ride day stem nor day branch', function () {
    // 乙日 寄辰; 干上 = 卯（非辰）, 支=亥, 支上 = 戌（非辰）
    // 初传=辰（天罡），但既不加日干也不加日支 → 必须拒
    $pan = zhanguan_synthetic(1, 11, 4, 3, 5, 3, 10);
    expect(zhanguan_match($pan))->toBeNull();
});

test('zhan-guan rejects when tian-kui does not ride day stem nor day branch', function () {
    // 乙日 寄辰; 干上 = 卯（非戌）, 支=亥, 支上 = 子（非戌）
    // 初传=戌（天魁），但既不加日干也不加日支 → 必须拒
    $pan = zhanguan_synthetic(1, 11, 10, 7, 8, 3, 0);
    expect(zhanguan_match($pan))->toBeNull();
});

test('zhan-guan rejects when initial is empty (null initial)', function () {
    $pan = new PanResult([
        'rigan' => 4, 'rizhi' => 6, 'tianpan' => range(0, 11),
        'sanchuan0' => 4, 'sanchuan1' => 4, 'sanchuan2' => 4,
    ]);
    // 改 sanchuan0 为非魁罡
    $data = $pan->toArray();
    $data['sanchuan0'] = 6;
    expect(zhanguan_match(new PanResult($data)))->toBeNull();
});

test('zhan-guan evidence exposes required keys and uncovered list', function () {
    $pan = (new PanCalculator)->calculate('2026-01-01 01:00:00');
    $match = zhanguan_match($pan);

    expect($match->evidence)->toHaveKeys([
        'day_stem', 'day_branch', 'day_stem_lodging_branch',
        'day_upper', 'branch_upper', 'initial',
        'is_tian_gang', 'is_tian_kui',
        'on_day_stem', 'on_day_branch', 'uncovered',
    ])->and($match->evidence['uncovered'])->toBeArray()
        ->and($match->evidence['uncovered'])->not->toBeEmpty();
});

test('zhan-guan returns null when core facts are missing', function () {
    expect(zhanguan_match(new PanResult([])))->toBeNull();
});

test('zhan-guan returns null when tianpan is missing the day branch position (P2 结构校验)', function () {
    // 干寄宫位有合法上神，但日支位 tianpan[rizhi] 缺失 → 支上神不能写入 evidence，应 null
    $pan = new PanResult([
        'rigan' => 0,   // 甲 寄寅
        'rizhi' => 2,   // 寅
        'tianpan' => array_replace(range(0, 11), [2 => '__broken__']),
        'sanchuan0' => 10,
        'sanchuan1' => 10,
        'sanchuan2' => 10,
    ]);
    expect(zhanguan_match($pan))->toBeNull();
});

test('zhan-guan returns null when tianpan day branch position is not int (P2 结构校验)', function () {
    // 日支位天盘值是合法但非整型（项目里出现过的弱类型异常路径）→ 应 null，不写 evidence
    $pan = new PanResult([
        'rigan' => 0,
        'rizhi' => 2,
        'tianpan' => array_replace(range(0, 11), [2 => '10']),
        'sanchuan0' => 10,
        'sanchuan1' => 10,
        'sanchuan2' => 10,
    ]);
    expect(zhanguan_match($pan))->toBeNull();
});

test('zhan-guan rule metadata exposes correct constants', function () {
    $pan = (new PanCalculator)->calculate('2026-01-01 01:00:00');
    $match = zhanguan_match($pan);

    expect($match->code)->toBe('lesson.zhan_guan')
        ->and($match->name)->toBe('斩关课')
        ->and($match->group)->toBe('六十四课')
        ->and($match->gua)->toBe('遁')
        ->and($match->xiang)->toBe('关梁逾越，最利逃亡。捉贼难获，出行自强。病讼凶祸，厌祷吉详。书符合药，方法最良。')
        ->and($match->guaSymbol)->toBe('䷠');
});
