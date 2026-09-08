<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\DeYunRule;
use App\Domain\Pan\Rules\FanchangRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\WangYunRule;

/**
 * 构造繁昌课基础盘面：夫行年寅、妻行年午（旺孕格），寅月（孟春，木旺火相）。
 * 默认不在德孕格内：丙己天干不合、寅午地支不合。
 *
 * @param  array<string, mixed>  $husband
 * @param  array<string, mixed>  $wife
 */
function fc_people(array $husband = [], array $wife = []): array
{
    return [
        ['role' => 'querent', 'gender' => 'male', 'nianming' => 0, 'xingnian' => 2, 'xingnian_gan' => 0, ...$husband],
        ['role' => 'spouse', 'gender' => 'female', 'nianming' => 8, 'xingnian' => 6, 'xingnian_gan' => 5, ...$wife],
    ];
}

/**
 * @param  array<string, mixed>  $husband
 * @param  array<string, mixed>  $wife
 * @param  array<string, mixed>  $overrides
 */
function fc_pan(array $husband = [], array $wife = [], array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'context' => ['people' => fc_people($husband, $wife)],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 2,
    ], $overrides));
}

/**
 * @param  class-string<FanchangRule|WangYunRule|DeYunRule>  $rule
 * @param  array<string, mixed>  $husband
 * @param  array<string, mixed>  $wife
 */
function fc_match(string $rule, array $husband = [], array $wife = []): ?RuleMatch
{
    return (new $rule)->match(PanFacts::from(fc_pan($husband, $wife)));
}

test('wang-yun grid matches the triple-combine wang-xiang couple in spring', function () {
    $match = fc_match(WangYunRule::class);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('旺孕格')
        ->and($match->group)->toBe('繁昌课体')
        ->and($match->marker)->toBe('格')
        ->and($match->evidence['detail'])->toContain('寅')
        ->and($match->evidence['detail'])->toContain('午')
        ->and($match->evidence['detail'])->toContain('三合');
});

test('wang-yun grid does not match when not triple-combined or not wang-xiang', function () {
    expect(fc_match(WangYunRule::class, [], ['xingnian' => 5]))->toBeNull()
        ->and(fc_match(WangYunRule::class, [], ['xingnian' => 10]))->toBeNull();
});

test('fanchang lesson aggregates the wang-yun grid', function () {
    $match = fc_match(FanchangRule::class);

    expect($match)->not->toBeNull()
        ->and($match->gua)->toBe('咸')
        ->and($match->guaSymbol)->toBe('䷞')
        ->and($match->marker)->toBe('经')
        ->and($match->evidence['foundations'])->toHaveCount(1)
        ->and($match->evidence['foundations'][0]['title'])->toBe('旺孕格')
        ->and($match->evidence['uncovered'])->toHaveCount(6);
});

test('fanchang does not match without a spouse person', function () {
    $facts = PanFacts::from(new PanResult([
        'context' => [
            'people' => [
                ['role' => 'querent', 'gender' => 'male', 'nianming' => 0, 'xingnian' => 2, 'xingnian_gan' => 0],
            ],
        ],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 2,
    ]));

    expect((new FanchangRule)->match($facts))->toBeNull();
});

test('fanchang declares spouse as required context', function () {
    $rule = new FanchangRule;

    expect($rule->requiredContext())->toBe(['people.querent', 'people.spouse'])
        ->and($rule->notEvaluatedInfo()['name'])->toBe('繁昌课')
        ->and($rule->notEvaluatedInfo()['notice'])->toBe('需要配偶出生信息，当前未进行判断。');
});

test('engine reports fanchang as not evaluated without a spouse', function () {
    $pan = new PanResult([
        'context' => [
            'people' => [
                ['role' => 'querent', 'gender' => 'male', 'nianming' => 0, 'xingnian' => 2, 'xingnian_gan' => 0],
            ],
        ],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 2,
    ]);

    $notEvaluated = (new PanRuleEngine)->notEvaluated($pan);

    expect($notEvaluated)->toHaveCount(1)
        ->and($notEvaluated[0]['code'])->toBe('lesson.fanchang')
        ->and($notEvaluated[0]['name'])->toBe('繁昌课')
        ->and($notEvaluated[0]['notice'])->toBe('需要配偶出生信息，当前未进行判断。');
});

