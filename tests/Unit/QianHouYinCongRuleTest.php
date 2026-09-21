<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 构造毕法第一法基础盘面：庚辰日(6/4)，
 *  - tianpan = (i + 5) % 12（与 YinCongRuleTest 同源结构）；
 *  - 初传寅(2) 临地盘酉(9)；末传子(0) 临地盘未(7) → flanks(9, 7, 8) 夹拱庚寄宫申(8)；
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
function qhyc_match(array $overrides = []): ?\App\Domain\Pan\BiFa\BiFaRuleMatch
{
    return (new QianHouYinCongRule)->match(PanFacts::from(qhyc_pan($overrides)));
}

test('引从天干 分格：初末夹拱日干寄宫', function () {
    $match = qhyc_match();

    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('bifa.qian_hou_yin_cong')
        ->and($match->matchedRoutes)->toContain('yin_gan');

    $yinGan = collect($match->subMatches)->firstWhere('code', 'yin_gan');
    expect($yinGan['matched'])->toBeTrue()
        ->and($yinGan['detail'])->toContain('夹拱日干寄宫');
});

test('初末引从地支 分格：初末夹拱日支', function () {
    // 甲子日(0)，天盘旋转偏移 11：tianpan[1] = 0(子=午前一位)、tianpan[11] = 10(戌=午后一位)。
    // 初传子(0) 临地盘 1(丑)、末传戌(10) 临地盘 11(亥) → flanks(1, 11, 0) 夹拱日支子(0)。
    $match = qhyc_match([
        'tianpan' => [11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'rigan' => 0,
        'rizhi' => 0,
        'sanchuan0' => 0,
        'sanchuan2' => 10,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_zhi');

    $yinZhi = collect($match->subMatches)->firstWhere('code', 'yin_zhi');
    expect($yinZhi['matched'])->toBeTrue()
        ->and($yinZhi['detail'])->toContain('夹拱日支');
});

test('拱贵格 分格：引从天干 + 干上神是昼夜贵人之一（庚辰日 干上丑））', function () {
    // 默认盘：庚辰日，tianpan offset=5，tianpan[8] = 1(丑) = 庚日昼贵。
    $match = qhyc_match();

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('gong_gui');

    $gongGui = collect($match->subMatches)->firstWhere('code', 'gong_gui');
    expect($gongGui['matched'])->toBeTrue()
        ->and($gongGui['detail'])->toContain('昼贵');
});

test('两贵引从天干格 分格：引从天干 + {初传, 末传} == {昼贵, 夜贵}', function () {
    // 壬子日(8/0)：壬日昼贵巳(5)、夜贵卯(3)。
    // 沿用 offset=5：tianpan[0] = 5(巳)、tianpan[10] = 3(卯)。
    // sanchuan0 = 5(巳) → initialGround = 0；sanchuan2 = 3(卯) → finalGround = 10。
    // flanks(0, 10, 11) 夹拱壬寄宫亥(11)。
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

test('两贵引从天干格 分格：昼夜贵允许互换方向（夜贵在前、昼贵在后）', function () {
    // 壬子日，sanchuan0 = 3(卯=夜贵) 临地盘 10(亥)；sanchuan2 = 5(巳=昼贵) 临地盘 0(子)。
    // flanks(10, 0, 11) 仍夹拱壬寄宫亥(11)。
    // {3, 5} sort = {3, 5} = {夜贵, 昼贵} 命中。
    $match = qhyc_match([
        'rigan' => 8,
        'rizhi' => 0,
        'sanchuan0' => 3,
        'sanchuan2' => 5,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('liang_gui_yin_gan');
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
        ->and($e['detail'])->toContain('本命');
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
                    'nianming' => 2,
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
        ->and($e['detail'])->toContain('行年');
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
        ->and($f['detail'])->toContain('本命');
});

test('二贵拱行年 分格：初末二贵 + 初末夹拱行年', function () {
    // 同上盘，但本命改成子(0)（不夹拱），行年改成亥(11)（夹拱）。
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
        ->and($f['detail'])->toContain('行年');
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
    // 选 sanchuan0=2(寅)、sanchuan1=0(子) → initialGround=0, middleGround=0；
    // flanks(0, 0, 1)：front=2, back=0 → (0==2 && 0==0) false; (0==0 && 0==2) false → 不成立。
    // 调整为：sanchuan0=4(辰) → ground=4；sanchuan1=2(寅) → ground=2 → flanks(4, 2, 1) = (front=2, back=0) → (4==2 && 2==0) false; (4==0 && 2==2) false → 不成立。
    // 正确：sanchuan0=2(寅) ground=2，sanchuan1=10(酉) ground=10 → flanks(2, 10, 1) = (front=2, back=0) → (2==2 && 10==0) false; (2==0 && 10==2) false → 不成立。
    // 重新算：要让 (ig, mg) 夹拱 1(丑)，需要 (ig, mg) ∈ {(2, 0), (0, 2)} 排列。即 (2, 0) 或 (0, 2)。
    // 但 (0, 2)：tianpan[0] = 0 = 子，tianpan[2] = 2 = 寅。
    // 所以 sanchuan0 = 0(子)、sanchuan1 = 2(寅) → initialGround=0, middleGround=2 → flanks(0, 2, 1) = (front=2, back=0) → (0==2 && 2==0) false; (0==0 && 2==2) ✓
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
    // 伏吟下 tp[i]=i，tp[4]=4, tp[2]=2。所以 sanchuan0=4(辰)→4, sanchuan1=2(寅)→2 → flanks(4, 2, 3)=(front=4, back=2)→(4==4 && 2==2) ✓
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

test('年命资料缺失 不影响其他分格：其它分格命中、E/F 标记未评估', function () {
    // 庚辰日（默认盘）引从天干成立 + 拱贵格成立，但删掉 context.people 让 personByRole 解析为 null。
    $match = qhyc_match([
        'context' => ['people' => []],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('yin_gan')
        ->and($match->matchedRoutes)->toContain('gong_gui');

    $e = collect($match->subMatches)->firstWhere('code', 'gui_lin_gan_zhi_gang_nianming');
    expect($e['requires_people'])->toBeTrue()
        ->and($e['matched'])->toBeFalse();

    $f = collect($match->subMatches)->firstWhere('code', 'er_gui_gang_nianming');
    expect($f['requires_people'])->toBeTrue()
        ->and($f['matched'])->toBeFalse();

    // 缺人资料时，E/F 不应当作为命中（即使满足"贵临干支"或"二贵"条件）。
    expect($match->matchedRoutes)->not->toContain('gui_lin_gan_zhi_gang_nianming');
    expect($match->matchedRoutes)->not->toContain('er_gui_gang_nianming');
});

test('完全无路线成立时 第一法整体不命中', function () {
    // 选一个肯定不会命中任何分格的盘：identity tianpan、戊午日(4/6)、
    // 初传辰(4) 中传戌(10) 末传丑(1)；无伏吟、夹拱 A/D 不成立；
    // {干上, 支上} = (4, 6) ≠ 昼夜二贵、{初中末} ≠ 昼夜二贵、{初末} ≠ 昼夜二贵；
    // 缺人资料以一并排除 E/F。
    $match = qhyc_match([
        'tianpan' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        'rigan' => 4,
        'rizhi' => 6,
        'sanchuan0' => 4,
        'sanchuan1' => 10,
        'sanchuan2' => 1,
        'calculationTrace' => ['plate_patterns' => []],
        'context' => ['people' => []],
    ]);

    expect($match)->not->toBeNull();

    foreach ($match->subMatches as $sub) {
        expect($sub['matched'])
            ->toBeFalse('分格 ' . $sub['code'] . ' 在无关盘不应命中');
    }

    expect($match->matchedRoutes)->toBe([]);
});

test('默认庚辰盘 仅 yin_gan 与 gong_gui 命中，其余 7 个分格不命中', function () {
    // 验证"找第一条命中就 return"这一错误模式不复存在——
    // matcher 必须遍历全部 9 个分格；命中与未命中独立标记。
    $match = qhyc_match();

    expect($match)->not->toBeNull();

    // 基础分格 A / B 命中。
    $yinGan = collect($match->subMatches)->firstWhere('code', 'yin_gan');
    expect($yinGan['matched'])->toBeTrue();

    $gongGui = collect($match->subMatches)->firstWhere('code', 'gong_gui');
    expect($gongGui['matched'])->toBeTrue();

    expect($match->matchedRoutes)->toContain('yin_gan', 'gong_gui');

    // 其余 7 个分格在此默认盘上不命中。
    foreach (['yin_zhi', 'liang_gui_yin_gan', 'gui_lin_gan_zhi_gang_nianming',
            'er_gui_gang_nianming', 'gan_zhi_gang_ri_lu', 'gan_zhi_gang_zhou_gui',
            'gan_zhi_gang_ye_gui', 'gan_zhi_bing_chu_zhong_gui'] as $code) {
        $sub = collect($match->subMatches)->firstWhere('code', $code);
        expect($sub)->not->toBeNull();
        expect($sub['matched'])->toBeFalse('分格 ' . $code . ' 在默认庚辰盘不应命中');
    }
});