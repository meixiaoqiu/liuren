<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\YinyiRule;
use App\Services\PanCalculator;

function yinyi_facts(?int $initial = 3, array $generals = [3 => 3], array $overrides = []): PanFacts
{
    $tianjiang = array_fill(0, 12, 4);
    foreach ($generals as $branch => $general) {
        $tianjiang[$branch] = $general;
    }

    return PanFacts::from(new PanResult(array_replace([
        'sanchuan0' => $initial,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'tianpan' => range(0, 11),
        'tianjiang' => $tianjiang,
    ], $overrides)));
}

function yinyi_match(PanFacts $facts): mixed
{
    return (new YinyiRule)->match($facts);
}

test('yinyi accepts each frozen initial and general combination', function (int $initial, int $general) {
    expect(yinyi_match(yinyi_facts($initial, [$initial => $general])))->not->toBeNull();
})->with([
    'mao riding liuhe' => [3, 3],
    'you riding liuhe' => [9, 3],
    'mao riding tianhou' => [3, 11],
    'you riding tianhou' => [9, 11],
]);

test('yinyi rejects mao or you riding another general', function (int $initial) {
    expect(yinyi_match(yinyi_facts($initial, [$initial => 4])))->toBeNull();
})->with(['mao' => 3, 'you' => 9]);

test('yinyi rejects a non mao-you initial riding hou or he', function (int $general) {
    expect(yinyi_match(yinyi_facts(0, [0 => $general])))->toBeNull();
})->with(['liuhe' => 3, 'tianhou' => 11]);

test('yinyi does not scan middle or final transmission', function (string $position, int $branch) {
    $overrides = [$position => $branch];
    expect(yinyi_match(yinyi_facts(0, [$branch => 3], $overrides)))->toBeNull();
})->with([
    'middle mao riding liuhe' => ['sanchuan1', 3],
    'final you riding liuhe' => ['sanchuan2', 9],
]);

test('yinyi requires hou or he to ride the same initial', function () {
    expect(yinyi_match(yinyi_facts(3, [3 => 4, 9 => 11], ['sanchuan1' => 9])))->toBeNull();
});

test('yinyi returns null when a necessary fact is missing', function (PanFacts $facts) {
    expect(yinyi_match($facts))->toBeNull();
})->with([
    'initial' => fn () => yinyi_facts(null),
    'dynamic generals' => fn () => yinyi_facts(3, [], ['tianjiang' => null]),
]);

test('yinyi exposes frozen metadata', function () {
    $match = yinyi_match(yinyi_facts());
    expect($match->code)->toBe('lesson.yinyi')
        ->and($match->name)->toBe('淫泆课')
        ->and($match->group)->toBe('六十四课')
        ->and($match->gua)->toBe('既济')
        ->and($match->guaSymbol)->toBe('䷾')
        ->and($match->evidence['initial'])->toBe(3)
        ->and($match->evidence['initial_general'])->toBe(3);
});

test('yinyi reproduces the daquan xin-wei example', function () {
    $pan = (new PanCalculator)->calculate('2020-09-25 15:00:00');
    $match = yinyi_match(PanFacts::from($pan));

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([7, 7, 8, 4])
        ->and($pan->get('tianpan'))->toBe([8, 9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7])
        ->and($pan->get('sike'))->toBe([7, 6, 6, 2, 7, 3, 3, 11])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([3, 11, 7])
        ->and([$pan->get('sanchuan0tianjiang'), $pan->get('sanchuan1tianjiang'), $pan->get('sanchuan2tianjiang')])->toBe([3, 7, 11])
        ->and($match)->not->toBeNull();
});

test('yinyi rejects the daquan yinv example because its initial is zi', function () {
    $pan = (new PanCalculator)->calculate('2025-07-28 07:00:00');
    expect(yinyi_match(PanFacts::from($pan)))->toBeNull();
});
