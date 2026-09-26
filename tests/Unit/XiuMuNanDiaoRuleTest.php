<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\XiuMuNanDiaoRule;
use App\Domain\Pan\Facts\PanFacts;

function xmnd_plate(int $maoGround): array
{
    $plate = range(0, 11);
    $oldGround = array_search(3, $plate, true);
    [$plate[$maoGround], $plate[$oldGround]] = [$plate[$oldGround], $plate[$maoGround]];

    return $plate;
}

function xmnd_match(array $overrides = []): mixed
{
    $base = [
        'rigan' => 6,
        'rizhi' => 10,
        'sanchuan0' => 3,
        'tianpan' => xmnd_plate(8),
    ];

    return (new XiuMuNanDiaoRule)->match(
        PanFacts::from(new PanResult(array_replace($base, $overrides))),
    );
}

test('第十法定义、法号和法名固定为两个独立分格', function () {
    $rule = new XiuMuNanDiaoRule;
    $law = $rule->law();
    $definition = $rule->definition();

    expect($rule->code())->toBe('bifa.10')
        ->and($law['number'])->toBe(10)
        ->and($law['name'])->toBe('朽木难雕别作为')
        ->and(array_column($definition['foundations'], 'code'))->toBe(['rotten_wood', 'axe_unfavorable'])
        ->and(array_column($definition['foundations'], 'title'))->toBe(['朽木难雕', '斧斤不利']);
});

test('卯发用且卯临申酉戌并旬空均成立朽木难雕', function (int $ground) {
    // 庚戌日为甲辰旬，寅卯空；旬空由真实日干支推得。
    $match = xmnd_match(['tianpan' => xmnd_plate($ground)]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toBe(['rotten_wood'])
        ->and($match->subMatches[0]['title'])->toBe('朽木难雕')
        ->and(array_column($match->matchedJudgments, 'label'))->toBe(['宜别作为'])
        ->and($match->evidence)->toMatchArray([
            'mao_ground' => $ground,
            'mao_ground_identity' => $ground,
            'mao_void' => true,
            'mao_ground_void' => false,
            'is_zhuolun_position' => true,
        ]);
})->with([
    '卯加申' => [8],
    '卯加酉' => [9],
    '卯加辛位即戌宫' => [10],
]);

test('丁丑日口径卯临申或酉且金地旬空成立斧斤不利', function (int $ground) {
    // 丁丑日为甲戌旬，申酉空而卯不空。
    $match = xmnd_match([
        'rigan' => 3,
        'rizhi' => 1,
        'tianpan' => xmnd_plate($ground),
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toBe(['axe_unfavorable'])
        ->and($match->subMatches[0]['title'])->toBe('斧斤不利')
        ->and(array_column($match->matchedJudgments, 'label'))->toBe(['斧斤不利'])
        ->and($match->evidence['mao_ground'])->toBe($ground)
        ->and($match->evidence['mao_void'])->toBeFalse()
        ->and($match->evidence['mao_ground_void'])->toBeTrue();
})->with(['申空' => [8], '酉空' => [9]]);

test('卯空但不临申酉戌不得成立', function () {
    expect(xmnd_match(['tianpan' => xmnd_plate(7)]))->toBeNull();
});

test('斫轮位置成立但卯与刀斧地均不空不得成立', function () {
    // 甲子日戌亥空；卯临申，卯与申均不空。
    expect(xmnd_match(['rigan' => 0, 'rizhi' => 0]))->toBeNull();
});

test('初传不是卯不得成立', function () {
    expect(xmnd_match(['sanchuan0' => 2]))->toBeNull();
});

test('卯临戌且戌空而卯不空不得扩张为斧斤不利', function () {
    // 甲子日戌亥空；戌宫不属于本轮斧斤不利的申酉范围。
    expect(xmnd_match([
        'rigan' => 0,
        'rizhi' => 0,
        'tianpan' => xmnd_plate(10),
    ]))->toBeNull();
});

test('资料缺失或非法天盘安全关闭', function (mixed $tianpan) {
    expect(xmnd_match(['tianpan' => $tianpan]))->toBeNull();
})->with([
    '缺失' => [null],
    '空数组' => [[]],
    '不足十二宫' => [[0, 1, 2, 3]],
    '重复地支' => [array_fill(0, 12, 3)],
    '越界地支' => [[0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12]],
    '非整数地支' => [[0, 1, 2, '卯', 4, 5, 6, 7, 8, 9, 10, 11]],
]);

test('朽木难雕与斧斤不利严格排他而不会同时命中', function () {
    $rotten = xmnd_match();
    $axe = xmnd_match(['rigan' => 3, 'rizhi' => 1]);

    expect($rotten->matchedRoutes)->toBe(['rotten_wood'])
        ->and($rotten->matchedRoutes)->not->toContain('axe_unfavorable')
        ->and($axe->matchedRoutes)->toBe(['axe_unfavorable'])
        ->and($axe->matchedRoutes)->not->toContain('rotten_wood');
});
