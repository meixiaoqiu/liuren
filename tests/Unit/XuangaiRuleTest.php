<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\XuangaiRule;

/**
 * 构造轩盖课基础盘面：三传午、卯、子；三传天将为中平将；其余字段均不触发任何课义条件。
 *
 * @param  array<string, mixed>  $overrides
 */
function xr_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 6,
        'sanchuan1' => 3,
        'sanchuan2' => 0,
        'sanchuan0tianjiang' => 2,
        'sanchuan1tianjiang' => 4,
        'sanchuan2tianjiang' => 6,
        'yuezhi' => 1,
        'rigan' => 0,
        'rizhi' => 0,
        'nianzhi' => 4,
        'yuejiang' => 11,
        'xundun0' => 0,
        'xundun1' => 1,
        'xundun2' => 2,
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function xr_match(array $overrides = []): ?RuleMatch
{
    return (new XuangaiRule)->match(PanFacts::from(xr_pan($overrides)));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return list<string>
 */
function xr_condition_codes(array $overrides = []): array
{
    $match = xr_match($overrides);

    return $match === null ? [] : array_column($match->evidence['conditions'], 'code');
}

test('xuangai does not match when the transmission is not wu-mao-zi', function () {
    expect(xr_match(['sanchuan2' => 1]))->toBeNull();
});

test('xuangai matches with a neutral base and no verified condition', function () {
    $match = xr_match();

    expect($match)->not->toBeNull()
        ->and($match->evidence['conditions'])->toBe([])
        ->and($match->evidence['uncovered'])->toHaveCount(6);
});

test('xuangai lists the uncovered original conditions', function () {
    $uncovered = xr_match()->evidence['uncovered'];

    expect(implode('', $uncovered))
        ->toContain('德神上乘吉将')
        ->toContain('三传带杀')
        ->toContain('乘蛇虎死气')
        ->toContain('克年命日辰')
        ->toContain('卯作丧车')
        ->toContain('车马作财');
});

test('xuangai marks the proper month for the first and seventh months', function (int $yuezhi) {
    expect(xr_condition_codes(['yuezhi' => $yuezhi]))->toContain('proper_month');
})->with([2, 8]);

test('xuangai marks day-and-use wangxiang when day stem and initial transmission are both wang or xiang', function () {
    // 寅月木旺火相：日干丁（火）相，发用午（火）相。
    expect(xr_condition_codes(['yuezhi' => 2, 'rigan' => 3]))->toContain('day_and_use_wang_xiang');
});

test('xuangai does not mark day-and-use wangxiang when only the day stem is wang', function () {
    // 申月金旺水相：日干庚（金）旺，发用午（火）不旺相。
    expect(xr_condition_codes(['yuezhi' => 8, 'rigan' => 6]))->not->toContain('day_and_use_wang_xiang');
});

test('xuangai marks an empty transmission branch', function () {
    expect(xr_condition_codes(['xundun0' => 10]))->toContain('empty_transmission');
});

test('xuangai reports the generals riding each transmission as an observation', function () {
    $match = xr_match(['sanchuan0tianjiang' => 5]);
    $observation = collect($match->evidence['observations'])->firstWhere('code', 'transmission_generals');

    expect($observation['evidence'])->toContain('初传午乘青龙');
});

test('xuangai reports the year and month positions as an observation', function () {
    $match = xr_match(['nianzhi' => 6]);
    $observation = collect($match->evidence['observations'])->firstWhere('code', 'year_month_positions');

    expect($observation['evidence'])->toContain('太岁午在初传');
});

test('xuangai preserves the transmission position when a general is missing', function () {
    $match = xr_match([
        'sanchuan0tianjiang' => null,
        'sanchuan1tianjiang' => 5,
        'sanchuan2tianjiang' => 11,
    ]);
    $observation = collect($match->evidence['observations'])->firstWhere('code', 'transmission_generals');

    expect($observation['evidence'])
        ->toContain('中传卯乘青龙')
        ->toContain('末传子乘天后')
        ->not->toContain('初传午乘青龙');
});

test('xuangai does not mark day-and-use wangxiang when only the initial transmission is wangxiang', function () {
    // 春季木旺火相：日干庚（金）不旺相，初传午（火）相 → 只有初传旺相。
    expect(xr_condition_codes([
        'calculationTime' => '2000-03-01 06:00:00',
        'rigan' => 6,
    ]))->not->toContain('day_and_use_wang_xiang');
});

test('xuangai does not mark day-and-use wangxiang in autumn when only the day stem is wang', function () {
    // 秋季金旺水相：日干庚（金）旺，初传午（火）不旺相 → 只有日干旺相。
    expect(xr_condition_codes([
        'calculationTime' => '2000-09-01 06:00:00',
        'rigan' => 6,
    ]))->not->toContain('day_and_use_wang_xiang');
});

test('xuangai does not mark day-and-use wangxiang in the soil period', function () {
    // 土旺期土旺金相：日干己（土）旺，初传午（火）不旺相。
    expect(xr_condition_codes([
        'calculationTime' => '2000-01-25 06:00:00',
        'rigan' => 5,
    ]))->not->toContain('day_and_use_wang_xiang');
});
