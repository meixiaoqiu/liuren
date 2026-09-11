<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ChongpoRule;
use App\Services\PanCalculator;

function chongpo_match(PanResult $pan): mixed
{
    return (new ChongpoRule)->match(PanFacts::from($pan));
}

function chongpo_plate(int $branch, int $ground): array
{
    $plate = range(0, 11);
    $oldPosition = array_search($branch, $plate, true);
    [$plate[$ground], $plate[$oldPosition]] = [$plate[$oldPosition], $plate[$ground]];

    return $plate;
}

test('chongpo matches the day branch path', function () {
    $match = chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 6, 'tianpan' => chongpo_plate(6, 3),
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['branch_path'])->toBeTrue()
        ->and($match->evidence['stem_path'])->toBeFalse()
        ->and($match->evidence['initial_ground'])->toBe(3)
        ->and($match->evidence['initial_break_ground'])->toBe(3)
        ->and($match->evidence['paths'])->toContain('日支冲神加破发用');
});

test('chongpo matches the stem lodging path', function () {
    $match = chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 1, 'sanchuan0' => 8, 'tianpan' => chongpo_plate(8, 5),
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['stem_lodging_branch'])->toBe(2)
        ->and($match->evidence['stem_clash'])->toBe(8)
        ->and($match->evidence['stem_path'])->toBeTrue()
        ->and($match->evidence['branch_path'])->toBeFalse();
});

test('chongpo records both paths when the day branch is the stem lodging branch', function () {
    $match = chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 2, 'sanchuan0' => 8, 'tianpan' => chongpo_plate(8, 5),
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['stem_path'])->toBeTrue()
        ->and($match->evidence['branch_path'])->toBeTrue()
        ->and($match->evidence['paths'])->toBe(['日干冲神加破发用', '日支冲神加破发用'])
        ->and($match->evidence['foundations'])->toHaveCount(2);
});

test('chongpo rejects a clash initial that is not on its own break ground', function () {
    expect(chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 6, 'tianpan' => chongpo_plate(6, 4),
    ])))->toBeNull();
});

test('chongpo rejects an initial on its own break ground when it is not a day clash', function () {
    expect(chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 7, 'tianpan' => chongpo_plate(7, 10),
    ])))->toBeNull();
});

test('chongpo does not treat a clash in a later transmission as issuing', function () {
    expect(chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 7, 'sanchuan1' => 6, 'sanchuan2' => 9,
        'tianpan' => chongpo_plate(7, 10),
    ])))->toBeNull();
});

test('chongpo uses the initial own break rather than the base break', function () {
    expect(chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 6, 'tianpan' => chongpo_plate(6, 9),
    ])))->toBeNull();
});

test('chongpo rejects every missing required fact', function (string $missing) {
    $data = ['rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 6, 'tianpan' => chongpo_plate(6, 3)];
    unset($data[$missing]);

    expect(chongpo_match(new PanResult($data)))->toBeNull();
})->with(['rigan', 'rizhi', 'sanchuan0', 'tianpan']);

test('chongpo exposes its metadata', function () {
    $match = chongpo_match(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sanchuan0' => 6, 'tianpan' => chongpo_plate(6, 3),
    ]));

    expect($match->code)->toBe('lesson.chongpo')
        ->and($match->name)->toBe('冲破课')
        ->and($match->gua)->toBe('夬')
        ->and($match->guaSymbol)->toBe('䷪')
        ->and($match->evidence['uncovered'])->toHaveCount(3);
});

test('chongpo reproduces the daquan standard example with the production calculator', function () {
    $pan = (new PanCalculator)->calculate('2056-04-18 13:00:00');
    $match = chongpo_match($pan);

    expect([$pan->get('nianzhi'), $pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([0, 6, 0, 7, 10])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([6, 9, 0])
        ->and($pan->get('tianpan')[3])->toBe(6)
        ->and($match)->not->toBeNull()
        ->and($match->evidence['branch_path'])->toBeTrue()
        ->and($match->evidence['initial_ground'])->toBe(3)
        ->and($match->evidence['initial_break_ground'])->toBe(3);
});
