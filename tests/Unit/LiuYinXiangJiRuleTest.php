<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\Facts\PanFacts;

function lyxj_match(array $overrides = []): mixed
{
    $base = [
        'sike' => [9, 1, 2, 3, 4, 5, 6, 7],
        'sanchuan0' => 1, 'sanchuan1' => 9, 'sanchuan2' => 11,
        'context' => ['people' => []], 'guirenPeriod' => 'day',
    ];

    return (new LiuYinXiangJiRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

test('第六法公开三条有序成立路线', function () {
    $definition = (new LiuYinXiangJiRule)->definition();
    expect(array_column($definition['foundations'], 'code'))->toBe([
        'six_yin', 'five_yin_filled_by_person', 'source_exhausted_root_severed',
    ]);
});

test('六阴按六个位置判定且允许重复支', function () {
    $match = lyxj_match(['sike' => [0, 1, 2, 1, 4, 3, 6, 3], 'sanchuan0' => 1, 'sanchuan1' => 5, 'sanchuan2' => 5]);
    expect($match?->matchedRoutes)->toContain('six_yin')
        ->and($match?->evidence['positions'])->toBe([1, 1, 3, 3, 5, 5]);
});

test('五阴一阳由本命或行年阴支填实', function (array $person) {
    $match = lyxj_match([
        'sike' => [0, 1, 2, 3, 4, 5, 6, 8],
        'context' => ['people' => [['role' => 'querent', ...$person]]],
    ]);
    expect($match?->matchedRoutes)->toContain('five_yin_filled_by_person')
        ->and($match?->pendingRoutes)->toBe([]);
})->with(['本命阴' => [['nianming' => 1]], '行年阴' => [['xingnian' => 11]]]);

test('五阴候选严格区分人物资料缺失与资料完整但不成立', function (array $people, ?array $pending) {
    $match = lyxj_match(['sike' => [0, 1, 2, 3, 4, 5, 6, 8], 'context' => ['people' => $people]]);
    expect($match?->matchedRoutes ?? [])->not->toContain('five_yin_filled_by_person')
        ->and($match?->pendingRoutes ?? [])->toBe($pending ?? []);
    if ($pending === null) {
        expect($match)->toBeNull();
    }
})->with([
    '完全缺失' => [[], ['five_yin_filled_by_person']],
    '本命阳行年缺失' => [[['role' => 'querent', 'nianming' => 0]], ['five_yin_filled_by_person']],
    '本命行年均阳' => [[['role' => 'querent', 'nianming' => 0, 'xingnian' => 2]], null],
]);

test('源消根断要求四课下生上且三传连续相生', function () {
    $strict = lyxj_match([
        'sike' => [9, 3, 5, 7, 3, 5, 5, 7],
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 5,
    ]);
    expect($strict?->matchedRoutes)->toContain('source_exhausted_root_severed')
        ->and($strict?->evidence['lesson_generations'])->toBe([true, true, true, true])
        ->and($strict?->evidence['transmission_generations'])->toBe([true, true]);

    $looseOnly = lyxj_match([
        'sike' => [9, 3, 5, 7, 3, 5, 5, 7],
        'sanchuan0' => 11, 'sanchuan1' => 1, 'sanchuan2' => 3,
    ]);
    expect($looseOnly?->evidence['lesson_generations'])->toBe([true, true, true, true])
        ->and($looseOnly?->evidence['transmission_generations'])->toBe([false, false])
        ->and($looseOnly?->matchedRoutes)->not->toContain('source_exhausted_root_severed');
});

test('己卯标准结构六阴成立但三传亥丑卯阻止源消根断', function () {
    $match = lyxj_match([
        'sike' => [5, 7, 5, 7, 3, 5, 5, 7],
        'sanchuan0' => 11, 'sanchuan1' => 1, 'sanchuan2' => 3,
    ]);
    expect($match?->matchedRoutes)->toContain('six_yin')
        ->and($match?->matchedRoutes)->not->toContain('source_exhausted_root_severed');
});

test('四个减损格严格按三传完整顺序判断', function (array $transmissions, string $label) {
    $match = lyxj_match([
        'sike' => [9, 1, 2, 3, 4, 5, 6, 7],
        'sanchuan0' => $transmissions[0], 'sanchuan1' => $transmissions[1], 'sanchuan2' => $transmissions[2],
    ]);
    expect(array_column($match?->matchedJudgments ?? [], 'label'))->toContain($label)
        ->and($match?->matchedRoutes)->toContain('six_yin');
})->with([
    '出户' => [[1, 3, 5], '出户'],
    '盈阳' => [[3, 5, 7], '盈阳'],
    '励明' => [[9, 7, 5], '励明'],
    '回明' => [[7, 5, 3], '回明'],
]);

test('减损格乱序不成立', function () {
    $match = lyxj_match(['sanchuan0' => 5, 'sanchuan1' => 3, 'sanchuan2' => 1]);
    expect(array_column($match?->matchedJudgments ?? [], 'label'))
        ->not->toContain('出户')->not->toContain('盈阳')->not->toContain('励明')->not->toContain('回明');
});

test('自昼传夜只按初末传方位判断', function (int $initial, int $final, bool $expected, string $period) {
    $match = lyxj_match([
        'sike' => [0, 3, 2, 5, 4, 7, 6, 9],
        'sanchuan0' => $initial, 'sanchuan1' => 11, 'sanchuan2' => $final,
        'guirenPeriod' => $period,
    ]);
    expect(in_array('自昼传夜', array_column($match?->matchedJudgments ?? [], 'label'), true))->toBe($expected);
})->with([
    '昼到夜' => [3, 9, true, 'day'],
    '昼到夜且实际夜占' => [3, 9, true, 'night'],
    '夜到昼' => [9, 3, false, 'day'],
    '昼到昼' => [3, 5, false, 'day'],
    '夜到夜' => [9, 11, false, 'night'],
]);

test('非法事实安全返回空', function () {
    expect(lyxj_match(['sike' => [0, 12, 2, 3, 4, 5, 6, 7]]))->toBeNull()
        ->and(lyxj_match(['sanchuan2' => -1]))->toBeNull();
});
