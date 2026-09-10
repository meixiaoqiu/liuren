<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\BikouRule;
use App\Domain\Pan\Rules\YixunZhoubianRule;
use App\Services\PanCalculator;

function yixun_zhoubian_pan(int $stemUpper, int $branchUpper): PanResult
{
    $tianpan = range(0, 11);
    $tianpan[4] = $stemUpper; // 乙寄辰
    $tianpan[7] = $branchUpper; // 乙未日之支未

    return new PanResult(['rigan' => 1, 'rizhi' => 7, 'tianpan' => $tianpan, 'sanchuan0' => 0, 'tianjiang' => range(0, 11)]);
}

test('yixun zhoubian requires xun tail on stem and xun head on branch', function () {
    // 乙未属甲午旬：旬首午(6)，旬尾卯(3)。
    $match = (new YixunZhoubianRule)->match(PanFacts::from(yixun_zhoubian_pan(3, 6)));
    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('structure.yixun_zhoubian')
        ->and($match->name)->toBe('一旬周遍格')
        ->and($match->marker)->toBe('格')
        ->and($match->group)->toBe('闭口课篇附格')
        ->and($match->evidence['xun_head'])->toBe(6)
        ->and($match->evidence['xun_tail'])->toBe(3);
});

test('yixun zhoubian rejects each missing side independently', function () {
    $rule = new YixunZhoubianRule;
    expect($rule->match(PanFacts::from(yixun_zhoubian_pan(2, 6))))->toBeNull()
        ->and($rule->match(PanFacts::from(yixun_zhoubian_pan(3, 5))))->toBeNull();
});

test('yixun zhoubian is independent from bikou', function () {
    $facts = PanFacts::from(yixun_zhoubian_pan(3, 6));
    expect((new YixunZhoubianRule)->match($facts))->not->toBeNull()
        ->and((new BikouRule)->match($facts))->toBeNull();
});

test('yixun zhoubian reproduces the daquan yi-wei example', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('1905-12-22 05:00:00'));
    $match = (new YixunZhoubianRule)->match($facts);
    expect($match)->not->toBeNull()
        ->and($match->evidence['day_stem'])->toBe(1)
        ->and($match->evidence['day_branch'])->toBe(7)
        ->and($match->evidence['stem_upper'])->toBe(3)
        ->and($match->evidence['branch_upper'])->toBe(6)
        ->and((new BikouRule)->match($facts))->toBeNull();
});
