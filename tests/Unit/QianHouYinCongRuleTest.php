<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 构造毕法第一法基础盘面：庚辰日(6/4)，
 *  - tianpan = (i + 5) % 12（与 YinCongRuleTest 同源结构）；
 *  - 初传寅(2) 临地盘酉(9)；末传子(0) 临地盘未(7) → 拱天干寄宫申(8)；
 *  - 干上神 tianpan[8] = 1(丑) = 庚日昼贵 → 拱贵格同时命中；
 *  - 占者本命 0(子)、行年 1(丑)；
 *  - 非伏吟、{干支上神} ≠ {昼夜贵}、{初末} ≠ {昼夜贵}、{初中} 不夹贵人。
 *
 * @param  array<string, mixed>  $overrides
 */
function qhyc_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 2,
        'sanchuan1' => 5,
        'sanchuan2' => 0,
        'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
        'rigan' => 6,
        'rizhi' => 4,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1986-08-01T00:00',
                    'gender' => 'male',
                    'nianming' => 0,
                    'xingnian' => 1,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
        'calculationTrace' => ['plate_patterns' => []],
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function qhyc_match(array $overrides = []): ?BiFaRuleMatch
{
    return (new QianHouYinCongRule)->match(PanFacts::from(qhyc_pan($overrides)));
}

test('QianHouYinCongRule code equals bifa.01', function () {
    $rule = new QianHouYinCongRule;
    expect($rule->code())->toBe('bifa.01');
});

test('QianHouYinCongRule law() returns BiFaCatalog first law', function () {
    $law = (new QianHouYinCongRule)->law();
    expect($law['number'])->toBe(1)
        ->and($law['code'])->toBe('bifa.01')
        ->and($law['slug'])->toBe('qian-hou-yin-cong')
        ->and($law['name'])->toBe('前后引从升迁吉');
});

test('引从天干 分格：初前末后 → 命中', function () {
    $match = qhyc_match();

    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('bifa.01')
        ->and($match->number)->toBe(1)
        ->and($match->matchedRoutes)->toContain('yin_gan');

    $yinGan = collect($match->subMatches)->firstWhere('code', 'yin_gan');
    expect($yinGan['matched'])->toBeTrue()
        ->and($yinGan['detail'])->toContain('夹拱日干寄宫');
});

