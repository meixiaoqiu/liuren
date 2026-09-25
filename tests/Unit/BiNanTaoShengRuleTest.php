<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\BiNanTaoShengRule;
use App\Domain\Pan\Facts\PanFacts;

function bnts_match(array $overrides = []): mixed
{
    $base = [
        'rigan' => 0, 'rizhi' => 0,
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 0),
        'sanchuan0' => 10, 'sanchuan1' => 8, 'sanchuan2' => 6,
        'yuejiang' => 0,
        'context' => ['people' => []],
    ];

    return (new BiNanTaoShengRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

function bnts_plate(array $heavenOnGround): array
{
    $plate = range(0, 11);
    foreach ($heavenOnGround as $ground => $heaven) {
        $oldGround = array_search($heaven, $plate, true);
        [$plate[$ground], $plate[$oldGround]] = [$plate[$oldGround], $plate[$ground]];
    }

    return $plate;
}

test('第九法定义固定九条中文路径', function () {
    $definition = (new BiNanTaoShengRule)->definition();

    expect(array_column($definition['foundations'], 'code'))->toBe([
        'escape_to_stem_support', 'escape_to_branch_support', 'escape_to_ground_support',
        'fate_ding_on_growth', 'escape_to_wealth', 'escape_failed',
        'abandon_benefit_for_loss', 'neither_stay_nor_leave', 'grave_as_sun',
    ])->and(array_column($definition['foundations'], 'title'))->toBe([
        '就干上之生', '就支上之生', '日干坐地盘之生', '本命乘丁坐长生',
        '日干下临财乡', '避难逃生而终不能逃生', '舍益就损', '舍就皆不可', '墓作太阳',
    ]);
});

test('甲子戌申午三传明确无益而就干上子生', function () {
    $plate = bnts_plate([2 => 0]);
    $match = bnts_match(['tianpan' => $plate]);

    expect($match?->matchedRoutes)->toContain('escape_to_stem_support')
        ->and($match->evidence['transmission_adverse_reasons'])->toBe([['旬空'], ['日鬼'], ['脱气']]);
});

test('干上虽生日但三传含普通比和则不就干上之生', function () {
    $match = bnts_match(['tianpan' => bnts_plate([2 => 0]), 'sanchuan0' => 2]);
    expect($match?->matchedRoutes ?? [])->not->toContain('escape_to_stem_support');
});

test('甲子财受上下夹克且中末脱鬼时就支上之生', function () {
    $plate = bnts_plate([0 => 2, 2 => 4]);
    $generals = array_fill(0, 12, 0);
    $generals[2] = 3; // 辰土所坐寅木，又乘六合木。
    $match = bnts_match([
        'tianpan' => $plate, 'tianjiang' => $generals,
        'sanchuan0' => 4, 'sanchuan1' => 6, 'sanchuan2' => 8,
    ]);

    expect($match?->matchedRoutes)->toContain('escape_to_branch_support')
        ->and($match->evidence['transmission_adverse_reasons'][0])->toContain('财受上下夹克');
});

test('财爻必须地盘与天将同时克财才成夹克', function () {
    $plate = bnts_plate([2 => 7, 3 => 4]);
    $both = array_fill(0, 12, 0);
    $both[3] = 3;
    $groundOnly = $both;
    $groundOnly[3] = 0; // 贵人土，不克辰土。
    $generalOnlyPlate = bnts_plate([2 => 7]);
    $generalOnly = array_fill(0, 12, 0);
    $generalOnly[4] = 3;

    $common = ['yuejiang' => 7, 'sanchuan0' => 4, 'sanchuan1' => 2, 'sanchuan2' => 2];
    $m1 = bnts_match([...$common, 'tianpan' => $plate, 'tianjiang' => $both]);
    $m2 = bnts_match([...$common, 'tianpan' => $plate, 'tianjiang' => $groundOnly]);
    $m3 = bnts_match([...$common, 'tianpan' => $generalOnlyPlate, 'tianjiang' => $generalOnly]);

    expect($m1?->evidence['transmission_adverse_reasons'][0] ?? [])->toContain('财受上下夹克')
        ->and($m2?->evidence['transmission_adverse_reasons'][0] ?? [])->not->toContain('财受上下夹克')
        ->and($m3?->evidence['transmission_adverse_reasons'][0] ?? [])->not->toContain('财受上下夹克');
});

test('庚子三传水脱且干寄宫申坐辰土时就地盘之生', function () {
    $match = bnts_match([
        'rigan' => 6, 'rizhi' => 0, 'tianpan' => bnts_plate([4 => 8]),
        'sanchuan0' => 0, 'sanchuan1' => 11, 'sanchuan2' => 0,
    ]);
    expect($match?->matchedRoutes)->toContain('escape_to_ground_support');
});

test('地盘虽生日但三传不是三位明确无益则不成立', function () {
    $match = bnts_match([
        'rigan' => 6, 'rizhi' => 0, 'tianpan' => bnts_plate([4 => 8]),
        'sanchuan0' => 8, 'sanchuan1' => 11, 'sanchuan2' => 0,
    ]);
    expect($match?->matchedRoutes ?? [])->not->toContain('escape_to_ground_support');
});

test('壬午辰酉寅三传无益且壬干下临午财乡', function () {
    $match = bnts_match([
        'rigan' => 8, 'rizhi' => 6, 'tianpan' => bnts_plate([6 => 11]),
        'sanchuan0' => 4, 'sanchuan1' => 9, 'sanchuan2' => 2,
    ]);
    expect($match?->matchedRoutes)->toContain('escape_to_wealth');
});

test('日干坐财乡但三传有可用位置不得逃生得财', function () {
    $match = bnts_match([
        'rigan' => 8, 'rizhi' => 6, 'tianpan' => bnts_plate([6 => 11]),
        'sanchuan0' => 0, 'sanchuan1' => 9, 'sanchuan2' => 2,
    ]);
    expect($match?->matchedRoutes ?? [])->not->toContain('escape_to_wealth');
});

test('丁亥墓空禄复墓长生乘白虎为终不能逃生', function () {
    $generals = array_fill(0, 12, 0);
    $generals[2] = 7;
    $match = bnts_match([
        'rigan' => 3, 'rizhi' => 11, 'tianpan' => bnts_plate([7 => 10]), 'tianjiang' => $generals,
        'sanchuan0' => 6, 'sanchuan1' => 10, 'sanchuan2' => 2,
    ]);
    expect($match?->matchedRoutes)->toContain('escape_failed')
        ->and(array_column($match->matchedJudgments, 'label'))->toContain('逃生受阻');
});

test('壬寅结构舍干上长生而就支受脱', function () {
    $match = bnts_match(['rigan' => 8, 'rizhi' => 2, 'tianpan' => bnts_plate([11 => 8, 2 => 11])]);
    expect($match?->matchedRoutes)->toContain('abandon_benefit_for_loss');
});

test('乙酉与辛丑按校勘归舍益就损而不得归舍就皆不可', function (int $stem, int $branch, int $upper, int $lodging) {
    $match = bnts_match(['rigan' => $stem, 'rizhi' => $branch, 'tianpan' => bnts_plate([$lodging => $upper, $branch => $lodging])]);
    expect($match?->matchedRoutes)->toContain('abandon_benefit_for_loss')
        ->and($match?->matchedRoutes)->not->toContain('neither_stay_nor_leave');
})->with([
    '乙酉干上亥' => [1, 9, 11, 4],
    '辛丑干上未' => [7, 1, 7, 10],
]);

test('庚子庚午结构生处空而就支又受脱或鬼', function (int $branch) {
    $upper = $branch === 0 ? 4 : 10;
    $match = bnts_match(['rigan' => 6, 'rizhi' => $branch, 'tianpan' => bnts_plate([8 => $upper, $branch => 8])]);
    expect($match?->matchedRoutes)->toContain('neither_stay_nor_leave');
})->with(['庚子' => [0], '庚午' => [6]]);

test('墓覆干且正为月将才是墓作太阳', function () {
    $plate = bnts_plate([2 => 7]); // 甲日墓未覆干。
    $yes = bnts_match(['tianpan' => $plate, 'yuejiang' => 7]);
    $notMonthGeneral = bnts_match(['tianpan' => $plate, 'yuejiang' => 6]);
    $notGrave = bnts_match(['tianpan' => bnts_plate([2 => 0]), 'yuejiang' => 0]);

    expect($yes?->matchedRoutes)->toContain('grave_as_sun')
        ->and(array_column($yes->matchedJudgments, 'label'))->toContain('难中有援')
        ->and($notMonthGeneral?->matchedRoutes ?? [])->not->toContain('grave_as_sun')
        ->and($notGrave?->matchedRoutes ?? [])->not->toContain('grave_as_sun');
});

test('本命丁神路径只在丁神确坐长生时才待评估人物', function () {
    // 甲子旬丁神卯（木），长生亥。
    $notOnGrowth = bnts_match(['tianpan' => range(0, 11), 'sanchuan0' => 2, 'sanchuan1' => 2, 'sanchuan2' => 2]);
    $onGrowthPlate = bnts_plate([11 => 3]);
    $pending = bnts_match(['tianpan' => $onGrowthPlate, 'sanchuan0' => 2, 'sanchuan1' => 2, 'sanchuan2' => 2]);
    $wrongFate = bnts_match([
        'tianpan' => $onGrowthPlate, 'sanchuan0' => 2, 'sanchuan1' => 2, 'sanchuan2' => 2,
        'context' => ['people' => [['role' => 'querent', 'nianming' => 4]]],
    ]);
    $rightFate = bnts_match([
        'tianpan' => $onGrowthPlate, 'sanchuan0' => 2, 'sanchuan1' => 2, 'sanchuan2' => 2,
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3]]],
    ]);

    expect($notOnGrowth?->pendingRoutes ?? [])->not->toContain('fate_ding_on_growth')
        ->and($pending?->pendingRoutes)->toBe(['fate_ding_on_growth'])
        ->and($wrongFate?->matchedRoutes ?? [])->not->toContain('fate_ding_on_growth')
        ->and($rightFate?->matchedRoutes)->toContain('fate_ding_on_growth');
});

test('保守三传无益只接受空鬼脱墓与财受夹克', function () {
    $ordinary = bnts_match([
        'tianpan' => bnts_plate([2 => 7]), 'yuejiang' => 7,
        'sanchuan0' => 2, // 比和
        'sanchuan1' => 0, // 普通不空父母
        'sanchuan2' => 4, // 普通财，无上下夹克
    ]);

    expect($ordinary?->evidence['transmission_adverse_reasons'] ?? null)->toBe([[], [], []])
        ->and($ordinary?->matchedRoutes ?? [])->not->toContain('escape_to_stem_support');
});