test('wang-yun matches exactly the yin-noon couple in spring across all branches', function () {
    $hits = [];

    for ($fu = 0; $fu < 12; $fu++) {
        for ($qi = 0; $qi < 12; $qi++) {
            $facts = PanFacts::from(fc_pan(
                ['xingnian' => $fu],
                ['xingnian' => $qi],
            ));

            if ((new WangYunRule)->match($facts) !== null) {
                $hits[] = [$fu, $qi];
            }
        }
    }

    expect($hits)->toBe([[2, 6], [6, 2]]);
});

// ---------------------------------------------------------------------------
// 德孕格（DeYunRule）按《观月经》口径的测试
// ---------------------------------------------------------------------------

/**
 * 德孕格显式五合表（甲己、乙庚、丙辛、丁壬、戊癸）。
 * 测试必须使用此显式表构造/断言五合成立，不调用 DeYunRule 内部方法。
 *
 * @var list<array{0: int, 1: int}>
 */
const DE_YUN_STEM_COMBINES = [
    [0, 5], // 甲己
    [1, 6], // 乙庚
    [2, 7], // 丙辛
    [3, 8], // 丁壬
    [4, 9], // 戊癸
];

/**
 * 德孕格显式六合表（子丑、寅亥、卯戌、辰酉、巳申、午未）。
 *
 * @var list<array{0: int, 1: int}>
 */
const DE_YUN_BRANCH_COMBINES = [
    [0, 1],   // 子丑
    [2, 11],  // 寅亥
    [3, 10],  // 卯戌
    [4, 9],   // 辰酉
    [5, 8],   // 巳申
    [6, 7],   // 午未
];

/**
 * 给定占年与男女行年，构造一个让 DeYunRule 命中所需的最小 PanFacts。
 * 默认 yuezhi=8（秋），寅木在秋季失令，故旺孕格不会命中。
 */
function deyun_pan(int $hGan, int $hZhi, int $wGan, int $wZhi, array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'context' => [
            'people' => [
                ['role' => 'querent', 'gender' => 'male', 'nianming' => 0, 'xingnian' => $hZhi, 'xingnian_gan' => $hGan],
                ['role' => 'spouse', 'gender' => 'female', 'nianming' => 0, 'xingnian' => $wZhi, 'xingnian_gan' => $wGan],
            ],
        ],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 8,
    ], $overrides));
}

test('de-yun grid matches the jia-ji yin-hai couple from guanyue jing', function () {
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 5, 11)));

    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('structure.de_yun')
        ->and($match->name)->toBe('德孕格')
        ->and($match->group)->toBe('繁昌课体')
        ->and($match->marker)->toBe('格')
        ->and($match->evidence['detail'])->toContain('甲寅')
        ->and($match->evidence['detail'])->toContain('己亥')
        ->and($match->evidence['detail'])->toContain('甲己')
        ->and($match->evidence['detail'])->toContain('寅亥')
        ->and($match->evidence['detail'])->toContain('《观月经》')
        ->and($match->evidence['detail'])->toContain('不论三传');
});

test('de-yun grid matches all five stem-combines from the explicit table', function () {
    foreach (DE_YUN_STEM_COMBINES as [$hGan, $wGan]) {
        // 配 寅亥：男干 = hGan、女干 = wGan、行年 = 寅(2)/亥(11)，是五合标准配对。
        $match = (new DeYunRule)->match(PanFacts::from(deyun_pan($hGan, 2, $wGan, 11)));

        expect($match)->not->toBeNull("干五合 ({$hGan},{$wGan}) 应命中德孕格")
            ->and($match->name)->toBe('德孕格');
    }
});

test('de-yun grid matches all six branch-combines from the explicit table', function () {
    foreach (DE_YUN_BRANCH_COMBINES as [$hZhi, $wZhi]) {
        // 配 甲己：男支 = hZhi、女支 = wZhi，是六合标准配对。
        $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, $hZhi, 5, $wZhi)));

        expect($match)->not->toBeNull("地支六合 ({$hZhi},{$wZhi}) 应命中德孕格")
            ->and($match->name)->toBe('德孕格');
    }
});

test('de-yun grid does not match when stems are not in any stem-combine', function () {
    // 六合配对存在，但干不合：甲(0) + 庚(6)（非五合）。
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 6, 11)));

    expect($match)->toBeNull();
});

