<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\YouziRule;
use App\Services\PanCalculator;

function youzi_match(PanResult $pan): mixed
{
    return (new YouziRule)->match(PanFacts::from($pan));
}

dataset('youzi month tianma mapping', [
    '子月天马寅' => [0, 2],
    '丑月天马辰' => [1, 4],
    '寅月天马午' => [2, 6],
    '卯月天马申' => [3, 8],
    '辰月天马戌' => [4, 10],
    '巳月天马子' => [5, 0],
    '午月天马寅' => [6, 2],
    '未月天马辰' => [7, 4],
    '申月天马午' => [8, 6],
    '酉月天马申' => [9, 8],
    '戌月天马戌' => [10, 10],
    '亥月天马子' => [11, 0],
]);

test('youzi month tianma table covers all twelve month branches', function (int $monthBranch, int $expectedTianma) {
    $mapping = (new ReflectionClass(YouziRule::class))->getConstant('MONTH_TIANMA_BY_MONTH_BRANCH');

    expect($mapping)->toHaveCount(12)
        ->and($mapping[$monthBranch])->toBe($expectedTianma);
})->with('youzi month tianma mapping');

test('youzi matches xun ding as initial when all transmissions are seasonal', function () {
    $match = youzi_match(new PanResult([
        'rigan' => 1, 'rizhi' => 5, 'yuezhi' => 2,
        'sanchuan0' => 7, 'sanchuan1' => 10, 'sanchuan2' => 1,
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['all_seasonal'])->toBeTrue()
        ->and($match->evidence['xun_head'])->toBe(4)
        ->and($match->evidence['xun_ding'])->toBe(7)
        ->and($match->evidence['xun_ding_as_initial'])->toBeTrue()
        ->and($match->evidence['month_tianma_as_initial'])->toBeFalse()
        ->and($match->evidence['paths'])->toBe(['旬丁发用']);
});

test('youzi matches month tianma as initial when all transmissions are seasonal', function () {
    $match = youzi_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'yuezhi' => 4,
        'sanchuan0' => 10, 'sanchuan1' => 1, 'sanchuan2' => 4,
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['xun_ding_as_initial'])->toBeFalse()
        ->and($match->evidence['month_tianma'])->toBe(10)
        ->and($match->evidence['month_tianma_as_initial'])->toBeTrue()
        ->and($match->evidence['paths'])->toBe(['天马发用']);
});

test('youzi maps the sixth month tianma to chen', function () {
    $match = youzi_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'yuezhi' => 7,
        'sanchuan0' => 4, 'sanchuan1' => 7, 'sanchuan2' => 10,
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['month_tianma'])->toBe(4)
        ->and($match->evidence['month_tianma_as_initial'])->toBeTrue();
});

test('youzi rejects all seasonal transmissions when neither xun ding nor tianma issues', function () {
    expect(youzi_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'yuezhi' => 2,
        'sanchuan0' => 7, 'sanchuan1' => 10, 'sanchuan2' => 1,
    ])))->toBeNull();
});

test('youzi requires every transmission to be a seasonal branch', function () {
    expect(youzi_match(new PanResult([
        'rigan' => 1, 'rizhi' => 5, 'yuezhi' => 2,
        'sanchuan0' => 7, 'sanchuan1' => 10, 'sanchuan2' => 0,
    ])))->toBeNull();
});

test('youzi does not treat month tianma in middle transmission as issuing', function () {
    expect(youzi_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'yuezhi' => 4,
        'sanchuan0' => 7, 'sanchuan1' => 10, 'sanchuan2' => 1,
    ])))->toBeNull();
});

test('youzi does not treat xun ding in middle transmission as issuing', function () {
    expect(youzi_match(new PanResult([
        'rigan' => 1, 'rizhi' => 5, 'yuezhi' => 2,
        'sanchuan0' => 10, 'sanchuan1' => 7, 'sanchuan2' => 4,
    ])))->toBeNull();
});

test('youzi reproduces the daquan yi-si example with the production calculator', function () {
    $pan = (new PanCalculator)->calculate('2022-04-22 11:00:00');
    $match = youzi_match($pan);

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('yuezhi'), $pan->get('yuejiang')])
        ->toBe([1, 5, 4, 9])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])
        ->toBe([7, 10, 1])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['xun_head'])->toBe(4)
        ->and($match->evidence['xun_ding'])->toBe(7)
        ->and($match->evidence['month_tianma'])->toBe(10)
        ->and($match->evidence['xun_ding_as_initial'])->toBeTrue()
        ->and($match->evidence['month_tianma_as_initial'])->toBeFalse();
});

test('youzi exposes metadata and rejects missing facts', function () {
    $match = youzi_match(new PanResult([
        'rigan' => 1, 'rizhi' => 5, 'yuezhi' => 2,
        'sanchuan0' => 7, 'sanchuan1' => 10, 'sanchuan2' => 1,
    ]));

    expect($match->code)->toBe('lesson.youzi')
        ->and($match->name)->toBe('游子课')
        ->and($match->gua)->toBe('观')
        ->and($match->guaSymbol)->toBe('䷓')
        ->and($match->evidence['uncovered'])->not->toBeEmpty()
        ->and(youzi_match(new PanResult([])))->toBeNull();
});
