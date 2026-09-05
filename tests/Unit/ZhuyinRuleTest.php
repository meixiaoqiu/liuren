<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\ZhuyinRule;

/**
 * 构造铸印课基础盘面：三传巳、戌、卯（5、10、3），含戌与巳。
 *
 * @param  array<string, mixed>  $overrides
 */
function zy_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 5,
        'sanchuan1' => 10,
        'sanchuan2' => 3,
        'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
        'sanchuan0tianjiang' => 6,
        'sanchuan1tianjiang' => 1,
        'sanchuan2tianjiang' => 8,
        'xundun0' => 0,
        'xundun1' => 1,
        'xundun2' => 2,
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function zy_match(array $overrides = []): ?RuleMatch
{
    return (new ZhuyinRule)->match(PanFacts::from(zy_pan($overrides)));
}

test('zhuyin matches when xu and si both enter the transmission', function () {
    $match = zy_match();

    expect($match)->not->toBeNull()
        ->and($match->gua)->toBe('鼎')
        ->and($match->guaSymbol)->toBe('䷱')
        ->and($match->evidence['foundations'])->toHaveCount(2)
        ->and($match->evidence['judgments'])->toBe([]);
});

test('zhuyin does not match when si is missing from the transmission', function () {
    expect(zy_match(['sanchuan0' => 10, 'sanchuan1' => 3, 'sanchuan2' => 0]))->toBeNull();
});

test('zhuyin does not match when xu is missing from the transmission', function () {
    expect(zy_match(['sanchuan0' => 5, 'sanchuan1' => 3, 'sanchuan2' => 0]))->toBeNull();
});

test('zhuyin matches regardless of where xu and si sit in the transmission', function () {
    expect(zy_match(['sanchuan0' => 10, 'sanchuan1' => 0, 'sanchuan2' => 5]))->not->toBeNull();
});

test('zhuyin marks the empty xu when it falls on void', function () {
    $match = zy_match(['xundun1' => 10]);
    $judgment = collect($match->evidence['judgments'])->firstWhere('code', 'seal_or_mold_empty');

    expect($judgment['evidence'])->toContain('中传戌落旬空')
        ->and($judgment['effect'])->toBe('reduce');
});

test('zhuyin marks the empty mao when it falls on void', function () {
    $match = zy_match(['xundun2' => 10]);
    $judgment = collect($match->evidence['judgments'])->firstWhere('code', 'seal_or_mold_empty');

    expect($judgment['evidence'])->toContain('末传卯落旬空');
});

test('zhuyin does not mark empty when only the initial si is void', function () {
    $match = zy_match(['xundun0' => 10]);

    expect(array_column($match->evidence['judgments'], 'code'))->not->toContain('seal_or_mold_empty');
});
