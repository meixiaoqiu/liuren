<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

function cgsz_match(array $overrides = []): mixed
{
    $base = ['rigan' => 0, 'rizhi' => 0, 'yuezhi' => 2, 'guirenPeriod' => 'day',
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'tianpan' => range(0, 11), 'tianjiang' => range(0, 11),
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3, 'xingnian' => 4]]]];

    return (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

/**
 * 构造一个让 Route 1（催官使者）成立的最小场景：甲日 lodging=2（寅寄宫），
 * 干上神 (tianpan[2]) 设为 8（申 = 甲日官星），白虎 (天将 7) 落在该位。
 *
 * 在该场景基础上再调整三传 / calculationTime / yuezhi 即可用于返本煞与返吟的确定性测试。
 *
 * @param  array<string, mixed>  $overrides
 */
function cgsz_match_route1(array $overrides = []): mixed
{
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;       // 干上神 = 申
    $generals[2] = 7;  // 白虎 = 申

    return cgsz_match(array_replace([
        'tianpan' => $pan,
        'tianjiang' => $generals,
        'context' => ['people' => []],
    ], $overrides));
}

test('第四法公开四条有序成立路线与三条断义', function () {
    $definition = (new CuiGuanShiZheRule)->definition();
    expect(array_column($definition['foundations'], 'code'))->toBe([
        'cui_guan_messenger', 'cui_guan_talisman', 'patron_parent_line', 'patron_noble_as_growth',
    ])->and(array_column($definition['judgments'], 'label'))->toBe(['催官使者空亡', '四时返本煞', '返吟附加']);
});

test('催官使者命中与人物缺失待评估', function () {
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;
    $generals[2] = 7;
    expect(cgsz_match(['tianpan' => $pan, 'tianjiang' => $generals])?->matchedRoutes)->toContain('cui_guan_messenger');
    $pan = range(0, 11);
    $pan[5] = 8;
    $generals = range(0, 11);
    $generals[5] = 7;
    expect(cgsz_match(['tianpan' => $pan, 'tianjiang' => $generals, 'context' => ['people' => []]])?->pendingRoutes)
        ->toContain('cui_guan_messenger');
});

test('催官符由日干推出官星五行并只在合局生官时待评估', function () {
    $pan = range(0, 11);
    $pan[5] = 6;
    $personPan = $pan;
    $personPan[4] = 6;
    expect(cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $personPan,
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 7])?->matchedRoutes)->toContain('cui_guan_talisman');
    $pending = cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $pan,
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 7, 'context' => ['people' => []]]);
    expect($pending?->pendingRoutes)->toContain('cui_guan_talisman');
    $impossible = cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $pan,
        'sanchuan0' => 0, 'sanchuan1' => 4, 'sanchuan2' => 8, 'context' => ['people' => []]]);
    expect($impossible?->pendingRoutes ?? [])->not->toContain('cui_guan_talisman');
});

test('父母爻只查日辰三传与行年六处并支持待评估', function () {
    $pan = range(0, 11);
    $pan[4] = 8;
    $matched = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $pan]);
    expect($matched?->matchedRoutes)->toContain('patron_parent_line')
        ->and($matched?->evidence['parent_hits'])->toContain('xingnian');
    $pan[4] = 4;
    $pan[3] = 8;
    $pan[8] = 0;
    $nianmingOnly = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $pan,
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3]]]]);
    expect($nianmingOnly?->matchedRoutes ?? [])->not->toContain('patron_parent_line')
        ->and($nianmingOnly?->pendingRoutes)->toContain('patron_parent_line');
    $knownFalsePan = range(0, 11);
    $knownFalsePan[8] = 0;
    $knownFalse = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $knownFalsePan,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 4]]]]);
    expect($knownFalse?->pendingRoutes ?? [])->not->toContain('patron_parent_line');
});

/**
 * 乙卯昼贵空的三组生产语义测试：
 *  - A. 唯一父母爻 = 子且子实际乘贵人 → Route 3 不成立，special detail 出现；
 *  - B. 同时还有亥父母爻 → Route 3 仍成立，parent_hits 只保留有效命中；
 *  - C. 非乙卯日普通父母爻旬空 → Route 3 正常成立，未被一刀切取消。
 *
 * 全部使用合法生产盘：不人为改写天将序号去模拟生产不可能的「子支不乘贵人」状态。
 */