test('de-yun grid does not match when branches are not in any branch-combine', function () {
    // 五合配对存在，但支不合：甲(0) + 己(5)，寅(2) + 申(8)（非六合）。
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 5, 8)));

    expect($match)->toBeNull();
});

test('de-yun grid does not match with only stem-combine (no branch-combine)', function () {
    // 仅干合、支不合：甲(0) + 己(5)，寅(2) + 卯(3)。
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 5, 3)));

    expect($match)->toBeNull();
});

test('de-yun grid does not match with only branch-combine (no stem-combine)', function () {
    // 仅支合、干不合：甲(0) + 庚(6)，寅(2) + 亥(11)。
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 6, 11)));

    expect($match)->toBeNull();
});

test('de-yun grid does not match when spouse is missing', function () {
    $facts = PanFacts::from(new PanResult([
        'context' => [
            'people' => [
                ['role' => 'querent', 'gender' => 'male', 'xingnian' => 2, 'xingnian_gan' => 0],
            ],
        ],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 8,
    ]));

    expect((new DeYunRule)->match($facts))->toBeNull();
});

test('de-yun grid matches when xingnian is complete but nianming is missing', function () {
    // 行年完整（甲寅/己亥）但 nianming 缺失：德孕按《观月经》口径只读行年，仍应命中。
    $people = [
        ['role' => 'querent', 'gender' => 'male', 'xingnian' => 2, 'xingnian_gan' => 0],
        ['role' => 'spouse', 'gender' => 'female', 'xingnian' => 11, 'xingnian_gan' => 5],
    ];

    $pan = new PanResult([
        'context' => ['people' => $people],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 8,
    ]);

    $match = (new DeYunRule)->match(PanFacts::from($pan));

    expect($match)->not->toBeNull('行年完整但 nianming 缺失，德孕格仍应命中')
        ->and($match->name)->toBe('德孕格')
        ->and($match->evidence['detail'])->toContain('甲寅')
        ->and($match->evidence['detail'])->toContain('己亥');
});

test('wang-yun grid matches when xingnian is complete but nianming is missing', function () {
    // 旺孕格只读行年地支与季节旺相；nianming 缺失不影响判定。
    $people = [
        ['role' => 'querent', 'gender' => 'male', 'xingnian' => 2, 'xingnian_gan' => 0],
        ['role' => 'spouse', 'gender' => 'female', 'xingnian' => 6, 'xingnian_gan' => 5],
    ];

    $pan = new PanResult([
        'context' => ['people' => $people],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 2,
    ]);

    $match = (new WangYunRule)->match(PanFacts::from($pan));

    expect($match)->not->toBeNull('行年完整但 nianming 缺失，旺孕格仍应命中')
        ->and($match->name)->toBe('旺孕格');
});

test('de-yun grid does not match when the couple is same-gender', function () {
    $facts = PanFacts::from(new PanResult([
        'context' => [
            'people' => [
                ['role' => 'querent', 'gender' => 'male', 'xingnian' => 2, 'xingnian_gan' => 0],
                ['role' => 'spouse', 'gender' => 'male', 'xingnian' => 11, 'xingnian_gan' => 5],
            ],
        ],
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'yuezhi' => 8,
    ]));

    expect((new DeYunRule)->match($facts))->toBeNull();
});

test('de-yun grid does not read san-chuan', function () {
    $people = [
        ['role' => 'querent', 'gender' => 'male', 'nianming' => 0, 'xingnian' => 2, 'xingnian_gan' => 0],
        ['role' => 'spouse', 'gender' => 'female', 'nianming' => 0, 'xingnian' => 11, 'xingnian_gan' => 5],
    ];

    $variants = [
        // 无三传字段
        [],
        // 三传子丑寅
        ['sanchuan0' => 0, 'sanchuan1' => 1, 'sanchuan2' => 2],
        // 三传午辰寅（A 段壬申日未时巳将）
        ['sanchuan0' => 6, 'sanchuan1' => 4, 'sanchuan2' => 2],
        // 三传亥巳子
        ['sanchuan0' => 11, 'sanchuan1' => 5, 'sanchuan2' => 0],
        // 三传 + tianpan 全部重排
        ['sanchuan0' => 9, 'sanchuan1' => 7, 'sanchuan2' => 3, 'tianpan' => [11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0]],
    ];

    foreach ($variants as $variant) {
        $pan = new PanResult(array_replace([
            'context' => ['people' => $people],
            'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            'yuezhi' => 8,
        ], $variant));

        $match = (new DeYunRule)->match(PanFacts::from($pan));

        expect($match)->not->toBeNull('三传变体应不影响德孕格判定')
            ->and($match->name)->toBe('德孕格')
            ->and($match->evidence['detail'])->toContain('甲寅')
            ->and($match->evidence['detail'])->toContain('己亥');
    }
});

