<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JiaotongRule;
use App\Services\PanCalculator;

function jiaotong_match(int $initial, int $initialGeneral, int $final, int $finalGeneral): mixed
{
    $generals = array_fill(0, 12, 4);
    $generals[$initial] = $initialGeneral;
    $generals[$final] = $finalGeneral;
    $pan = new PanResult(['sanchuan0' => $initial, 'sanchuan2' => $final, 'tianpan' => range(0, 11), 'tianjiang' => $generals]);

    return (new JiaotongRule)->match(PanFacts::from($pan));
}

test('jiaotong requires initial liuhe and final tianhou', function () {
    expect(jiaotong_match(3, 3, 7, 11))->not->toBeNull()
        ->and(jiaotong_match(3, 3, 7, 4))->toBeNull()
        ->and(jiaotong_match(3, 4, 7, 11))->toBeNull();
});

test('jiaotong may match when the initial is not mao or you', function () {
    expect(jiaotong_match(0, 3, 7, 11))->not->toBeNull();
});

test('jiaotong reproduces the daquan xin-wei example', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2020-09-25 15:00:00'));
    expect((new JiaotongRule)->match($facts))->not->toBeNull();
});

test('jiaotong rejects the daquan yinv example', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2025-07-28 07:00:00'));
    expect((new JiaotongRule)->match($facts))->toBeNull();
});

test('jiaotong returns null when necessary facts are missing', function (array $data) {
    expect((new JiaotongRule)->match(PanFacts::from(new PanResult($data))))->toBeNull();
})->with([
    'initial' => [['sanchuan2' => 7, 'tianpan' => range(0, 11), 'tianjiang' => range(0, 11)]],
    'final' => [['sanchuan0' => 3, 'tianpan' => range(0, 11), 'tianjiang' => range(0, 11)]],
    'generals' => [['sanchuan0' => 3, 'sanchuan2' => 7, 'tianpan' => range(0, 11)]],
]);

test('jiaotong exposes grid metadata and evidence', function () {
    $match = jiaotong_match(3, 3, 7, 11);
    expect($match->code)->toBe('structure.jiaotong')
        ->and($match->name)->toBe('狡童格')
        ->and($match->group)->toBe('淫泆课体')
        ->and($match->marker)->toBe('格')
        ->and($match->evidence)->toMatchArray(['initial' => 3, 'initial_general' => 3, 'final' => 7, 'final_general' => 11]);
});
