<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\QinhaiRule;
use App\Services\PanCalculator;

function qinhai_fixture(int $stem, int $branch, array $tianpan, int $initial): PanFacts
{
    return PanFacts::from(new PanResult([
        'rigan' => $stem, 'rizhi' => $branch, 'tianpan' => $tianpan, 'sanchuan0' => $initial,
    ]));
}

test('qinhai reproduces the daquan bing-zi lower-deity example through production calculator', function () {
    $pan = (new PanCalculator)->calculate('2025-11-03 15:00:00');
    $match = (new QinhaiRule)->match(PanFacts::from($pan));

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([2, 0, 8, 3])
        ->and($pan->get('sike'))->toBe([2, 0, 0, 7, 0, 7, 7, 2])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([0, 7, 2])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['matched_routes'])->toBe(['branch'])
        ->and($match->evidence['routes'][0])->toMatchArray([
            'route' => 'branch', 'lower' => 0, 'upper' => 7, 'initial' => 0,
            'hai_pair' => [0, 7], 'initial_role' => 'lower',
        ]);
});

test('qinhai accepts the stem route with the upper deity as initial', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 5;
    $match = (new QinhaiRule)->match(qinhai_fixture(0, 0, $tianpan, 5));

    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toBe(['stem'])
        ->and($match->evidence['routes'][0]['initial_role'])->toBe('upper');
});

test('qinhai preserves both matching routes', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 5;
    $tianpan[5] = 2;
    $match = (new QinhaiRule)->match(qinhai_fixture(0, 5, $tianpan, 5));

    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toBe(['stem', 'branch'])
        ->and($match->evidence['routes'])->toHaveCount(2);
});

test('qinhai rejects a harm whose two deities do not include initial', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 5;
    expect((new QinhaiRule)->match(qinhai_fixture(0, 0, $tianpan, 7)))->toBeNull();
});

test('qinhai does not expand to cross-carriage or arbitrary lesson harms', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 4;
    $tianpan[0] = 5;
    expect((new QinhaiRule)->match(qinhai_fixture(0, 0, $tianpan, 4)))->toBeNull();
});

test('qinhai rejects fuyin and fanyin structural counterexamples', function () {
    $fuyin = range(0, 11);
    $fanyin = array_map(fn (int $branch): int => ($branch + 6) % 12, range(0, 11));
    expect((new QinhaiRule)->match(qinhai_fixture(0, 0, $fuyin, 0)))->toBeNull()
        ->and((new QinhaiRule)->match(qinhai_fixture(0, 0, $fanyin, 0)))->toBeNull();
});

test('qinhai exposes its frozen identity and evidence keys', function () {
    $tianpan = range(0, 11);
    $tianpan[0] = 7;
    $match = (new QinhaiRule)->match(qinhai_fixture(0, 0, $tianpan, 0));
    expect($match->code)->toBe('lesson.qinhai')->and($match->name)->toBe('侵害课')
        ->and($match->gua)->toBe('损')->and($match->guaSymbol)->toBe('䷨')
        ->and($match->evidence)->toHaveKeys(['matched_routes', 'routes', 'initial', 'foundations', 'judgments', 'uncovered'])
        ->and($match->evidence['judgments'])->toBe([]);
});
