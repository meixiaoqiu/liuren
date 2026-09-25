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
    expect(array_column($definition['judgments'], 'label'))->toContain('六阴格')
        ->and(array_column($definition['judgments'], 'label'))->toContain('五阴年命填实');
});

test('六阴按六个位置判定且允许重复支', function () {
    $match = lyxj_match(['sike' => [0, 1, 2, 1, 4, 3, 6, 3], 'sanchuan0' => 1, 'sanchuan1' => 5, 'sanchuan2' => 5]);
    expect($match?->matchedRoutes)->toContain('six_yin')
        ->and($match?->evidence['positions'])->toBe([1, 1, 3, 3, 5, 5])
        ->and($match?->evidence['initial_from_lesson'])->toBeTrue();
});

test('六阴命中后 matchedJudgments 包含正文断义', function () {
    $match = lyxj_match(['sike' => [0, 1, 2, 1, 4, 3, 6, 3], 'sanchuan0' => 1, 'sanchuan1' => 5, 'sanchuan2' => 5]);
    $judgment = collect($match?->matchedJudgments ?? [])->firstWhere('label', '六阴格');
    expect($judgment)->not->toBeNull()
        ->and($judgment['effect'])->toBe('neutral')
        ->and($judgment['description'])->toBe('利阴谋私干，不利公闻，昏迷。');
});

test('初传不在四课上神则六阴不成立', function () {
    // 默认 fixture 上神为 [1, 3, 5, 7]；六位仍可全阴，但初传=9（酉）不在上神中。
    $match = lyxj_match(['sanchuan0' => 9, 'sanchuan1' => 1, 'sanchuan2' => 3]);
    expect($match?->matchedRoutes ?? [])->not->toContain('six_yin');
});

test('五阴一阳由本命或行年阴支填实', function (array $person) {
    $match = lyxj_match([
        'sike' => [0, 1, 2, 3, 4, 5, 6, 8],
        'context' => ['people' => [['role' => 'querent', ...$person]]],
    ]);
    expect($match?->matchedRoutes)->toContain('five_yin_filled_by_person')
        ->and($match?->pendingRoutes)->toBe([]);
})->with(['本命阴' => [['nianming' => 1]], '行年阴' => [['xingnian' => 11]]]);

test('五阴命中后 matchedJudgments 包含正文断义', function (array $person) {
    $match = lyxj_match([
        'sike' => [0, 1, 2, 3, 4, 5, 6, 8],
        'context' => ['people' => [['role' => 'querent', ...$person]]],
    ]);
    $judgment = collect($match?->matchedJudgments ?? [])->firstWhere('label', '五阴年命填实');
    expect($judgment)->not->toBeNull()
        ->and($judgment['effect'])->toBe('neutral')
        ->and($judgment['description'])->toBe('利私不利公，利小人不利君子。');
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

test('五阴候选初传不在四课上神则不成立且不待评估', function () {
    // 默认 fixture 上神为 [1, 3, 5, 7]；把初传放到 9（酉）——六位仍为五阴一阳，
    // 但初传不属于四课上神，故五阴候选不成立，资料缺失也不进入待评估。
    $match = lyxj_match(['sanchuan0' => 9, 'sanchuan1' => 1, 'sanchuan2' => 0]);
    expect($match)->toBeNull();
});

test('源消根断要求四课下生上且三传连续相生', function () {
    $strict = lyxj_match([
        'sike' => [9, 3, 5, 7, 3, 5, 5, 7],
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 5,
    ]);
    expect($strict?->matchedRoutes)->toContain('source_exhausted_root_severed')
        ->and($strict?->evidence['lesson_generations'])->toBe([true, true, true, true])
        ->and($strict?->evidence['transmission_generations'])->toBe([true, true]);

    // 四课仍全部下生上，但三传并非连续相生，因此源消根断不成立。
    // 同时给一个五阴填实的占人（行年 亥 = 阴支）确保 match 不会因为新代码的 null 提前退出，
    // 这样才能继续断言 lesson_generations / transmission_generations 的形态。
    $looseOnly = lyxj_match([
        'sike' => [9, 3, 5, 7, 3, 5, 5, 7],
        'sanchuan0' => 3, 'sanchuan1' => 1, 'sanchuan2' => 0,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 11]]],
    ]);
    expect($looseOnly?->evidence['lesson_generations'])->toBe([true, true, true, true])
        ->and($looseOnly?->evidence['transmission_generations'])->toBe([false, false])
        ->and($looseOnly?->matchedRoutes)->not->toContain('source_exhausted_root_severed')
        ->and($looseOnly?->matchedRoutes)->toContain('five_yin_filled_by_person');
});

test('己卯标准结构六阴成立但三传亥丑卯阻止源消根断', function () {
    // 上神中需含 11（亥），保证初传 11 属于四课上神。
    $match = lyxj_match([
        'sike' => [5, 9, 0, 11, 0, 5, 0, 7],
        'sanchuan0' => 11, 'sanchuan1' => 1, 'sanchuan2' => 3,
    ]);
    expect($match?->matchedRoutes)->toContain('six_yin')
        ->and($match?->matchedRoutes)->not->toContain('source_exhausted_root_severed')
        ->and($match?->evidence['lesson_uppers'])->toBe([9, 11, 5, 7]);
});

test('四个减损格严格按三传完整顺序判断', function (array $transmissions, string $label, array $sike) {
    $match = lyxj_match([
        'sike' => $sike,
        'sanchuan0' => $transmissions[0], 'sanchuan1' => $transmissions[1], 'sanchuan2' => $transmissions[2],
    ]);
    expect(array_column($match?->matchedJudgments ?? [], 'label'))->toContain($label)
        ->and($match?->matchedRoutes)->toContain('six_yin');
})->with([
    '出户' => [[1, 3, 5], '出户', [9, 1, 2, 3, 4, 5, 6, 7]],
    '盈阳' => [[3, 5, 7], '盈阳', [9, 1, 2, 3, 4, 5, 6, 7]],
    // 励明初传=9（酉），需要让 9 出现在四课上神中：上神全设为酉即可。
    '励明' => [[9, 7, 5], '励明', [0, 9, 0, 9, 0, 9, 0, 9]],
    '回明' => [[7, 5, 3], '回明', [9, 1, 2, 3, 4, 5, 6, 7]],
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
