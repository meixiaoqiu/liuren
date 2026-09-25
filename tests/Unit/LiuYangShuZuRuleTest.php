<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\LiuYangShuZuRule;
use App\Domain\Pan\Facts\PanFacts;

function lysz_match(array $overrides = []): mixed
{
    $base = [
        'sike' => [0, 0, 0, 2, 0, 4, 0, 6],
        'sanchuan0' => 0, 'sanchuan1' => 8, 'sanchuan2' => 10,
        'context' => ['people' => []], 'guirenPeriod' => 'day',
    ];

    return (new LiuYangShuZuRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

test('第五法公开两条有序且互斥的成立路线', function () {
    $definition = (new LiuYangShuZuRule)->definition();
    expect(array_column($definition['foundations'], 'code'))->toBe(['six_yang', 'five_yang_filled_by_person'])
        ->and(array_column($definition['judgments'], 'label'))->toBe(['公用明白·利公不利私', '悖戾格', '自夜传昼'])
        ->and(collect($definition['judgments'])->firstWhere('label', '自夜传昼')['effect'])->toBe('increase');
});

test('六阳六位全阳且重复阳支合法并不依赖人物资料', function () {
    $match = lysz_match(['sike' => [0, 0, 0, 0, 0, 2, 0, 2], 'sanchuan0' => 2, 'sanchuan1' => 0, 'sanchuan2' => 2]);
    expect($match?->matchedRoutes)->toBe(['six_yang'])
        ->and($match?->pendingRoutes)->toBe([])
        ->and($match?->evidence['positions'])->toBe([0, 0, 2, 2, 0, 2]);
});

test('初传不在四课上神则六阳不成立', function () {
    expect(lysz_match(['sanchuan0' => 10]))->toBeNull();
});

test('五阳一阴可由本命或行年阳支填实', function (array $person) {
    $match = lysz_match(['sike' => [0, 0, 0, 2, 0, 4, 0, 1], 'context' => ['people' => [['role' => 'querent', ...$person]]]]);
    expect($match?->matchedRoutes)->toBe(['five_yang_filled_by_person'])
        ->and($match?->pendingRoutes)->toBe([]);
})->with([
    '本命阳' => [['nianming' => 2]],
    '行年阳' => [['xingnian' => 4]],
]);

test('五阳一阴人物资料边界严格区分不成立与待评估', function (array $people, ?array $pending) {
    $match = lysz_match(['sike' => [0, 0, 0, 2, 0, 4, 0, 1], 'context' => ['people' => $people]]);
    expect($match?->matchedRoutes ?? [])->toBe([])
        ->and($match?->pendingRoutes ?? [])->toBe($pending ?? []);
    if ($pending === null) {
        expect($match)->toBeNull();
    }
})->with([
    '本命行年均阴' => [[['role' => 'querent', 'nianming' => 1, 'xingnian' => 3]], null],
    '全部缺失' => [[], ['five_yang_filled_by_person']],
    '本命阴行年缺失' => [[['role' => 'querent', 'nianming' => 1]], ['five_yang_filled_by_person']],
    '行年阴本命缺失' => [[['role' => 'querent', 'xingnian' => 3]], ['five_yang_filled_by_person']],
]);

test('四阳二阴在人物资料缺失时也不待评估', function () {
    expect(lysz_match(['sike' => [0, 0, 0, 2, 0, 1, 0, 3]]))->toBeNull();
});

test('填实检查年命地支本身而非对应天盘上神', function () {
    $match = lysz_match([
        'sike' => [0, 0, 0, 2, 0, 4, 0, 1], 'tianpan' => [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        'context' => ['people' => [['role' => 'querent', 'nianming' => 0, 'xingnian' => 1]]],
    ]);
    expect($match?->matchedRoutes)->toBe(['five_yang_filled_by_person']);
});

test('退间传不取消六阳并产生悖戾格减损断义', function () {
    $match = lysz_match(['sike' => [0, 2, 0, 10, 0, 0, 0, 2], 'sanchuan0' => 2, 'sanchuan1' => 0, 'sanchuan2' => 10]);
    expect($match?->matchedRoutes)->toBe(['six_yang'])
        ->and($match?->matchedJudgments)->toContain(['label' => '悖戾格', 'effect' => 'reduce', 'description' => '三传退间，又称倒拔蛇；第五法仍然成立，但事情间阻、艰辛。']);
    expect(lysz_match(['sike' => [0, 1, 0, 3, 0, 5, 0, 7], 'sanchuan0' => 1, 'sanchuan1' => 11, 'sanchuan2' => 9]))->toBeNull();
});

test('夜地初传与昼方末传产生自夜传昼且不受贵人昼夜影响', function (string $period) {
    $match = lysz_match(['sike' => [0, 0, 0, 2, 0, 4, 0, 6], 'sanchuan0' => 0, 'sanchuan1' => 8, 'sanchuan2' => 6, 'guirenPeriod' => $period]);
    $judgment = collect($match?->matchedJudgments ?? [])->firstWhere('label', '自夜传昼');
    expect($judgment)->not->toBeNull()
        ->and($judgment['effect'])->toBe('increase');
})->with(['day', 'night']);

test('昼方初传与昼方末传不误判自夜传昼', function () {
    $match = lysz_match(['sike' => [0, 4, 0, 2, 0, 6, 0, 8], 'sanchuan0' => 4, 'sanchuan1' => 8, 'sanchuan2' => 6]);
    expect(array_column($match?->matchedJudgments ?? [], 'label'))->not->toContain('自夜传昼');
});

test('非法地支事实安全返回空', function () {
    expect(lysz_match(['sike' => [0, 12, 0, 2, 0, 4, 0, 6]]))->toBeNull()
        ->and(lysz_match(['sanchuan2' => -1]))->toBeNull();
});
