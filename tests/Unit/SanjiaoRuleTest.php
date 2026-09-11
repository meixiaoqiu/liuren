<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\SanjiaoRule;
use App\Services\PanCalculator;

function sanjiao_pan(array $overrides = [], array $generalsByBranch = [3 => 10]): PanResult
{
    $generals = array_fill(0, 12, 4);
    foreach ($generalsByBranch as $branch => $general) {
        $generals[$branch] = $general;
    }

    return new PanResult(array_replace([
        'rizhi' => 0,
        'sike' => [4, 0, 0, 3, 0, 6, 6, 9],
        'sanchuan0' => 3,
        'sanchuan1' => 6,
        'sanchuan2' => 9,
        'tianpan' => range(0, 11),
        'tianjiang' => $generals,
    ], $overrides));
}

function sanjiao_match(PanResult $pan): mixed
{
    return (new SanjiaoRule)->match(PanFacts::from($pan));
}

test('sanjiao matches the standard textual structure', function () {
    $match = sanjiao_match(sanjiao_pan());

    expect($match)->not->toBeNull()
        ->and($match->evidence['day_is_zhong'])->toBeTrue()
        ->and($match->evidence['branch_yang_is_zhong'])->toBeTrue()
        ->and($match->evidence['branch_yin_is_zhong'])->toBeTrue()
        ->and($match->evidence['all_transmissions_are_zhong'])->toBeTrue();
});

test('sanjiao accepts yin or he on each transmission position', function (int $position, int $branch, int $general) {
    $pan = sanjiao_pan([
        'sike' => [4, 0, 0, 0, 0, 0, 0, 0],
        'sanchuan0' => 3,
        'sanchuan1' => 6,
        'sanchuan2' => 9,
    ], [$branch => $general]);
    $match = sanjiao_match($pan);

    expect($match)->not->toBeNull()
        ->and(collect($match->evidence['matched_yin_he_occurrences'])
            ->contains(fn (array $item): bool => $item['scope'] === 'transmission' && $item['position'] === $position))
        ->toBeTrue();
})->with([
    'initial transmission riding taiyin' => [0, 3, 10],
    'middle transmission riding liuhe' => [1, 6, 3],
    'final transmission riding taiyin' => [2, 9, 10],
]);

test('sanjiao accepts a lesson upper zhong riding yin or he when transmissions do not', function () {
    $match = sanjiao_match(sanjiao_pan([
        'sike' => [4, 0, 0, 3, 0, 6, 6, 9],
        'sanchuan0' => 0,
        'sanchuan1' => 0,
        'sanchuan2' => 0,
    ], [3 => 3]));

    expect($match)->not->toBeNull()
        ->and(collect($match->evidence['matched_yin_he_occurrences'])->pluck('scope')->unique()->all())
        ->toBe(['lesson']);
});

test('sanjiao requires a zhong day branch', function () {
    expect(sanjiao_match(sanjiao_pan(['rizhi' => 1])))->toBeNull();
});

test('sanjiao requires branch yang to be zhong', function () {
    expect(sanjiao_match(sanjiao_pan(['sike' => [4, 0, 0, 3, 0, 1, 1, 9]])))->toBeNull();
});

test('sanjiao requires branch yin to be zhong', function () {
    expect(sanjiao_match(sanjiao_pan(['sike' => [4, 0, 0, 3, 0, 6, 6, 1]])))->toBeNull();
});

test('sanjiao requires every transmission to be zhong', function () {
    expect(sanjiao_match(sanjiao_pan(['sanchuan1' => 1])))->toBeNull();
});

test('sanjiao rejects zhong occurrences when none rides yin or he', function () {
    expect(sanjiao_match(sanjiao_pan([], [])))->toBeNull();
});

test('sanjiao rejects yin or he riding only a non-zhong branch', function () {
    expect(sanjiao_match(sanjiao_pan([], [1 => 10])))->toBeNull();
});

test('sanjiao does not scan a zhong elsewhere on the full plate', function () {
    $pan = sanjiao_pan([
        'sike' => [4, 0, 0, 0, 0, 0, 0, 0],
        'sanchuan0' => 0,
        'sanchuan1' => 0,
        'sanchuan2' => 0,
    ], [3 => 10]);

    expect(sanjiao_match($pan))->toBeNull();
});

test('sanjiao preserves repeated structural occurrences', function () {
    $match = sanjiao_match(sanjiao_pan([
        'sike' => [4, 0, 0, 0, 0, 3, 3, 0],
        'sanchuan0' => 3,
        'sanchuan1' => 6,
        'sanchuan2' => 9,
    ], [3 => 10]));
    $matched = collect($match->evidence['matched_yin_he_occurrences']);

    expect($matched->contains(fn (array $item): bool => $item['scope'] === 'lesson' && $item['position'] === 5))->toBeTrue()
        ->and($matched->contains(fn (array $item): bool => $item['scope'] === 'transmission' && $item['position'] === 0))->toBeTrue();
});

test('sanjiao returns null when a necessary fact is missing', function (array $overrides) {
    expect(sanjiao_match(sanjiao_pan($overrides)))->toBeNull();
})->with([
    'day branch' => [['rizhi' => null]],
    'lesson upper' => [['sike' => [4, 0]]],
    'transmission' => [['sanchuan2' => null]],
    'plate generals' => [['tianjiang' => null]],
]);

test('sanjiao exposes the frozen metadata and three foundations', function () {
    $match = sanjiao_match(sanjiao_pan());

    expect($match->code)->toBe('lesson.sanjiao')
        ->and($match->name)->toBe('三交课')
        ->and($match->group)->toBe('六十四课')
        ->and($match->description)->toBe('四仲日占，支辰阴阳及三传皆为四仲，且课传所见仲神至少一处乘太阴或六合。')
        ->and($match->gua)->toBe('姤')
        ->and($match->guaSymbol)->toBe('䷫')
        ->and(collect($match->evidence['foundations'])->pluck('title')->all())->toBe([
            '一交·四仲加辰', '二交·传皆四仲', '三交·仲神乘阴合',
        ]);
});

test('sanjiao reproduces the daquan wu-zi example with the production calculator', function () {
    $pan = (new PanCalculator)->calculate('2026-05-14 11:00:00');
    $match = sanjiao_match($pan);

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([4, 0, 6, 9])
        ->and($pan->get('sike'))->toBe([4, 8, 8, 11, 0, 3, 3, 6])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([3, 6, 9])
        ->and([$pan->get('sanchuan0tianjiang'), $pan->get('sanchuan1tianjiang'), $pan->get('sanchuan2tianjiang')])->toBe([10, 7, 4])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['matched_yin_he_occurrences'][0])->toMatchArray([
            'scope' => 'lesson', 'position' => 5, 'branch' => 3, 'general' => 10, 'general_name' => '太阴',
        ]);
});
