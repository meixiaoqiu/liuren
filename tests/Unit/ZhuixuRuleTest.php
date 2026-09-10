<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZhuixuRule;
use App\Services\PanCalculator;

function zhuixu_match(PanResult $pan): mixed
{
    return (new ZhuixuRule)->match(PanFacts::from($pan));
}

test('zhuixu matches day branch on stem when the day branch issues', function () {
    $match = zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 10, 'sanchuan0' => 10,
        'sike' => [0, 10, 10, 6, 10, 6, 6, 2],
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['day_stem_controls_day_branch'])->toBeTrue()
        ->and($match->evidence['day_branch_on_stem'])->toBeTrue()
        ->and($match->evidence['day_branch_as_initial'])->toBeTrue()
        ->and($match->evidence['branch_path'])->toBeTrue()
        ->and($match->evidence['stem_path'])->toBeFalse()
        ->and($match->evidence['paths'])->toContain('支临干发用');
});

test('zhuixu matches stem on day branch when the stem lodging branch issues', function () {
    $match = zhuixu_match(new PanResult([
        'rigan' => 2, 'rizhi' => 8, 'sanchuan0' => 5,
        'sike' => [2, 2, 2, 11, 8, 5, 5, 2],
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['stem_lodging_branch'])->toBe(5)
        ->and($match->evidence['day_stem_controls_day_branch'])->toBeTrue()
        ->and($match->evidence['stem_on_day_branch'])->toBeTrue()
        ->and($match->evidence['stem_lodging_as_initial'])->toBeTrue()
        ->and($match->evidence['stem_path'])->toBeTrue()
        ->and($match->evidence['paths'])->toContain('干临支发用');
});

test('zhuixu rejects a day stem that does not control the day branch', function () {
    expect(zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 0,
        'sike' => [0, 0, 0, 1, 0, 1, 1, 2],
    ])))->toBeNull();
});

test('zhuixu does not accept day branch on stem unless the day branch issues', function () {
    expect(zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 10, 'sanchuan0' => 6,
        'sike' => [0, 10, 10, 6, 10, 6, 6, 2],
    ])))->toBeNull();
});

test('zhuixu does not accept stem on day branch unless the stem lodging branch issues', function () {
    expect(zhuixu_match(new PanResult([
        'rigan' => 2, 'rizhi' => 8, 'sanchuan0' => 2,
        'sike' => [2, 2, 2, 11, 8, 5, 5, 2],
    ])))->toBeNull();
});

test('zhuixu requires one of the two opposing placements', function () {
    expect(zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 10, 'sanchuan0' => 10,
        'sike' => [0, 9, 9, 5, 10, 6, 6, 2],
    ])))->toBeNull();
});

test('zhuixu does not treat the day branch in a later transmission as issuing', function () {
    expect(zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 10, 'sanchuan0' => 6, 'sanchuan1' => 10, 'sanchuan2' => 2,
        'sike' => [0, 10, 10, 6, 10, 6, 6, 2],
    ])))->toBeNull();
});

test('zhuixu reproduces the daquan jia-xu example with the production calculator', function () {
    $pan = (new PanCalculator)->calculate('2024-03-11 05:00:00');
    $match = zhuixu_match($pan);

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([0, 10, 3, 11])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([10, 6, 2])
        ->and($pan->get('sike')[1])->toBe(10)
        ->and($match)->not->toBeNull()
        ->and($match->evidence['branch_path'])->toBeTrue()
        ->and($match->evidence['stem_path'])->toBeFalse();
});

test('zhuixu reproduces the daquan bing-shen example with the production calculator', function () {
    $pan = (new PanCalculator)->calculate('2019-12-25 07:00:00');
    $match = zhuixu_match($pan);

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([2, 8, 4, 1])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([5, 2, 11])
        ->and($pan->get('sike')[5])->toBe(5)
        ->and($match)->not->toBeNull()
        ->and($match->evidence['stem_lodging_branch'])->toBe(5)
        ->and($match->evidence['stem_path'])->toBeTrue();
});

test('zhuixu exposes metadata and rejects missing facts', function () {
    $match = zhuixu_match(new PanResult([
        'rigan' => 0, 'rizhi' => 10, 'sanchuan0' => 10,
        'sike' => [0, 10, 10, 6, 10, 6, 6, 2],
    ]));

    expect($match->code)->toBe('lesson.zhuixu')
        ->and($match->name)->toBe('赘婿课')
        ->and($match->gua)->toBe('旅')
        ->and($match->guaSymbol)->toBe('䷷')
        ->and($match->evidence['uncovered'])->not->toBeEmpty()
        ->and(zhuixu_match(new PanResult([])))->toBeNull();
});