test('de-yun and wang-yun grids are independent and FanchangRule aggregates them', function () {
    // Pan A：只命中德孕（甲己 + 寅亥；yuezhi=8 秋令，寅木失令 → 旺孕不命中）
    $deYunPan = deyun_pan(0, 2, 5, 11);
    $deYunFacts = PanFacts::from($deYunPan);
    $deYunMatch = (new DeYunRule)->match($deYunFacts);
    $wangYunOnlyA = (new WangYunRule)->match($deYunFacts);
    $fanchangA = (new FanchangRule)->match($deYunFacts);

    expect($deYunMatch)->not->toBeNull()
        ->and($wangYunOnlyA)->toBeNull()
        ->and($fanchangA)->not->toBeNull()
        ->and($fanchangA->evidence['foundations'])->toHaveCount(1)
        ->and($fanchangA->evidence['foundations'][0]['title'])->toBe('德孕格');

    // Pan B：只命中旺孕（默认 fc_pan: 丙寅 + 己午，春令木旺火相 → 旺孕命中；
    //          丙己天干不合 + 寅午地支不合 → 德孕不命中）
    $wangYunPan = fc_pan();
    $wangYunFacts = PanFacts::from($wangYunPan);
    $deYunOnlyB = (new DeYunRule)->match($wangYunFacts);
    $wangYunMatch = (new WangYunRule)->match($wangYunFacts);
    $fanchangB = (new FanchangRule)->match($wangYunFacts);

    expect($deYunOnlyB)->toBeNull()
        ->and($wangYunMatch)->not->toBeNull()
        ->and($fanchangB)->not->toBeNull()
        ->and($fanchangB->evidence['foundations'])->toHaveCount(1)
        ->and($fanchangB->evidence['foundations'][0]['title'])->toBe('旺孕格');

    // FanchangRule 与 DeYunRule 互不重叠，详见 FanchangRule 不重复登记。
    $engine = new PanRuleEngine;

    // Pan A：FanchangRule、DeYunRule 命中；WangYunRule 不命中。
    $matchesA = $engine->evaluate($deYunPan);
    $fanchangA = collect($matchesA)->firstWhere('code', 'lesson.fanchang');
    $deYunA = collect($matchesA)->firstWhere('code', 'structure.de_yun');
    $wangYunA = collect($matchesA)->firstWhere('code', 'structure.wang_yun');

    expect($fanchangA)->not->toBeNull()
        ->and($deYunA)->not->toBeNull()
        ->and($wangYunA)->toBeNull()
        ->and($fanchangA->evidence['foundations'][0]['title'])->toBe('德孕格');

    // Pan B：FanchangRule、WangYunRule 命中；DeYunRule 不命中。
    $matchesB = $engine->evaluate($wangYunPan);
    $fanchangB = collect($matchesB)->firstWhere('code', 'lesson.fanchang');
    $deYunB = collect($matchesB)->firstWhere('code', 'structure.de_yun');
    $wangYunB = collect($matchesB)->firstWhere('code', 'structure.wang_yun');

    expect($fanchangB)->not->toBeNull()
        ->and($deYunB)->toBeNull()
        ->and($wangYunB)->not->toBeNull()
        ->and($fanchangB->evidence['foundations'][0]['title'])->toBe('旺孕格');
});

test('de-yun evidence contains exact stem-combine and branch-combine names', function () {
    $match = (new DeYunRule)->match(PanFacts::from(deyun_pan(0, 2, 5, 11)));

    expect($match->evidence['detail'])->toContain('甲己')
        ->and($match->evidence['detail'])->toContain('寅亥')
        ->and($match->evidence['detail'])->toContain('天干五合')
        ->and($match->evidence['detail'])->toContain('地支六合')
        ->and($match->evidence['detail'])->toContain('《观月经》')
        ->and($match->evidence['detail'])->toContain('不论三传');
});
