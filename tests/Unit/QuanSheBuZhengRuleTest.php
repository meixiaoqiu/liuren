<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\QuanSheBuZhengRule;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 第八法单元测试：
 *  - 主体仅一条路线 sike[5] === DAY_LU[rigan]，十干全部参与；
 *  - 减损判断（禄受墓 / 禄受支克 / 禄受支脱）独立判定，可重叠；
 *  - 输入防御覆盖非法 rigan、sike 缺失、sike[5] 缺失、sike[5] 非 0..11。
 */

/**
 * 构造一个最小可用 PanFacts：sike[4] = rizhi，sike[5] = 上神，其他位置填充占位。
 * tianpan 全 0..11；tianjiang 全 0 即可；其他缺省字段保持 null 以避免假阳性。
 */
function qsbz_match(array $overrides = []): mixed
{
    $base = [
        'rigan' => 1,
        'rizhi' => 1,
        // 8 个槽位；sike[4]=rizhi、sike[5]=上神由 overrides 决定；其余占位。
        'sike' => [1, 3, 3, 5, 1, 3, 5, 7],
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 0),
    ];

    $pan = array_replace($base, $overrides);

    return (new QuanSheBuZhengRule)->match(PanFacts::from(new PanResult($pan)));
}

test('第八法定义只有日禄临支一条成立路线且包含三项减损判断', function () {
    $definition = (new QuanSheBuZhengRule)->definition();

    expect(array_column($definition['foundations'], 'code'))->toBe(['lu_on_branch'])
        ->and(array_column($definition['foundations'], 'title'))->toBe(['日禄临支'])
        ->and(array_column($definition['judgments'], 'label'))->toBe([
            '禄受墓', '禄受支克', '禄受支脱',
        ])
        ->and(array_column($definition['judgments'], 'effect'))->toBe([
            'reduce', 'reduce', 'reduce',
        ]);
});

