<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\YinCongRule;

/**
 * 构造引从课基础盘面：庚辰日，初传寅（2）、末传子（0）。
 * 日干庚寄申（8），天盘旋转偏移 5，使 tianpan[9]=寅（申前一位酉）、tianpan[7]=子（申后一位未）。
 *
 * @param  array<string, mixed>  $overrides
 */
function yc_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 2,
        'sanchuan1' => 5,
        'sanchuan2' => 0,
        'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
        'rigan' => 6,
        'rizhi' => 4,
        'nianming' => 0,
        'calculationTrace' => ['plate_patterns' => []],
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function yc_match(array $overrides = []): ?RuleMatch
{
    return (new YinCongRule)->match(PanFacts::from(yc_pan($overrides)));
}

test('yincong matches when initial and final transmissions flank the day stem', function () {
    $match = yc_match();

    expect($match)->not->toBeNull()
        ->and($match->gua)->toBe('涣')
        ->and($match->guaSymbol)->toBe('䷺')
        ->and($match->evidence['foundations'])->toHaveCount(1)
        ->and($match->evidence['foundations'][0]['title'])->toBe('拱天干')
        ->and($match->evidence['foundations'][0]['detail'])->toContain('拱贵')
        ->and($match->evidence['judgments'])->toBe([])
        ->and($match->evidence['uncovered'])->toHaveCount(2);
});

test('yincong matches when initial and final transmissions flank the day branch', function () {
    // 甲午日，初传子（0）、末传戌（10），tianpan[7]=子（午前一位未）、tianpan[5]=戌（午后一位巳）。
    $match = yc_match([
        'sanchuan0' => 0,
        'sanchuan2' => 10,
        'rigan' => 0,
        'rizhi' => 6,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('拱地支');
});

test('yincong marks two-noble yin cong when the transmissions are the two nobles', function () {
    // 壬子日，初传巳（昼贵）、末传卯（夜贵），拱天干的同时又为两贵引从。
    $match = yc_match([
        'sanchuan0' => 5,
        'sanchuan2' => 3,
        'rigan' => 8,
        'rizhi' => 0,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('拱天干')
        ->and($match->evidence['foundations'][0]['detail'])->toContain('两贵引从');
});

test('yincong matches when nobles flank the stem and branch to embrace the birth year', function () {
    // 丁酉日，夜贵酉加临丁干寄宫未、昼贵亥加临日支酉，干支夹拱年命申。
    $match = yc_match([
        'tianpan' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1],
        'rigan' => 3,
        'rizhi' => 9,
        'nianming' => 8,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('贵临干支拱年命');
});

test('yincong matches nobles on the stem and branch in either direction', function () {
    // 丁巳日，昼贵亥加临丁干寄宫未、夜贵酉加临日支巳，干支夹拱年命午（与丁酉方向相反）。
    $match = yc_match([
        'tianpan' => [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3],
        'rigan' => 3,
        'rizhi' => 5,
        'nianming' => 6,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('贵临干支拱年命')
        ->and($match->evidence['foundations'][0]['detail'])->toContain('昼贵亥加临日干寄宫未');
});

test('yincong matches when stem and branch flank the day prosperity in fuyin', function () {
    // 丁巳日伏吟，丁寄未、日支巳，前后夹拱日禄午。
    $match = yc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 3,
        'rizhi' => 5,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('干支拱日禄');
});

test('yincong matches when stem and branch flank the night noble in fuyin', function () {
    // 庚午日伏吟，庚寄申、日支午，前后夹拱夜贵未。
    $match = yc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 6,
        'rizhi' => 6,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('干支拱夜贵');
});

test('yincong matches when stem and branch flank the day noble in fuyin', function () {
    // 甲子日伏吟，甲寄寅、日支子，前后夹拱昼贵丑。
    $match = yc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 0,
        'rizhi' => 0,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['foundations'][0]['title'])->toBe('干支拱昼贵');
});

test('yincong does not match when the transmissions do not flank the stem or branch', function () {
    expect(yc_match(['tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]]))->toBeNull();
});

test('yincong does not match when the initial or final transmission is displaced', function () {
    expect(yc_match(['sanchuan0' => 1, 'sanchuan2' => 3]))->toBeNull();
});