test('乙卯昼贵空只排除实际乘贵人的子且保留普通子和亥（A：唯一命中被过滤）', function () {
    // A. 乙卯日 + 昼占 + 甲寅旬空(子丑) + 子实际乘贵人 + 唯一父母爻命中为子
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[4] = 0;       // 干上神 = 子（乙 lodging=4 辰寄宫）
    $generals[4] = 0;  // 贵人坐在子位 → 子为「父母爻且实际乘贵人」的特殊命中

    // xingnian=7 → tianpan[7]=7（未），非父母爻，确保 route3Pending=false，
    // 乙卯特例 detail 才能展示而非被 pending detail 覆盖。
    $match = cgsz_match([
        'rigan' => 1, 'rizhi' => 3, 'guirenPeriod' => 'day',
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'tianpan' => $pan, 'tianjiang' => $generals,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 7]]],
    ]);

    expect($match?->matchedRoutes ?? [])->not->toContain('patron_parent_line');
    $sub = collect($match?->subMatches ?? [])->firstWhere('code', 'patron_parent_line');
    expect($sub['detail'] ?? null)
        ->toContain('乙卯日')
        ->and($sub['detail'] ?? null)->toContain('不用')
        ->and($sub['detail'] ?? null)->toContain('Route 3 不成立');
    expect($match?->evidence['yi_mao_day_noble_voided'] ?? null)->toBeTrue();
});

test('乙卯昼贵空只排除实际乘贵人的子且保留普通子和亥（B：另有亥命中保留）', function () {
    // B. 乙卯日 + 子路径被过滤 + 支上神额外出现亥父母爻
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[4] = 0;       // 干上神 = 子 → 被过滤
    $pan[3] = 11;      // 支上神 = 亥 → 父母爻命中且保留
    $generals[4] = 0;  // 贵人坐在子位 → 子是「实际乘贵人」的命中

    $match = cgsz_match([
        'rigan' => 1, 'rizhi' => 3, 'guirenPeriod' => 'day',
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'tianpan' => $pan, 'tianjiang' => $generals,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 7]]],
    ]);

    expect($match?->matchedRoutes ?? [])->toContain('patron_parent_line')
        ->and($match?->evidence['parent_hits'] ?? [])->toContain('zhiShang')
        ->and($match?->evidence['parent_hits'] ?? [])->not->toContain('ganShang');
    $sub = collect($match?->subMatches ?? [])->firstWhere('code', 'patron_parent_line');
    expect($sub['detail'] ?? null)->toContain('支上神')
        ->and($sub['detail'] ?? null)->toContain('亥')
        ->and($sub['detail'] ?? null)->not->toContain('乙卯'); // 已成立时不展示特例
});

test('乙卯昼贵空只排除实际乘贵人的子且保留普通子和亥（C：普通日父母爻旬空）', function () {
    // C. 丙辰日（甲辰旬空 = 寅卯）+ 干上神 = 寅（父母爻且旬空）+ 非乙卯特例
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[5] = 2;       // 干上神 = 寅（丙 lodging=5 巳寄宫）

    $match = cgsz_match([
        'rigan' => 2, 'rizhi' => 4, 'guirenPeriod' => 'day',
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'tianpan' => $pan, 'tianjiang' => $generals,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 7]]],
    ]);

    expect($match?->matchedRoutes ?? [])->toContain('patron_parent_line')
        ->and($match?->evidence['parent_hits'] ?? [])->toContain('ganShang')
        ->and($match?->evidence['yi_mao_day_noble_voided'] ?? null)->toBeFalse();
});

test('己卯夜贵申空不用', function () {
    $match = cgsz_match(['rigan' => 5, 'rizhi' => 3, 'guirenPeriod' => 'night']);
    expect($match?->evidence['ji_mao_origin_voided'])->toBeTrue()
        ->and($match?->matchedRoutes ?? [])->not->toContain('patron_noble_as_growth');
});

test('催官使者空亡仍成立并产生结构化动态断义', function () {
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;
    $generals[2] = 7;
    $match = cgsz_match(['rigan' => 0, 'rizhi' => 10, 'tianpan' => $pan, 'tianjiang' => $generals]);
    expect($match?->matchedRoutes)->toContain('cui_guan_messenger')
        ->and($match?->evidence['messenger_is_void'])->toBeTrue()
        ->and(array_column($match?->matchedJudgments ?? [], 'label'))->toContain('催官使者空亡');
});

