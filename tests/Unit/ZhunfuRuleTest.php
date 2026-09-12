<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\ZhunfuRule;
use App\Services\PanCalculator;

function zhunfu_pan_with_querent(string $datetime = '2026-02-28 11:00:00', string $birth = '1986-08-01 00:00:00', string $gender = 'male'): PanResult
{
    $calculator = new PanCalculator;
    $pan = $calculator->calculate($datetime);
    $birthPan = $calculator->calculate($birth);
    $fate = (new FateCalculator)->calculate(
        $birthPan->get('nian_index'),
        $pan->get('nian_index'),
        $gender,
    );

    $data = $pan->toArray();
    $data['context'] = ['people' => [[
        'role' => 'querent',
        'birth_datetime' => $birth,
        'gender' => $gender,
        ...$fate,
    ]]];

    return new PanResult($data);
}

function zhunfu_match(PanResult $pan): mixed
{
    return (new ZhunfuRule)->match(PanFacts::from($pan));
}

test('daquan gui-you spring example satisfies all eight zhun and five fu', function () {
    $pan = zhunfu_pan_with_querent();
    $facts = PanFacts::from($pan);
    $match = zhunfu_match($pan);

    expect($pan->get('rigan'))->toBe(9)
        ->and($pan->get('rizhi'))->toBe(9)
        ->and($pan->get('yuejiang'))->toBe(11)
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([7, 0, 5])
        ->and($pan->get('sanchuan0tianjiang'))->toBe(2)
        ->and($pan->get('sanchuan2tianjiang'))->toBe(0)
        ->and($facts->branchSeasonalState(7))->toBe('死')
        ->and($facts->branchSeasonalState(2))->toBe('旺')
        ->and($facts->branchSeasonalState(5))->toBe('相')
        ->and($match)->not->toBeNull()
        ->and($match->code)->toBe('lesson.zhunfu')
        ->and($match->name)->toBe('迍福课')
        ->and($match->gua)->toBe('屯')
        ->and($match->guaSymbol)->toBe('䷂')
        ->and($match->evidence['zhun_count'])->toBe(8)
        ->and($match->evidence['fu_count'])->toBe(5)
        ->and(in_array(false, array_values($match->evidence['conditions']), true))->toBeFalse();
});

test('sixth zhun records both stem punishment and transmission harm routes in the standard example', function () {
    $match = zhunfu_match(zhunfu_pan_with_querent());
    $routes = collect($match->evidence['xing_hai_routes']);

    expect($routes->contains(fn (array $route): bool => $route['type'] === 'xing' && $route['source'] === 'day_stem'))->toBeTrue()
        ->and($routes->contains(fn (array $route): bool => $route['type'] === 'hai' && $route['source'] === 'middle_transmission'))->toBeTrue()
        ->and($routes->pluck('label'))->toContain('癸刑未', '子未六害（中传）')
        ->and($match->evidence['transmission_hits_grave'])->toBeTrue();
});

test('fourth fu accepts the querent year-life branch itself restraining the initial transmission', function () {
    $pan = zhunfu_pan_with_querent();
    $match = zhunfu_match($pan);

    expect($pan->get('context')['people'][0]['nianming'])->toBe(2)
        ->and($match->evidence['year_ming_rescues']['matched_via'])->toContain('nianming_ground');
});

test('fourth fu fails when neither year-life nor xingnian nor their upper gods restrain the initial transmission', function () {
    $pan = zhunfu_pan_with_querent();
    $data = $pan->toArray();
    $data['context']['people'][0]['nianming'] = 0;
    $data['context']['people'][0]['xingnian'] = 1;

    expect(zhunfu_match(new PanResult($data)))->toBeNull();
});

test('grave-star part of sixth zhun remains necessary', function () {
    $pan = zhunfu_pan_with_querent();
    $data = $pan->toArray();
    $data['sanchuan1'] = 1;

    expect(zhunfu_match(new PanResult($data)))->toBeNull();
});
