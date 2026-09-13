<?php

/** 文件作用：锁定天网课“时支克日 AND 初传克日”的精确语义，并保护同克非同支、时干不参与及正文候选生产盘边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\TianwangRule;
use App\Services\PanCalculator;

function tianwang_facts(array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'rigan' => 6,
        'rizhi' => 4,
        'shigan' => 0,
        'shizhi' => 6,
        'sanchuan0' => 6,
        'sanchuan1' => 4,
        'sanchuan2' => 2,
    ], $changes)));
}

test('time branch and initial transmission both restraining the day stem match', function () {
    $match = (new TianwangRule)->match(tianwang_facts());

    expect($match)->not->toBeNull()
        ->and($match->evidence['time_restrains_day'])->toBeTrue()
        ->and($match->evidence['initial_restrains_day'])->toBeTrue()
        ->and($match->evidence['time_equals_initial'])->toBeTrue();
});

test('different time and initial branches still match when both restrain the day stem', function () {
    $match = (new TianwangRule)->match(tianwang_facts([
        'rigan' => 0,
        'shizhi' => 9,
        'sanchuan0' => 8,
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['time_equals_initial'])->toBeFalse()
        ->and($match->evidence['time_branch'])->toBe(9)
        ->and($match->evidence['initial'])->toBe(8);
});

test('both necessary conditions are independently required', function (array $changes) {
    expect((new TianwangRule)->match(tianwang_facts($changes)))->toBeNull();
})->with([
    'only time restrains day' => [['sanchuan0' => 0]],
    'only initial restrains day' => [['shizhi' => 0]],
    'neither restrains day' => [['shizhi' => 0, 'sanchuan0' => 0]],
]);

test('hour stem does not substitute for the hour branch', function () {
    expect((new TianwangRule)->match(tianwang_facts([
        'rigan' => 0,
        'shigan' => 6,
        'shizhi' => 0,
        'sanchuan0' => 8,
    ])))->toBeNull();
});

test('missing or invalid required facts safely reject', function (array $changes) {
    expect((new TianwangRule)->match(tianwang_facts($changes)))->toBeNull();
})->with([
    [['rigan' => null]],
    [['shizhi' => '6']],
    [['sanchuan0' => 12]],
]);

test('metadata evidence and registry order are stable', function () {
    $match = (new TianwangRule)->match(tianwang_facts());
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());

    expect([$match?->code, $match?->name, $match?->gua, $match?->guaSymbol])
        ->toBe(['lesson.tianwang', '天网课', '蒙', '䷃'])
        ->and($match?->description)->toBe('占时与发用同为日鬼，即占时支和初传分别克日干。')
        ->and($match?->evidence)->toHaveKeys([
            'day_stem', 'day_stem_element', 'time_branch', 'time_branch_element', 'time_restrains_day',
            'initial', 'initial_element', 'initial_restrains_day', 'time_equals_initial', 'foundations', 'uncovered',
        ])
        ->and(array_values(array_filter($codes, fn (string $code): bool => $code === 'lesson.tianwang')))->toHaveCount(1)
        ->and(array_search('lesson.tianwang', $codes, true))->toBe(array_search('lesson.tiankou', $codes, true) + 1);
});

test('geng-chen noon chen-general candidate preserves production plate but does not reproduce ancient transmissions', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2028-10-22 11:00:00'));

    expect([$facts->get('rigan'), $facts->get('rizhi'), $facts->get('shizhi'), $facts->get('yuejiang')])->toBe([6, 4, 6, 4])
        ->and($facts->get('tianpan'))->toBe([10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9])
        ->and($facts->get('sike'))->toBe([6, 6, 6, 4, 4, 2, 2, 0])
        ->and([$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')])->toBe([2, 0, 10])
        ->and((new TianwangRule)->match($facts))->toBeNull();
});
