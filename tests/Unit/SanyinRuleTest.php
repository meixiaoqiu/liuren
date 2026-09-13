<?php

/** 文件作用：锁定三阴课六项 AND、同一初传约束、贵后固定天将集合及行年必要上下文。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\SanyinRule;
use App\Services\PanCalculator;

function sanyin_pan(array $changes = []): PanResult
{
    return new PanResult(array_replace([
        'calculationTime' => '2025-12-10 09:00:00',
        'rigan' => 9, 'rizhi' => 1, 'shizhi' => 5,
        'tianpan' => range(0, 11),
        // 逆行；癸寄丑、日支丑在地盘丑乘天后，初传巳乘白虎。
        'tianjiang' => [0, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 8]]],
    ], $changes));
}

function sanyin_match(array $changes = []): mixed
{
    return (new SanyinRule)->match(PanFacts::from(sanyin_pan($changes)));
}

test('qiu and si initial states both match and white tiger or black tortoise both match', function () {
    $si = sanyin_match();
    $qiuPan = sanyin_pan(['sanchuan0' => 1, 'tianpan' => [0, 5, 2, 3, 4, 1, 6, 7, 8, 9, 10, 11]]);
    $qiu = (new SanyinRule)->match(PanFacts::from($qiuPan));
    $xuanPan = sanyin_pan(['sanchuan0' => 1, 'tianpan' => [0, 4, 2, 1, 3, 5, 6, 7, 8, 9, 10, 11]]);
    $xuan = (new SanyinRule)->match(PanFacts::from($xuanPan));

    expect($si?->evidence['initial_transmission'])->toMatchArray(['seasonal_state' => '死', 'general' => 7])
        ->and($qiu?->evidence['initial_transmission'])->toMatchArray(['seasonal_state' => '囚', 'general' => 7])
        ->and($xuan?->evidence['initial_transmission'])->toMatchArray(['seasonal_state' => '囚', 'general' => 9]);
});

test('rear general boundaries are fixed sky through heaven queen', function () {
    $skyGenerals = [0, 11, 10, 9, 8, 6, 6, 5, 4, 3, 2, 1];
    $sky = PanFacts::from(sanyin_pan(['rigan' => 2, 'rizhi' => 6, 'tianjiang' => $skyGenerals]));
    $queen = PanFacts::from(sanyin_pan());
    expect($sky->noblemanRearGeneralRankAtGroundPosition(5))->toBe(6)
        ->and($sky->noblemanRearGeneralRankAtGroundPosition(6))->toBe(6)
        ->and($queen->noblemanRearGeneralRankAtGroundPosition(1))->toBe(11)
        ->and($queen->noblemanFrontGeneralRankAtGroundPosition(1))->toBeNull();
});

test('classic guichou plate structure closes every frozen matcher condition', function () {
    $match = sanyin_match();
    expect($match)->not->toBeNull()
        ->and($match->evidence['nobleman_forward'])->toBeFalse()
        ->and($match->evidence['day_stem']['lodging_branch'])->toBe(1)
        ->and($match->evidence['day_stem']['rear_general_rank'])->toBe(11)
        ->and($match->evidence['day_branch']['rear_general_rank'])->toBe(11)
        ->and($match->evidence['initial_transmission'])->toMatchArray(['branch' => 5, 'seasonal_state' => '死', 'general' => 7])
        ->and($match->evidence['time_restrains_xingnian'])->toBeTrue();
});

test('each directional and day-or-branch rear condition is independently necessary', function (array $changes) {
    expect(sanyin_match($changes))->toBeNull();
})->with([
    '贵人顺行' => [['tianjiang' => range(0, 11)]],
    '日干在前日支在后' => [['rigan' => 0, 'tianjiang' => [0, 11, 5, 9, 8, 7, 6, 5, 4, 3, 2, 1]]],
    '日干在后日支在前' => [['rizhi' => 11]],
]);

test('wang xiang and xiu initial states do not match', function (string $datetime, int $initial, array $tianpan) {
    expect(sanyin_match(['calculationTime' => $datetime, 'sanchuan0' => $initial, 'tianpan' => $tianpan]))->toBeNull();
})->with([
    '旺' => ['2025-12-10 09:00:00', 0, [5, 1, 2, 3, 4, 0, 6, 7, 8, 9, 10, 11]],
    '相' => ['2025-12-10 09:00:00', 2, [0, 1, 5, 3, 4, 2, 6, 7, 8, 9, 10, 11]],
    '休' => ['2025-12-10 09:00:00', 8, [0, 1, 2, 3, 4, 8, 6, 7, 5, 9, 10, 11]],
]);

test('xuanhu and seasonal state must belong to the same initial transmission', function () {
    expect(sanyin_match(['sanchuan0' => 4]))->toBeNull() // 囚土，但初传辰乘太常；盘中仍有白虎、玄武。
        ->and(sanyin_match(['sanchuan0' => 2]))->toBeNull(); // 初传寅乘玄武，但冬季为相。
});

test('other transmission being qiu or si cannot replace the initial condition', function () {
    expect(sanyin_match(['sanchuan0' => 2, 'sanchuan1' => 5, 'sanchuan2' => 1]))->toBeNull();
});

test('time branch must restrain xingnian and time stem is irrelevant', function () {
    expect(sanyin_match(['shizhi' => 0, 'shigan' => 2]))->toBeNull()
        ->and(sanyin_match(['context' => ['people' => [['role' => 'querent', 'xingnian' => 1]]]]))->toBeNull();
});

test('xingnian is required context and missing data is not evaluated', function () {
    $rule = new SanyinRule;
    $pan = sanyin_pan(['context' => ['people' => [['role' => 'querent']]]]);
    $engine = new PanRuleEngine;
    expect($rule->requiredContext())->toBe(['people.querent.xingnian'])
        ->and($rule->notEvaluatedInfo()['notice'])->toContain('当前未进行三阴课判断')
        ->and(collect($engine->evaluate($pan))->pluck('code'))->not->toContain('lesson.sanyin')
        ->and(collect($engine->notEvaluated($pan))->pluck('code'))->toContain('lesson.sanyin');
});

test('production candidate reproduces the complete modern executable case', function () {
    $calculator = new PanCalculator;
    $pan = $calculator->calculate('2025-12-10 09:00:00');
    $birthYear = $calculator->calculate('1959-08-01 00:00:00')->get('nian_index');
    $fate = (new FateCalculator)->calculate($birthYear, $pan->get('nian_index'), 'male');
    $pan = new PanResult([...$pan->toArray(), 'context' => ['people' => [['role' => 'querent', ...$fate]]]]);
    $facts = PanFacts::from($pan);
    $match = (new SanyinRule)->match($facts);

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('yuejiang'), $pan->get('shizhi')])->toBe([9, 1, 2, 5])
        ->and($pan->get('tianpan'))->toBe([9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8])
        ->and($pan->get('sike'))->toBe([9, 10, 10, 7, 1, 10, 10, 7])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([10, 7, 4])
        ->and($facts->isNoblemanMovingForward())->toBeFalse()
        ->and($pan->get('tianjiang'))->toBe([8, 7, 6, 5, 4, 3, 2, 1, 0, 11, 10, 9])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['nobleman'])->toBe(5)->and($match->evidence['nobleman_ground'])->toBe(8)
        ->and($match->evidence['day_stem']['general'])->toBe(7)->and($match->evidence['day_branch']['general'])->toBe(7)
        ->and($match->evidence['initial_transmission'])->toMatchArray(['branch' => 10, 'seasonal_state' => '囚', 'general' => 7])
        ->and($fate['xingnian'])->toBe(8)->and($match->evidence['time_restrains_xingnian'])->toBeTrue();
});
