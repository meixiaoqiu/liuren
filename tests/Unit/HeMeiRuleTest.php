<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HeMeiRule;
use App\Services\PanCalculator;

function hemei_match(PanResult $pan): mixed
{
    return (new HeMeiRule)->match(PanFacts::from($pan));
}

function hemei_synthetic(int $stem, int $branch, array $transmissions, int $dayUpper, int $branchUpper): PanResult
{
    $lodgings = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];
    $tianpan = range(0, 11);
    $tianpan[$lodgings[$stem]] = $dayUpper;
    $tianpan[$branch] = $branchUpper;

    return new PanResult([
        'rigan' => $stem, 'rizhi' => $branch, 'tianpan' => $tianpan,
        'sanchuan0' => $transmissions[0], 'sanchuan1' => $transmissions[1], 'sanchuan2' => $transmissions[2],
    ]);
}

test('daquan ren-wu example matches the cross-liuhe structure exactly', function () {
    $match = hemei_match((new PanCalculator)->calculate('2026-01-08 09:00:00'));
    expect($match)->not->toBeNull()
        ->and($match->evidence['day_stem_lodging_branch'])->toBe(11)
        ->and($match->evidence['branch_upper'])->toBe(2)
        ->and($match->evidence['day_branch'])->toBe(6)
        ->and($match->evidence['day_upper'])->toBe(7)
        ->and($match->evidence['cross_liuhe'])->toBeTrue()
        ->and($match->evidence['matched_structures'])->toContain('cross_liuhe');
});

test('same-liuhe structure requires both same-side relations', function () {
    $match = hemei_match((new PanCalculator)->calculate('2000-01-27 05:00:00'));
    expect($match)->not->toBeNull()
        ->and($match->evidence['same_liuhe'])->toBeTrue()
        ->and($match->evidence['matched_structures'])->toContain('same_liuhe');
});

test('upper gods and any one transmission may form sanhe', function () {
    $match = hemei_match(hemei_synthetic(0, 4, [8, 1, 2], 0, 4));
    expect($match)->not->toBeNull()
        ->and($match->evidence['upper_sanhe_transmission'])->toBeTrue()
        ->and($match->evidence['sanhe_transmission_detail'])->toBe(['position' => 0, 'branch' => 8]);
});

test('a single cross-side liuhe relation is insufficient', function () {
    expect(hemei_match(hemei_synthetic(1, 1, [5, 6, 7], 0, 4)))->toBeNull();
});

test('a single same-side liuhe relation is insufficient', function () {
    expect(hemei_match(hemei_synthetic(7, 3, [4, 5, 6], 3, 5)))->toBeNull();
});

test('three transmissions forming sanhe alone is insufficient', function () {
    expect(hemei_match(hemei_synthetic(7, 1, [1, 5, 9], 5, 11)))->toBeNull();
});

test('evidence exposes only the three production structures', function () {
    $match = hemei_match((new PanCalculator)->calculate('2026-01-08 09:00:00'));
    expect($match->evidence)->toHaveKeys([
        'cross_liuhe', 'same_liuhe', 'upper_sanhe_transmission',
        'sanhe_transmission_detail', 'matched_structures', 'uncovered',
    ])->not->toHaveKeys(['struct_F2_sanchuan_sanhe_element_yin_or_cai', 'struct_A_day_upper_liuhe_branch_upper']);
});
