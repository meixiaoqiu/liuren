<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\ZhuolunRule;

/**
 * 构造斫轮课基础盘面：初传卯（3），天盘卯加临地盘申（tianpan[8]=3）。
 * 天盘为十二支等距旋转（月将加时），此处取旋转偏移 7，使 tianpan[8]=3。
 *
 * @param  array<string, mixed>  $overrides
 */
function zl_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 3,
        'sanchuan1' => 11,
        'sanchuan2' => 7,
        'tianpan' => [7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5, 6],
        'xundun0' => 0,
        'xundun1' => 1,
        'xundun2' => 2,
        'rigan' => 6,
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function zl_match(array $overrides = []): ?RuleMatch
{
    return (new ZhuolunRule)->match(PanFacts::from(zl_pan($overrides)));
}

test('zhuolun matches when mao sits over geng and is the initial transmission', function () {
    $match = zl_match();

    expect($match)->not->toBeNull()
        ->and($match->gua)->toBe('颐')
        ->and($match->guaSymbol)->toBe('䷚')
        ->and($match->evidence['foundations'])->toHaveCount(2)
        ->and($match->evidence['judgments'])->toBe([])
        ->and($match->evidence['uncovered'])->toHaveCount(8);
});

test('zhuolun matches when mao sits over xin (you)', function () {
    // 旋转偏移 6，使 tianpan[9]=3（卯加酉）。
    expect(zl_match(['tianpan' => [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5]]))->not->toBeNull();
});

test('zhuolun does not match when the initial transmission is not mao', function () {
    expect(zl_match(['sanchuan0' => 5]))->toBeNull();
});

test('zhuolun does not match when mao does not sit over geng or xin', function () {
    expect(zl_match(['tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]))->toBeNull();
});

test('zhuolun marks rotten wood when the initial mao falls on void', function () {
    $match = zl_match(['xundun0' => 10]);
    $judgment = collect($match->evidence['judgments'])->firstWhere('code', 'rotten_wood');

    expect($judgment['label'])->toBe('朽木难雕')
        ->and($judgment['effect'])->toBe('reduce');
});

test('zhuolun marks old wheel rehewn when the transmission contains the day grave', function () {
    $match = zl_match(['sanchuan1' => 1]);
    $judgment = collect($match->evidence['judgments'])->firstWhere('code', 'old_wheel_rehewn');

    expect($judgment['label'])->toBe('旧轮再斫')
        ->and($judgment['effect'])->toBe('reduce');
});
