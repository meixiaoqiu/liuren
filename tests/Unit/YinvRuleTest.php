<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\YinvRule;
use App\Services\PanCalculator;

function yinv_match(int $initial, int $initialGeneral, int $final, int $finalGeneral): mixed
{
    $generals = array_fill(0, 12, 4);
    $generals[$initial] = $initialGeneral;
    $generals[$final] = $finalGeneral;
    $pan = new PanResult(['sanchuan0' => $initial, 'sanchuan2' => $final, 'tianpan' => range(0, 11), 'tianjiang' => $generals]);

    return (new YinvRule)->match(PanFacts::from($pan));
}

test('yinv requires initial tianhou and final liuhe', function () {
    expect(yinv_match(0, 11, 4, 3))->not->toBeNull()
        ->and(yinv_match(0, 11, 4, 4))->toBeNull()
        ->and(yinv_match(0, 4, 4, 3))->toBeNull();
});

test('yinv may match when the initial is not mao or you', function () {
    expect(yinv_match(0, 11, 4, 3))->not->toBeNull();
});

test('yinv reproduces the daquan wu-xu example independently', function () {
    $pan = (new PanCalculator)->calculate('2025-07-28 07:00:00');
    $facts = PanFacts::from($pan);
    expect($pan->get('sike'))->toBe([4, 7, 7, 9, 10, 0, 0, 2])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([0, 2, 4])
        ->and([$pan->get('sanchuan0tianjiang'), $pan->get('sanchuan1tianjiang'), $pan->get('sanchuan2tianjiang')])->toBe([11, 1, 3])
        ->and((new YinvRule)->match($facts))->not->toBeNull();
});

test('yinv rejects the daquan xin-wei example', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2020-09-25 15:00:00'));
    expect((new YinvRule)->match($facts))->toBeNull();
});

test('yinv returns null when necessary facts are missing', function (array $data) {
    expect((new YinvRule)->match(PanFacts::from(new PanResult($data))))->toBeNull();
})->with([
    'initial' => [['sanchuan2' => 4, 'tianpan' => range(0, 11), 'tianjiang' => range(0, 11)]],
    'final' => [['sanchuan0' => 0, 'tianpan' => range(0, 11), 'tianjiang' => range(0, 11)]],
    'generals' => [['sanchuan0' => 0, 'sanchuan2' => 4, 'tianpan' => range(0, 11)]],
]);

test('yinv exposes grid metadata and evidence', function () {
    $match = yinv_match(0, 11, 4, 3);
    expect($match->code)->toBe('structure.yinv')
        ->and($match->name)->toBe('泆女格')
        ->and($match->group)->toBe('淫泆课体')
        ->and($match->marker)->toBe('格')
        ->and($match->evidence)->toMatchArray(['initial' => 0, 'initial_general' => 11, 'final' => 4, 'final_general' => 3]);
});
