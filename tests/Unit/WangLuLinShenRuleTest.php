<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\WangLuLinShenRule;
use App\Domain\Pan\Facts\PanFacts;

function wlls_match(array $overrides = []): mixed
{
    $base = [
        'rigan' => 1, 'rizhi' => 3,
        'sike' => [1, 3, 3, 5, 3, 5, 5, 7],
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 0),
    ];

    return (new WangLuLinShenRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

function wlls_general_on_lu(int $lu, int $general): array
{
    $generals = array_fill(0, 12, 0);
    $generals[$lu] = $general;

    return $generals;
}

test('第七法定义只有旺禄临身一条成立路线', function () {
    $definition = (new WangLuLinShenRule)->definition();

    expect(array_column($definition['foundations'], 'code'))->toBe(['wang_lu_on_stem'])
        ->and(array_column($definition['foundations'], 'title'))->toBe(['旺禄临身'])
        ->and(array_column($definition['judgments'], 'label'))->toBe([
            '宜守旺禄', '旺禄旬空', '闭口禄', '禄被玄武夺', '旺禄乘白虎',
        ]);
});

test('五个阴干各以本干日禄临干成立且日支不参与', function (int $stem, int $lu, int $branch) {
    $match = wlls_match(['rigan' => $stem, 'rizhi' => $branch, 'sike' => [$stem, $lu]]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toBe(['wang_lu_on_stem']);
})->with([
    '乙卯' => [1, 3, 0], '丁午' => [3, 6, 1], '己午' => [5, 6, 2],
    '辛酉' => [7, 9, 4], '癸子' => [9, 0, 5],
]);

test('阴干干上不是本干日禄不成立', function () {
    expect(wlls_match(['rigan' => 1, 'sike' => [1, 2]]))->toBeNull();
});

test('五个阳干即使日禄临干也不成立', function (int $stem, int $lu) {
    expect(wlls_match(['rigan' => $stem, 'sike' => [$stem, $lu]]))->toBeNull();
})->with(['甲寅' => [0, 2], '丙巳' => [2, 5], '戊巳' => [4, 5], '庚申' => [6, 8], '壬亥' => [8, 11]]);

test('旺禄旬空不取消成立且不输出宜守旺禄', function () {
    // 辛巳为甲戌旬，申酉空；辛禄酉。
    $match = wlls_match(['rigan' => 7, 'rizhi' => 5, 'sike' => [7, 9]]);
    $labels = array_column($match->matchedJudgments, 'label');

    expect($match->matchedRoutes)->toBe(['wang_lu_on_stem'])
        ->and($labels)->toContain('旺禄旬空')
        ->and($labels)->not->toContain('宜守旺禄');
});

test('闭口禄由旬首算法自然推出且不要求课经闭口结构', function (int $stem, int $branch, int $lu) {
    $match = wlls_match(['rigan' => $stem, 'rizhi' => $branch, 'sike' => [$stem, $lu], 'sanchuan0' => 0]);

    expect($match->evidence['day_lu'])->toBe($lu)
        ->and($match->evidence['xun_tail'])->toBe($lu)
        ->and(array_column($match->matchedJudgments, 'label'))->toContain('闭口禄')
        ->not->toContain('宜守旺禄');
})->with(['乙未' => [1, 7, 3], '辛未' => [7, 7, 9]]);

test('日禄不等于旬尾不会误报闭口禄', function () {
    // 乙卯为甲寅旬，旬尾亥；乙禄卯。
    $match = wlls_match(['rigan' => 1, 'rizhi' => 3, 'sike' => [1, 3]]);

    expect($match->evidence['day_lu'])->not->toBe($match->evidence['xun_tail'])
        ->and(array_column($match->matchedJudgments, 'label'))->not->toContain('闭口禄');
});

test('旺禄乘玄武追加禄被玄武夺且不宜守', function () {
    $match = wlls_match(['tianjiang' => wlls_general_on_lu(3, 9)]);
    $labels = array_column($match->matchedJudgments, 'label');
    $effects = array_column($match->matchedJudgments, 'effect', 'label');

    expect($labels)->toContain('禄被玄武夺')->not->toContain('宜守旺禄')
        ->and($effects['禄被玄武夺'])->toBe('resolve');
});

test('旺禄只乘白虎时作减损并仍然宜守', function () {
    $match = wlls_match(['tianjiang' => wlls_general_on_lu(3, 7)]);
    $labels = array_column($match->matchedJudgments, 'label');
    $effects = array_column($match->matchedJudgments, 'effect', 'label');

    expect($labels)->toContain('旺禄乘白虎', '宜守旺禄')
        ->and($effects['旺禄乘白虎'])->toBe('reduce')
        ->and($effects['宜守旺禄'])->toBe('neutral');
});

test('普通旺禄只输出宜守旺禄', function () {
    $match = wlls_match();

    expect(array_column($match->matchedJudgments, 'label'))->toBe(['宜守旺禄']);
});

test('旬空与白虎可以并存且由旬空解除宜守', function () {
    $match = wlls_match([
        'rigan' => 7, 'rizhi' => 5, 'sike' => [7, 9],
        'tianjiang' => wlls_general_on_lu(9, 7),
    ]);
    $labels = array_column($match->matchedJudgments, 'label');
    $effects = array_column($match->matchedJudgments, 'effect', 'label');

    expect($labels)->toContain('旺禄旬空', '旺禄乘白虎')
        ->not->toContain('宜守旺禄')
        ->and($effects['旺禄旬空'])->toBe('resolve')
        ->and($effects['旺禄乘白虎'])->toBe('reduce');
});

test('闭口禄与白虎可以并存且由闭口禄解除宜守', function () {
    $match = wlls_match([
        'rigan' => 7, 'rizhi' => 7, 'sike' => [7, 9],
        'tianjiang' => wlls_general_on_lu(9, 7),
    ]);
    $labels = array_column($match->matchedJudgments, 'label');
    $effects = array_column($match->matchedJudgments, 'effect', 'label');

    expect($labels)->toContain('闭口禄', '旺禄乘白虎')
        ->not->toContain('宜守旺禄')
        ->and($effects['闭口禄'])->toBe('resolve')
        ->and($effects['旺禄乘白虎'])->toBe('reduce');
});

test('主体成立但特殊判断事实不完整时不伪造宜守判断', function () {
    $match = wlls_match([
        'rizhi' => null,
        'tianpan' => null,
        'tianjiang' => null,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toBe(['wang_lu_on_stem'])
        ->and($match->evidence['xun_head'])->toBeNull()
        ->and($match->evidence['xun_tail'])->toBeNull()
        ->and($match->evidence['lu_void'])->toBeNull()
        ->and($match->evidence['closed_mouth_lu'])->toBeNull()
        ->and($match->evidence['lu_general'])->toBeNull()
        ->and($match->matchedJudgments)->toBe([]);
});

test('非法四课或日干安全返回空结果', function (array $overrides) {
    expect(wlls_match($overrides))->toBeNull();
})->with([
    '日干缺失' => [['rigan' => null]],
    '日干越界' => [['rigan' => 10]],
    '四课非数组' => [['sike' => null]],
    '四课不足' => [['sike' => [1]]],
    '干上神非整数' => [['sike' => [1, '卯']]],
    '干上神越界' => [['sike' => [1, 12]]],
]);
