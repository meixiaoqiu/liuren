<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RongHuaRule;
use App\Services\PanCalculator;

function ronghua_pan_for(string $datetime): PanResult
{
    return (new PanCalculator)->calculate($datetime);
}

function ronghua_match(PanResult $pan): mixed
{
    return (new RongHuaRule)->match(PanFacts::from($pan));
}

test('rong-hua reproduces the daquan bing-yin horse-initial structure', function () {
    $match = ronghua_match(ronghua_pan_for('2001-05-03 11:00:00'));

    expect($match)->not->toBeNull()
        ->and($match->evidence['terms']['lu'])->toMatchArray(['branch' => 5])
        ->and($match->evidence['terms']['ma'])->toMatchArray(['branch' => 8])
        ->and($match->evidence['terms']['gui_ren'])->toMatchArray(['branch' => 11])
        ->and($match->evidence['initial'])->toBe(['branch' => 8, 'wang_xiang' => true]);
});

test('rong-hua includes the daquan ren-shen nobleman-initial structure', function () {
    // 壬申日：干上寅马、支上亥禄，三传巳申亥；巳贵人旺相发用。
    $match = ronghua_match(ronghua_pan_for('2000-03-15 15:00:00'));

    expect($match)->not->toBeNull()
        ->and($match->evidence['initial'])->toBe(['branch' => 5, 'wang_xiang' => true])
        ->and($match->evidence['terms']['gui_ren']['positions'])->toContain('initial');
});

test('rong-hua reproduces the detailed bing-shen lu-initial structure even though initial rides sky', function () {
    $pan = new PanResult([
        'context' => ['people' => []],
        'tianpan' => [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8],
        'tianjiang' => [10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        'rigan' => 2,
        'rizhi' => 8,
        'sanchuan0' => 5,
        'sanchuan1' => 2,
        'sanchuan2' => 11,
        'yuezhi' => 2,
        'guirenPeriod' => 'day',
    ]);

    $match = ronghua_match($pan);

    expect($match)->not->toBeNull()
        ->and($match->evidence['initial'])->toBe(['branch' => 5, 'wang_xiang' => true])
        ->and(array_column($match->evidence['auspicious_transmission_terms'], 'position'))
        ->toContain('middle', 'final');
});

test('rong-hua allows the birth-year upper to complete the three terms', function () {
    $calculator = new PanCalculator;
    $calculated = $calculator->calculate('2026-02-06 10:00:00');

    expect(ronghua_match($calculated))->toBeNull();

    $birthIndex = $calculator->calculate('1986-08-18 12:17:00')->get('nian_index');
    $fate = (new FateCalculator)->calculate($birthIndex, $calculated->get('nian_index'), 'male');
    $match = ronghua_match(new PanResult([...$calculated->toArray(), ...$fate]));

    expect($match)->not->toBeNull()
        ->and(array_merge(...array_column($match->evidence['terms'], 'positions')))
        ->toContain('ming_upper');
});

test('rong-hua allows the annual-fate upper to complete the three terms', function () {
    $calculator = new PanCalculator;
    $calculated = $calculator->calculate('2026-03-18 18:00:00');

    expect(ronghua_match($calculated))->toBeNull();

    $birthIndex = $calculator->calculate('1986-08-18 12:17:00')->get('nian_index');
    $fate = (new FateCalculator)->calculate($birthIndex, $calculated->get('nian_index'), 'female');
    $match = ronghua_match(new PanResult([...$calculated->toArray(), ...$fate]));

    expect($match)->not->toBeNull()
        ->and(array_merge(...array_column($match->evidence['terms'], 'positions')))
        ->toContain('xingnian_upper');
});

test('rong-hua requires lu horse and nobleman all to be present', function () {
    $data = ronghua_pan_for('2001-05-03 11:00:00')->toArray();
    $data['tianpan'][2] = 4;
    $data['sanchuan0'] = 8;
    $data['sanchuan1'] = 10;
    $data['sanchuan2'] = 2;

    expect(ronghua_match(new PanResult($data)))->toBeNull();
});

test('rong-hua requires one of the three terms to be the initial transmission', function () {
    $data = ronghua_pan_for('2001-05-03 11:00:00')->toArray();
    $data['sanchuan0'] = 6;

    expect(ronghua_match(new PanResult($data)))->toBeNull();
});

test('rong-hua requires the initial transmission to be seasonally wang or xiang', function () {
    $data = ronghua_pan_for('2001-05-03 11:00:00')->toArray();
    $data['calculationTime'] = '2001-01-10 11:00:00';

    expect(ronghua_match(new PanResult($data)))->toBeNull();
});

test('rong-hua requires a related transmission term to ride an auspicious general', function () {
    $data = ronghua_pan_for('2001-05-03 11:00:00')->toArray();
    foreach ($data['sanchuan'] ?? [$data['sanchuan0'], $data['sanchuan1'], $data['sanchuan2']] as $branch) {
        $ground = array_search($branch, $data['tianpan'], true);
        $data['tianjiang'][$ground] = 1;
    }

    expect(ronghua_match(new PanResult($data)))->toBeNull();
});

test('rong-hua does not match without the nobleman period', function () {
    $data = ronghua_pan_for('2001-05-03 11:00:00')->toArray();
    unset($data['guirenPeriod']);

    expect(ronghua_match(new PanResult($data)))->toBeNull();
});

test('rule engine integrates the adopted rong-hua formula', function () {
    $codes = array_column((new PanRuleEngine)->evaluate(ronghua_pan_for('2000-03-15 15:00:00')), 'code');

    expect($codes)->toContain('lesson.rong_hua');
});