test('引从天干 分格：初后末前 → 不命中', function () {
    // 颠倒初末：初传子(0) 临地盘未(7)，末传寅(2) 临地盘酉(9)。
    // flanks(7, 9, 8) 仍成立，但方向性 yinGan 要求 initialGround === lodging+1。
    // 同时缺人资料，迫使 E/F 进入 pending_routes，match 整体不返回 null。
    $match = qhyc_match([
        'sanchuan0' => 0,
        'sanchuan2' => 2,
        'context' => ['people' => []],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('yin_gan');

    $yinGan = collect($match->subMatches)->firstWhere('code', 'yin_gan');
    expect($yinGan['matched'])->toBeFalse();
});

test('初末引从地支 分格：初前末后 → 命中', function () {
    // 甲午日(0/6)，天盘 offset=11：tianpan[1] = 0(子=午前一位)、tianpan[11] = 10(戌=午后一位)。
    // 初传子(0) 临地盘 7(未)、末传戌(10) 临地盘 5(巳) → 夹拱日支午(6)。
    $match = qhyc_match([
        'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
        'rigan' => 0,
        'rizhi' => 6,
        'sanchuan0' => 0,
        'sanchuan2' => 10,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_zhi');
});

test('初末引从地支 分格：初后末前 → 不命中', function () {
    // 缺人资料，迫使 E/F 进入 pending_routes，match 整体不返回 null。
    $match = qhyc_match([
        'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
        'rigan' => 0,
        'rizhi' => 6,
        'sanchuan0' => 10,
        'sanchuan2' => 0,
        'context' => ['people' => []],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('yin_zhi');
});

test('拱贵格 分格：引从天干 + 干上神是昼夜贵人之一（庚辰日 干上丑）', function () {
    $match = qhyc_match();

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('gong_gui');

    $gongGui = collect($match->subMatches)->firstWhere('code', 'gong_gui');
    expect($gongGui['matched'])->toBeTrue()
        ->and($gongGui['detail'])->toContain('昼贵');
});

test('两贵引从天干格 分格：贵人身份可互换，初末方向不可互换', function () {
    // 壬子日(8/0)，壬日昼贵巳(5)、夜贵卯(3)。
    // tianpan offset=5：tianpan[0] = 5(巳)、tianpan[10] = 3(卯)。
    // sanchuan0 = 5(巳) → initialGround = 0(子)；sanchuan2 = 3(卯) → finalGround = 10(亥)。
    // flanks(0, 10, 11) 成立；{5, 3} == {昼贵, 夜贵} → 两贵引从天干命中。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 5,
        'sanchuan2' => 3,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('liang_gui_yin_gan');

    $two = collect($match->subMatches)->firstWhere('code', 'liang_gui_yin_gan');
    expect($two['matched'])->toBeTrue()
        ->and($two['detail'])->toContain('昼贵')
        ->and($two['detail'])->toContain('夜贵');
});

test('两贵引从天干格 分格：贵人身份互换（夜贵在前、昼贵在后）', function () {
    // 壬子日(8/0)，壬日昼贵巳(5)、夜贵卯(3)。
    // 直接构造 tianpan：让 sanchuan0=3(卯=夜贵) 临 lodgingFront=0(子)，
    // sanchuan2=5(巳=昼贵) 临 lodgingBack=10(亥)；
    // 这样初前末后保持（满足 yinGan 方向性），但贵人身份按初=夜贵、末=昼贵。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 3,
        'sanchuan2' => 5,
        'sanchuan1' => 0,
        'tianpan' => [3, 8, 2, 4, 6, 7, 9, 10, 11, 1, 5, 0],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('liang_gui_yin_gan');
});

test('两贵引从天干格 分格：初后末前即使为昼夜贵也不命中', function () {
    // 壬子日，故意颠倒：初传夜贵卯临地盘 10(亥)、末传昼贵巳临地盘 0(子)。
    // yinGan 失败 → liang_gui_yin_gan 也不应命中（即使 {3, 5} == {昼贵, 夜贵}）。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 3,
        'sanchuan2' => 5,
    ]);

    // 上面那个 case 的方向实际是初=卯(前=亥)、末=巳(后=子)，flanks(10, 0, 11) 成立；
    // 现在故意把 sanchuan0 改为 5、sanchuan2 改为 3 让方向颠倒。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 5,
        'sanchuan2' => 3,
        // 配合颠倒：让 tianpan 让初传临 lodging 后一宫、末传临 lodging 前一宫。
        // 壬寄亥(11)，前一宫 = 0(子)，后一宫 = 10(亥)。
        // 要让 sanchuan0=5 临地盘 10、sanchuan2=3 临地盘 0 → tianpan[10] = 5, tianpan[0] = 3。
        // 那 tianpan[i] = (i + offset) % 12，offset=7：tianpan[0]=7, tianpan[10]=5。但我们想要 tianpan[0]=3, tianpan[10]=5。
        // 直接构造：[3, ?, ?, ?, ?, ?, ?, ?, ?, ?, 5, ?] — 改成不用 offset 的写法。
        'tianpan' => [3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 5, 2],
    ]);

    expect($match)->not->toBeNull();
    // yin_gan 不成立；liang_gui_yin_gan 也不应命中。
    expect($match->matchedRoutes)->not->toContain('yin_gan');
    expect($match->matchedRoutes)->not->toContain('liang_gui_yin_gan');
});

test('贵临干支拱本命 分格：昼夜二贵分别加临干支 + 干支夹拱本命', function () {
    // 丁酉日(3/9)：丁日昼贵亥(11)、夜贵酉(9)。
    // tianpan offset=2：tianpan[7] = 9(酉=夜贵)、tianpan[9] = 11(亥=昼贵)。
    // 干寄宫未(7) 上乘夜贵酉(9)、日支酉(9) 上乘昼贵亥(11)。
    // 本命申(8)：flanks(7, 9, 8) = (front=9, back=7) → (7==7 && 9==9) ✓ 夹拱本命。
    $match = qhyc_match([
        'tianpan' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1],
        'rigan' => 3,
        'rizhi' => 9,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1980-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => 8,
                    'xingnian' => 6,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');

    $e = collect($match->subMatches)->firstWhere('code', 'gui_lin_gan_zhi_gang_nianming');
    expect($e['matched'])->toBeTrue()
        ->and($e['detail'])->toContain('本命')
        ->and($e['detail'])->not->toContain('行年'); // 行年不在夹拱带，仅本命显示。
});

test('贵临干支拱行年 分格：昼夜二贵分别加临干支 + 干支夹拱行年', function () {
    // 丁巳日(3/5)：丁日昼贵亥(11)、夜贵酉(9)。
    // tianpan offset=4：tianpan[7] = 11(亥=昼贵)、tianpan[5] = 9(酉=夜贵)。
    // 干寄宫未(7) 上乘昼贵亥(11)、日支巳(5) 上乘夜贵酉(9)。
    // 行年午(6)：flanks(7, 5, 6) = (front=7, back=5) → (7==7 && 5==5) ✓ 夹拱行年。
    $match = qhyc_match([
        'tianpan' => [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3],
        'rigan' => 3,
        'rizhi' => 5,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1990-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => 2,  // 本命寅不在夹拱带。
                    'xingnian' => 6,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');

    $e = collect($match->subMatches)->firstWhere('code', 'gui_lin_gan_zhi_gang_nianming');
    expect($e['matched'])->toBeTrue()
        ->and($e['detail'])->toContain('行年')
        ->and($e['detail'])->not->toContain('本命'); // 本命不在夹拱带，仅行年显示。
});

test('贵临干支拱年命 分格：仅本命存在可行年缺失也可判定', function () {
    $match = qhyc_match([
        'tianpan' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1],
        'rigan' => 3,
        'rizhi' => 9,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1980-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => 8,
                    'xingnian' => null,
                    'xingnian_gan' => null,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');
});

test('贵临干支拱年命 分格：仅行年存在可本命缺失也可判定', function () {
    $match = qhyc_match([
        'tianpan' => [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3],
        'rigan' => 3,
        'rizhi' => 5,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1990-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => null,
                    'xingnian' => 6,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');
});

test('贵临干支拱年命 分格：两者皆缺才 people_missing', function () {
    $match = qhyc_match([
        'tianpan' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1],
        'rigan' => 3,
        'rizhi' => 9,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1980-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => null,
                    'xingnian' => null,
                    'xingnian_gan' => null,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull();

    $e = collect($match->subMatches)->firstWhere('code', 'gui_lin_gan_zhi_gang_nianming');
    expect($e['matched'])->toBeFalse()
        ->and($e['people_missing'])->toBeTrue();

    // 此时 E 既不命中、但因 people_missing 进入 pending_routes。
    expect($match->pendingRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');
});

test('二贵拱本命 分格：初末二贵 + 初末夹拱本命', function () {
    // 壬子日(8/0)，offset=5，sanchuan0=5(巳=昼贵)、sanchuan2=3(卯=夜贵)。
    // initialGround=0, finalGround=10。
    // 本命亥(11)：flanks(0, 10, 11) = (front=0, back=10) → (0==0 && 10==10) ✓。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 5,
        'sanchuan2' => 3,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1986-08-01T00:00',
                    'gender' => 'male',
                    'nianming' => 11,
                    'xingnian' => 6,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('er_gui_gang_nianming');

    $f = collect($match->subMatches)->firstWhere('code', 'er_gui_gang_nianming');
    expect($f['matched'])->toBeTrue()
        ->and($f['detail'])->toContain('本命')
        ->and($f['detail'])->not->toContain('行年');
});

test('二贵拱行年 分格：初末二贵 + 初末夹拱行年', function () {
    // 同上盘，xingnian = 11(亥)、nianming = 0(子)（本命不在夹拱带）。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 5,
        'sanchuan2' => 3,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1990-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => 0,
                    'xingnian' => 11,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('er_gui_gang_nianming');

    $f = collect($match->subMatches)->firstWhere('code', 'er_gui_gang_nianming');
    expect($f['matched'])->toBeTrue()
        ->and($f['detail'])->toContain('行年')
        ->and($f['detail'])->not->toContain('本命');
});

test('二贵拱年命 分格：两者皆缺才 people_missing', function () {
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 5,
        'sanchuan2' => 3,
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1980-06-01T00:00',
                    'gender' => 'male',
                    'nianming' => null,
                    'xingnian' => null,
                    'xingnian_gan' => null,
                ],
            ],
        ],
    ]);

    expect($match)->not->toBeNull();

    $f = collect($match->subMatches)->firstWhere('code', 'er_gui_gang_nianming');
    expect($f['matched'])->toBeFalse()
        ->and($f['people_missing'])->toBeTrue();

    expect($match->pendingRoutes)->toContain('er_gui_gang_nianming');
});

test('干支拱日禄 分格：伏吟 + 干支夹拱日禄', function () {
    // 丁巳日(3/5)，丁日禄午(6)。伏吟 tianpan=[0..11]。
    // 丁寄未(7)，日支巳(5)；未(7) 与 巳(5) 前后夹拱午(6)。
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 3,
        'rizhi' => 5,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gan_zhi_gang_ri_lu');
});

test('干支拱昼贵 分格：伏吟 + 干支夹拱昼贵', function () {
    // 甲子日，甲日昼贵丑(1)。伏吟 + 甲寄寅(2) + 日支子(0) → 寅(2) 与 子(0) 前后夹拱丑(1)。
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 0,
        'rizhi' => 0,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gan_zhi_gang_zhou_gui');
});

test('干支拱夜贵 分格：伏吟 + 干支夹拱夜贵', function () {
    // 庚午日(6)，庚日夜贵未(7)。伏吟 + 庚寄申(8) + 日支午(6) → 申(8) 与 午(6) 前后夹拱未(7)。
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 6,
        'rizhi' => 6,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gan_zhi_gang_ye_gui');
});

test('干支并初中拱同一昼夜贵人 分格：干支夹昼贵 + 初中亦夹同一昼贵', function () {
    // 甲子日伏吟 tp=[0..11]，甲寄寅(2)、日支子(0)、昼贵丑(1)。
    // flanks(2, 0, 1) 成立 → 干支拱昼贵；
    // 选 sanchuan0=0(子)、sanchuan1=2(寅) → initialGround=0, middleGround=2 → flanks(0, 2, 1) = (front=2, back=0) → (0==0 && 2==2) ✓
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 0,
        'rizhi' => 0,
        'sanchuan0' => 0,
        'sanchuan1' => 2,
        'sanchuan2' => 5,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('gan_zhi_bing_chu_zhong_gui');

    $i = collect($match->subMatches)->firstWhere('code', 'gan_zhi_bing_chu_zhong_gui');
    expect($i['matched'])->toBeTrue()
        ->and($i['detail'])->toContain('昼贵');
});

test('干支并初中拱贵人 不得误判：干支拱昼贵、初中拱夜贵时不命中', function () {
    // 甲子日伏吟：干支夹丑(1=昼贵)；让初中夹卯(3=夜贵)。
    // 卯前后 = (4, 2)。需要 (ig, mg) ∈ {(4, 2), (2, 4)}。
    // 伏吟下 sanchuan0=4(辰)→4, sanchuan1=2(寅)→2 → flanks(4, 2, 3)=(front=4, back=2)→(4==4 && 2==2) ✓
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 0,
        'rizhi' => 0,
        'sanchuan0' => 4,
        'sanchuan1' => 2,
        'sanchuan2' => 8,
        'calculationTrace' => ['plate_patterns' => ['fuyin']],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('gan_zhi_bing_chu_zhong_gui');

    $i = collect($match->subMatches)->firstWhere('code', 'gan_zhi_bing_chu_zhong_gui');
    expect($i['matched'])->toBeFalse();
});

test('年命资料缺失 不影响其他分格：其它分格命中、E/F 进入 pending_routes', function () {
    // 庚辰日（默认盘）yin_gan + gong_gui 命中；删掉 context.people 让 E/F 都缺人资料。
    $match = qhyc_match([
        'context' => ['people' => []],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('gong_gui');

    $e = collect($match->subMatches)->firstWhere('code', 'gui_lin_gan_zhi_gang_nianming');
    expect($e['requires_people'])->toBeTrue()
        ->and($e['matched'])->toBeFalse()
        ->and($e['people_missing'])->toBeTrue();

    $f = collect($match->subMatches)->firstWhere('code', 'er_gui_gang_nianming');
    expect($f['requires_people'])->toBeTrue()
        ->and($f['matched'])->toBeFalse()
        ->and($f['people_missing'])->toBeTrue();

    // E/F 因 people_missing 进入 pending_routes，而不是 matched_routes。
    expect($match->matchedRoutes)->not->toContain('gui_lin_gan_zhi_gang_nianming');
    expect($match->matchedRoutes)->not->toContain('er_gui_gang_nianming');
    expect($match->pendingRoutes)->toContain('gui_lin_gan_zhi_gang_nianming');
    expect($match->pendingRoutes)->toContain('er_gui_gang_nianming');
});

test('完全不命中且无待评估 → match() 返回 null', function () {
    // 选一个肯定不会命中任何分格的盘：戊午日(4/6)，identity tianpan，
    // sanchuan0=4(辰), sanchuan1=10(戌), sanchuan2=1(丑)；无伏吟、夹拱条件全不满足。
    // 同时给出完整人物资料——E/F 的 people_missing=false，因此不会进入 pending_routes。
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 4,
        'rizhi' => 6,
        'sanchuan0' => 4,
        'sanchuan1' => 10,
        'sanchuan2' => 1,
        'calculationTrace' => ['plate_patterns' => []],
        'context' => [
            'people' => [
                [
                    'role' => 'querent',
                    'birth_datetime' => '1986-08-01T00:00',
                    'gender' => 'male',
                    'nianming' => 0,
                    'xingnian' => 1,
                    'xingnian_gan' => 1,
                ],
            ],
        ],
    ]);

    // 不命中、且无待评估路线 → 整体返回 null。
    expect($match)->toBeNull();
});

test('默认庚辰盘 仅 yin_gan 与 gong_gui 命中，其余 8 个分格不命中', function () {
    $match = qhyc_match();

    expect($match)->not->toBeNull();

    $yinGan = collect($match->subMatches)->firstWhere('code', 'yin_gan');
    expect($yinGan['matched'])->toBeTrue();

    $gongGui = collect($match->subMatches)->firstWhere('code', 'gong_gui');
    expect($gongGui['matched'])->toBeTrue();

    expect($match->matchedRoutes)->toContain('yin_gan', 'gong_gui');

    foreach (['yin_zhi', 'liang_gui_yin_gan', 'gui_lin_gan_zhi_gang_nianming',
        'er_gui_gang_nianming', 'gan_zhi_gang_ri_lu', 'gan_zhi_gang_zhou_gui',
        'gan_zhi_gang_ye_gui', 'gan_zhi_bing_chu_zhong_gui'] as $code) {
        $sub = collect($match->subMatches)->firstWhere('code', $code);
        expect($sub)->not->toBeNull();
        expect($sub['matched'])->toBeFalse('分格 '.$code.' 在默认庚辰盘不应命中');
    }
});

test('definition() 返回 9 类古籍分格与 10 条程序 route', function () {
    $definition = (new QianHouYinCongRule)->definition();
    $codes = array_column($definition['foundations'], 'code');

    expect($codes)->toHaveCount(10)
        ->and($codes)->toContain(
            'yin_gan',
            'yin_zhi',
            'gong_gui',
            'liang_gui_yin_gan',
            'gui_lin_gan_zhi_gang_nianming',
            'er_gui_gang_nianming',
            'gan_zhi_gang_ri_lu',
            'gan_zhi_gang_zhou_gui',
            'gan_zhi_gang_ye_gui',
            'gan_zhi_bing_chu_zhong_gui',
        );
});