test('Route 3 pending 明确为行年缺失而非本命或行年', function () {
    // 戊午日（rigan=4, lodging=5 巳寄宫）+ 父母爻 = 巳午 + 行年缺失 + 前五处无命中
    $pan = [1, 2, 3, 4, 7, 0, 8, 9, 10, 11, 5, 6];
    $generals = range(0, 11);
    $match = cgsz_match([
        'rigan' => 4, 'rizhi' => 6, 'guirenPeriod' => 'day',
        'yuezhi' => 5,
        'sanchuan0' => 3, 'sanchuan1' => 4, 'sanchuan2' => 7, // 非三合
        'tianpan' => $pan, 'tianjiang' => $generals,
        'context' => ['people' => []],
    ]);

    expect($match?->pendingRoutes ?? [])->toContain('patron_parent_line');
    $sub = collect($match?->subMatches ?? [])->firstWhere('code', 'patron_parent_line');
    expect($sub['detail'] ?? null)
        ->toContain('行年')
        ->and($sub['detail'] ?? null)->not->toContain('本命或行年')
        ->and($sub['detail'] ?? null)->toContain('前五处');
});

/**
 * 返本煞四季映射确定性测试。
 *
 * 真实命中要求 seasonalPeriod() 返回对应 key 且 sanchuan 与 fanbenTripleForSeason()
 * 返回值严格相等。production 当前实现 fanbenTripleForSeason()：
 *  - spring → [3, 7, 11]（亥卯未木局）
 *  - summer → [0, 4, 8]（申子辰水局）
 *  - autumn → [2, 6, 10]（寅午戌火局）
 *  - winter → [2, 6, 10]（寅午戌火局，按《御定六壬直指》「土局与火同」处理）
 *
 * 本测试仅锁住当前实际行为；spring 与《六壬大全》正文「春得金局」及研究文档
 * 「春 = 巳酉丑」之差异属另一议题（不在本轮 matcher 修订范围），由本测试快照下来，
 * 待用户单独裁决后另起一轮修复。
 */
test('返本煞四季映射：每季对应三合局确实让 fanben_hit === true', function (
    string $time,
    string $expectedSeason,
    array $sanchuan
) {
    $match = cgsz_match_route1([
        'sanchuan0' => $sanchuan[0],
        'sanchuan1' => $sanchuan[1],
        'sanchuan2' => $sanchuan[2],
        'calculationTime' => $time,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['fanben_hit'] ?? null)->toBeTrue()
        ->and($match->evidence['season_key'] ?? null)->toBe($expectedSeason)
        ->and($match->evidence['fanben_triple'] ?? null)->toBe($sanchuan);
})->with([
    '春 → [3,7,11]' => ['2024-03-15T12:00:00', 'spring', [3, 7, 11]],
    '夏 → [0,4,8]' => ['2024-06-15T12:00:00', 'summer', [0, 4, 8]],
    '秋 → [2,6,10]' => ['2024-09-15T12:00:00', 'autumn', [2, 6, 10]],
    '冬 → [2,6,10]' => ['2024-12-15T12:00:00', 'winter', [2, 6, 10]],
]);

/**
 * 返本煞 soil 映射：四库月建 (辰/未/戌/丑) 分别映射回春/夏/秋/冬。
 *
 * 真实命中要求 seasonalPeriod() 返回 'soil' 且 yuezhi 命中对应季节；
 * fanbenTripleForSeason() 走 soil 分支后返回对应季的三合局。
 */
test('返本煞 soil 映射：四库月建确实让 fanben_hit === true', function (
    string $time,
    int $yuezhi,
    array $sanchuan,
    string $expectedSeason
) {
    $match = cgsz_match_route1([
        'yuezhi' => $yuezhi,
        'sanchuan0' => $sanchuan[0],
        'sanchuan1' => $sanchuan[1],
        'sanchuan2' => $sanchuan[2],
        'calculationTime' => $time,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['fanben_hit'] ?? null)->toBeTrue()
        ->and($match->evidence['season_key'] ?? null)->toBe('soil')
        ->and($match->evidence['fanben_triple'] ?? null)->toBe($sanchuan);
})->with([
    '辰月 soil → 春 → [3,7,11]' => ['2024-04-25T12:00:00', 4, [3, 7, 11], 'spring'],
    '未月 soil → 夏 → [0,4,8]' => ['2024-07-25T12:00:00', 7, [0, 4, 8], 'summer'],
    '戌月 soil → 秋 → [2,6,10]' => ['2024-10-25T12:00:00', 10, [2, 6, 10], 'autumn'],
    '丑月 soil → 冬 → [2,6,10]' => ['2024-01-25T12:00:00', 1, [2, 6, 10], 'winter'],
]);

/**
 * 只有返本煞命中但无任何正式 route、无 pending 时，第四法不成立。
 */
test('只有返本煞不得成立第四法', function () {
    // 壬日（rigan=8, lodging=11 亥寄宫, officials [4,10,1,7], parents [8,9]）
    // 三传 [3,7,11] = 亥卯未木局，与壬 parents 不相交 → 无 Route 3 命中
    // 干上神 = 子（tianpan[11]=0），不在壬 officials → 无 Route 1/Route 2
    // 给出 xingnian=4（辰）→ tianpan[4]=2（寅）不在壬 officials → 无 Route 1
    // season=spring → fanbenTriple=[3,7,11]，sanhe 命中 → fanben_hit=true
    // xingnian 已设 → route3Pending=false → pendingRoutes 空 → match 应返回 null
    $pan = [1, 2, 3, 2, 5, 6, 7, 0, 9, 10, 11, 0]; // tianpan[4]=2, tianpan[7]=0, tianpan[11]=0
    $generals = range(0, 11);

    $match = (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult([
        'rigan' => 8, 'rizhi' => 0, 'yuezhi' => 2,
        'guirenPeriod' => 'day',
        'sanchuan0' => 3, 'sanchuan1' => 7, 'sanchuan2' => 11,
        'tianpan' => $pan, 'tianjiang' => $generals,
        'calculationTime' => '2024-03-15T12:00:00', // 春
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 4]]],
    ])));

    expect($match)->toBeNull();
});

