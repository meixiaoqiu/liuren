<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\BikouRule;
use App\Services\PanCalculator;

function bikou_match(PanResult $pan): mixed
{
    return (new BikouRule)->match(PanFacts::from($pan));
}

function bikou_synthetic(int $stem, int $branch, int $initial, int $upperAtHead, int $generalAtHead, int $initialGeneral): PanResult
{
    $head = [0, 10, 8, 6, 4, 2][intdiv(array_search([$stem, $branch], PanCalculator::$jiazi2Ganzhi, true), 10)];
    $offset = ($upperAtHead - $head + 12) % 12;
    $tianpan = array_map(fn (int $ground): int => ($ground + $offset) % 12, range(0, 11));
    $initialGround = array_search($initial, $tianpan, true);
    $generals = array_fill(0, 12, 0);
    $generals[$head] = $generalAtHead;
    $generals[$initialGround] = $initialGeneral;

    return new PanResult(['rigan' => $stem, 'rizhi' => $branch, 'sanchuan0' => $initial, 'tianpan' => $tianpan, 'tianjiang' => $generals]);
}

test('bikou matches daquan jia-shen example by xun tail on xun head', function () {
    $pan = (new PanCalculator)->calculate('1904-02-20 05:00:00');
    $match = bikou_match($pan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['xun_head'])->toBe(8)
        ->and($match->evidence['xun_tail'])->toBe(5)
        ->and($match->evidence['initial'])->toBe(5)
        ->and($match->evidence['tail_on_head'])->toBeTrue();
});

test('bikou matches xun head riding xuanwu and issuing', function () {
    $match = bikou_match(bikou_synthetic(0, 0, 0, 4, 0, 9));
    expect($match)->not->toBeNull()
        ->and($match->evidence['head_riding_xuanwu'])->toBeTrue()
        ->and($match->evidence['head_upper_riding_xuanwu'])->toBeFalse();
});

test('bikou matches upper god at xun head riding xuanwu and issuing', function () {
    $match = bikou_match(bikou_synthetic(0, 0, 4, 4, 9, 9));
    expect($match)->not->toBeNull()
        ->and($match->evidence['head_upper_riding_xuanwu'])->toBeTrue();
});

test('bikou requires the matching god to issue', function () {
    expect(bikou_match(bikou_synthetic(0, 0, 6, 4, 9, 0)))->toBeNull();
});

test('bikou requires xuanwu for the second and third paths', function () {
    expect(bikou_match(bikou_synthetic(0, 0, 0, 4, 0, 0)))->toBeNull()
        ->and(bikou_match(bikou_synthetic(0, 0, 4, 4, 0, 0)))->toBeNull();
});

test('bikou exposes metadata and rejects missing facts', function () {
    $match = bikou_match(bikou_synthetic(0, 0, 0, 4, 0, 9));
    expect($match->code)->toBe('lesson.bikou')
        ->and($match->name)->toBe('闭口课')
        ->and($match->gua)->toBe('谦')
        ->and($match->guaSymbol)->toBe('䷎')
        ->and($match->evidence['uncovered'])->not->toBeEmpty()
        ->and(bikou_match(new PanResult([])))->toBeNull();
});