test('十个天干各自日禄临支全部成立（含甲丙戊庚壬五个阳干）', function (int $stem, int $lu, int $branch) {
    // sike[5] = 日禄；日支取与日禄不同且不构成墓/克/脱的支以避免多余判断混入。
    $match = qsbz_match([
        'rigan' => $stem, 'rizhi' => $branch,
        'sike' => [$stem, 0, 0, 0, $branch, $lu, $lu, $lu],
    ]);

    expect($match)->not->toBeNull("stem={$stem} lu={$lu} 必须成立")
        ->and($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe($lu)
        ->and($match->evidence['branch_upper'])->toBe($lu);
})->with([
    '甲寅' => [0, 2, 9],   // 甲 日支亥：水不墓木/不克木/木不生水
    '乙卯' => [1, 3, 10],  // 乙 日支戌：火不墓木/不克木/木不生火
    '丙巳' => [2, 5, 0],   // 丙 日支子：水不墓火/不克火/火不生水
    '丁午' => [3, 6, 0],   // 丁 日支子：水不墓火/不克火/火不生水
    '戊巳' => [4, 5, 0],   // 戊 日支子：水不墓火/不克火/火不生水
    '己午' => [5, 6, 0],   // 己 日支子：水不墓火/不克火/火不生水
    '庚申' => [6, 8, 3],   // 庚 日支卯：木不墓金/不克金/金不生木
    '辛酉' => [7, 9, 0],   // 辛 日支子：水不墓金/水不克金/金不生水
    '壬亥' => [8, 11, 8],  // 壬 日支申：金不墓水/不克水/水不生金
    '癸子' => [9, 0, 8],   // 癸 日支申：金不墓水/不克水/水不生金
]);

test('支上神不是日禄时不成立', function () {
    expect(qsbz_match([
        'rigan' => 0, 'rizhi' => 9, // 甲日，支上 5 = 巳 ≠ 甲禄寅
        'sike' => [0, 0, 0, 0, 9, 5, 5, 5],
    ]))->toBeNull();
});

test('干上见禄但支上不见禄时不成立', function () {
    // 甲日：干上 2 (甲禄寅) 出现，但支上 = 1 不是 2，第八法只看支上。
    expect(qsbz_match([
        'rigan' => 0, 'rizhi' => 9,
        'sike' => [0, 2, 2, 2, 9, 1, 1, 1],
    ]))->toBeNull();
});

test('三传见禄但支上不见禄时不成立', function () {
    // sanchuan 出现甲禄寅 (2)，但支上 = 1 ≠ 2，第八法只看支上。
    expect(qsbz_match([
        'rigan' => 0, 'rizhi' => 9,
        'sike' => [0, 0, 0, 0, 9, 1, 1, 1],
        'sanchuan0' => 2, 'sanchuan1' => 2, 'sanchuan2' => 2,
    ]))->toBeNull();
});

test('辛丑结构（辛禄酉/丑金墓）成立并单独追加禄受墓', function () {
    $match = qsbz_match([
        'rigan' => 7, 'rizhi' => 1, // 辛丑
        'sike' => [7, 0, 0, 0, 1, 9, 9, 9], // 支上 = 酉 = 辛禄
    ]);

    $labels = array_column($match->matchedJudgments, 'label');
    $effects = array_column($match->matchedJudgments, 'effect', 'label');

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe(9) // 辛禄酉
        ->and($match->evidence['branch_upper'])->toBe(9)
        ->and($match->evidence['lu_element'])->toBe(3) // 酉金
        ->and($match->evidence['branch_element'])->toBe(2) // 丑土
        ->and($match->evidence['lu_grave'])->toBe(1) // 金墓丑
        ->and($labels)->toBe(['禄受墓'])
        ->and($effects['禄受墓'])->toBe('reduce');
});

test('乙酉结构（乙禄卯/酉金克卯木）成立并单独追加禄受支克', function () {
    $match = qsbz_match([
        'rigan' => 1, 'rizhi' => 9, // 乙酉
        'sike' => [1, 0, 0, 0, 9, 3, 3, 3], // 支上 = 卯 = 乙禄
    ]);

    $labels = array_column($match->matchedJudgments, 'label');

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe(3) // 乙禄卯
        ->and($match->evidence['lu_element'])->toBe(0) // 卯木
        ->and($match->evidence['branch_element'])->toBe(3) // 酉金
        ->and($match->evidence['lu_grave'])->toBe(7) // 木墓未
        ->and($labels)->toBe(['禄受支克']);
});

test('乙巳结构（乙禄卯/卯木生巳火）成立并单独追加禄受支脱', function () {
    $match = qsbz_match([
        'rigan' => 1, 'rizhi' => 5, // 乙巳
        'sike' => [1, 0, 0, 0, 5, 3, 3, 3], // 支上 = 卯 = 乙禄
    ]);

    $labels = array_column($match->matchedJudgments, 'label');

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe(3) // 乙禄卯
        ->and($match->evidence['lu_element'])->toBe(0) // 卯木
        ->and($match->evidence['branch_element'])->toBe(1) // 巳火
        ->and($match->evidence['lu_grave'])->toBe(7) // 木墓未
        ->and($labels)->toBe(['禄受支脱']);
});

test('壬辰结构（壬禄亥/辰为水墓+土克水）同时追加禄受墓与禄受支克', function () {
    $match = qsbz_match([
        'rigan' => 8, 'rizhi' => 4, // 壬辰
        'sike' => [8, 0, 0, 0, 4, 11, 11, 11], // 支上 = 亥 = 壬禄
    ]);

    $labels = array_column($match->matchedJudgments, 'label');

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe(11) // 壬禄亥
        ->and($match->evidence['lu_element'])->toBe(4) // 亥水
        ->and($match->evidence['branch_element'])->toBe(2) // 辰土
        ->and($match->evidence['lu_grave'])->toBe(4) // 水土墓辰
        ->and($labels)->toContain('禄受墓', '禄受支克')
        ->and(count($labels))->toBe(2);
});

test('丙戌结构（丙禄巳/戌为火墓+火生土）同时追加禄受墓与禄受支脱', function () {
    $match = qsbz_match([
        'rigan' => 2, 'rizhi' => 10, // 丙戌
        'sike' => [2, 0, 0, 0, 10, 5, 5, 5], // 支上 = 巳 = 丙禄
    ]);

    $labels = array_column($match->matchedJudgments, 'label');

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->evidence['day_lu'])->toBe(5) // 丙禄巳
        ->and($match->evidence['lu_element'])->toBe(1) // 巳火
        ->and($match->evidence['branch_element'])->toBe(2) // 戌土
        ->and($match->evidence['lu_grave'])->toBe(10) // 火墓戌
        ->and($labels)->toContain('禄受墓', '禄受支脱')
        ->and(count($labels))->toBe(2);
});