/**
 * 只有返吟附加命中但无任何正式 route、无 pending 时，第四法不成立。
 */
test('只有返吟不得成立第四法', function () {
    // 壬日 + 三传非三合（避免 fanben）+ fanyin 板式 + 同样避免所有正式 route
    $pan = [1, 2, 3, 2, 5, 6, 7, 0, 9, 10, 11, 0];
    $generals = range(0, 11);
    $match = (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult([
        'rigan' => 8, 'rizhi' => 0, 'yuezhi' => 2,
        'guirenPeriod' => 'day',
        'sanchuan0' => 1, 'sanchuan1' => 2, 'sanchuan2' => 4, // 非三合
        'tianpan' => $pan, 'tianjiang' => $generals,
        'calculationTrace' => ['plate_patterns' => ['fanyin']],
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 4]]],
    ])));

    expect($match)->toBeNull();
});

/**
 * 正式 route + 返本煞：必须既成立 route 又触发返本煞 judgment。
 */
test('正式 Route 1 命中且三传组成返本局 → matchedJudgments 含「四时返本煞」', function () {
    $match = cgsz_match_route1([
        'sanchuan0' => 3, 'sanchuan1' => 7, 'sanchuan2' => 11,
        'calculationTime' => '2024-03-15T12:00:00',
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('cui_guan_messenger')
        ->and($match->evidence['fanben_hit'] ?? null)->toBeTrue()
        ->and(array_column($match->matchedJudgments, 'label'))->toContain('四时返本煞');
});

/**
 * 正式 route + 返吟附加：必须既成立 route 又触发返吟 judgment。
 */
test('正式 Route 1 命中且盘面返吟 → matchedJudgments 含「返吟附加」', function () {
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;       // 干上神 = 申
    $generals[2] = 7;  // 白虎 = 申

    $match = (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'yuezhi' => 2,
        'guirenPeriod' => 'day',
        'sanchuan0' => 1, 'sanchuan1' => 2, 'sanchuan2' => 4, // 非三合
        'tianpan' => $pan, 'tianjiang' => $generals,
        'calculationTrace' => ['plate_patterns' => ['fanyin']],
        'context' => ['people' => []],
    ])));

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('cui_guan_messenger')
        ->and($match->evidence['fanyin'] ?? null)->toBeTrue()
        ->and(array_column($match->matchedJudgments, 'label'))->toContain('返吟附加');
});

test('固定生产案例锁定四条路线', function (string $datetime, string $route) {
    $data = (new PanCalculator)->calculate($datetime)->toArray();
    $data['context'] = ['people' => []];
    $match = (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult($data)));
    expect($match?->matchedRoutes)->toContain($route);
})->with([
    '2000-01-05 03:00:00' => ['2000-01-05 03:00:00', 'cui_guan_messenger'],
    '2000-01-20 17:00:00' => ['2000-01-20 17:00:00', 'cui_guan_talisman'],
    '2000-01-01 01:00:00' => ['2000-01-01 01:00:00', 'patron_parent_line'],
    '2000-01-01 23:00:00' => ['2000-01-01 23:00:00', 'patron_noble_as_growth'],
]);
