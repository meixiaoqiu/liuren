<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;

test('PanFacts identifies both void branches in all six xun', function (int $stem, int $branch, array $voids, int $solid) {
    $facts = PanFacts::from(new PanResult(['rigan' => $stem, 'rizhi' => $branch]));

    expect($facts->isBranchXunVoid($voids[0]))->toBeTrue()
        ->and($facts->isBranchXunVoid($voids[1]))->toBeTrue()
        ->and($facts->isBranchXunVoid($solid))->toBeFalse();
})->with([
    '甲子旬戌亥空' => [0, 0, [10, 11], 0],
    '甲戌旬申酉空' => [0, 10, [8, 9], 10],
    '甲申旬午未空' => [0, 8, [6, 7], 8],
    '甲午旬辰巳空' => [0, 6, [4, 5], 6],
    '甲辰旬寅卯空' => [0, 4, [2, 3], 4],
    '甲寅旬子丑空' => [0, 2, [0, 1], 2],
]);

test('PanFacts xun void returns null for invalid input or unavailable xun', function () {
    expect(PanFacts::from(new PanResult([]))->isBranchXunVoid(0))->toBeNull()
        ->and(PanFacts::from(new PanResult(['rigan' => 0, 'rizhi' => 0]))->isBranchXunVoid(12))->toBeNull();
});