test('普通日禄临支（甲辰/甲禄寅临辰）不输出任何减损判断', function () {
    $match = qsbz_match([
        'rigan' => 0, 'rizhi' => 4, // 甲辰
        'sike' => [0, 0, 0, 0, 4, 2, 2, 2], // 支上 = 寅 = 甲禄
    ]);

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->matchedJudgments)->toBe([])
        ->and($match->evidence['day_lu'])->toBe(2)
        ->and($match->evidence['lu_element'])->toBe(0) // 寅木
        ->and($match->evidence['branch_element'])->toBe(2) // 辰土
        ->and($match->evidence['lu_tombed_by_branch'])->toBeFalse()
        ->and($match->evidence['lu_controlled_by_branch'])->toBeFalse()
        ->and($match->evidence['lu_drained_by_branch'])->toBeFalse();
});

test('三项判定独立：墓+克与墓+脱可重叠，但克+脱组合理论上不同时成立', function () {
    // 丙戌 = 墓+脱；壬辰 = 墓+克。
    // 另构造一个木禄临金支（受克）的甲申/甲寅临申：木克土不是受克。
    // 直接枚举已知三组合即可证明独立性。
    $matches = [
        '甲辰' => qsbz_match(['rigan' => 0, 'rizhi' => 4, 'sike' => [0, 0, 0, 0, 4, 2, 2, 2]]),
        '辛丑' => qsbz_match(['rigan' => 7, 'rizhi' => 1, 'sike' => [7, 0, 0, 0, 1, 9, 9, 9]]),
        '乙酉' => qsbz_match(['rigan' => 1, 'rizhi' => 9, 'sike' => [1, 0, 0, 0, 9, 3, 3, 3]]),
        '乙巳' => qsbz_match(['rigan' => 1, 'rizhi' => 5, 'sike' => [1, 0, 0, 0, 5, 3, 3, 3]]),
        '壬辰' => qsbz_match(['rigan' => 8, 'rizhi' => 4, 'sike' => [8, 0, 0, 0, 4, 11, 11, 11]]),
        '丙戌' => qsbz_match(['rigan' => 2, 'rizhi' => 10, 'sike' => [2, 0, 0, 0, 10, 5, 5, 5]]),
    ];

    $flags = [];
    foreach ($matches as $name => $m) {
        $flags[$name] = [
            't' => $m->evidence['lu_tombed_by_branch'],
            'c' => $m->evidence['lu_controlled_by_branch'],
            'd' => $m->evidence['lu_drained_by_branch'],
        ];
    }

    expect($flags['甲辰'])->toBe(['t' => false, 'c' => false, 'd' => false])
        ->and($flags['辛丑'])->toBe(['t' => true, 'c' => false, 'd' => false])
        ->and($flags['乙酉'])->toBe(['t' => false, 'c' => true, 'd' => false])
        ->and($flags['乙巳'])->toBe(['t' => false, 'c' => false, 'd' => true])
        ->and($flags['壬辰'])->toBe(['t' => true, 'c' => true, 'd' => false])
        ->and($flags['丙戌'])->toBe(['t' => true, 'c' => false, 'd' => true]);
});

test('日干五行虽被支克但日禄五行不被支克时不误报禄受支克', function () {
    // 甲日（甲木）/日支卯（木）/支上卯=甲禄卯：
    //  - 日干五行木 = 日支五行木：比和，不是支克；
    //  - 日禄五行木 卯 vs 日支五行木 卯：相等，不是支克。
    $match = qsbz_match([
        'rigan' => 0, 'rizhi' => 3, // 甲子日寄宫在寅，与日支卯不相干；这里只看 sike[5]
        'sike' => [0, 0, 0, 0, 3, 2, 2, 2], // 支上 = 寅 = 甲禄
    ]);

    expect($match->matchedRoutes)->toBe(['lu_on_branch'])
        ->and($match->matchedJudgments)->toBe([])
        ->and($match->evidence['lu_controlled_by_branch'])->toBeFalse();
});

test('输入防御：非法输入安全返回 null', function (array $overrides) {
    expect(qsbz_match($overrides))->toBeNull();
})->with([
    '日干缺失' => [['rigan' => null]],
    '日干越界' => [['rigan' => 10]],
    '日干负值' => [['rigan' => -1]],
    '日干非整数' => [['rigan' => '甲']],
    'sike 非数组' => [['sike' => null]],
    'sike 为空数组' => [['sike' => []]],
    'sike 缺少 index 5' => [['sike' => [0, 0, 0, 0, 0]]],
    'sike[5] 非整数' => [['sike' => [0, 0, 0, 0, 0, '寅', 0, 0]]],
    'sike[5] 越界' => [['sike' => [0, 0, 0, 0, 0, 12, 0, 0]]],
    'sike[5] 负值' => [['sike' => [0, 0, 0, 0, 0, -1, 0, 0]]],
    '日支越界' => [['rizhi' => 12]],
    '日支负值' => [['rizhi' => -1]],
]);
