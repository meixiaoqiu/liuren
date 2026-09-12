<?php

use App\Livewire\Pan\CreatePan;
use App\Support\KeJingCatalog;
use Livewire\Livewire;

test('kejing page lists every lesson with its hexagram', function () {
    $response = $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('课经');

    foreach (KeJingCatalog::lessons() as $lesson) {
        $response
            ->assertSee($lesson['name'])
            ->assertSee($lesson['gua'].'卦');

        expect($lesson['cases'])->not->toBeEmpty();
    }
});

test('kejing page does not expose implementation names or internal numeric notation', function () {
    $this->get(route('kejing'))
        ->assertOk()
        ->assertDontSee('FateCalculator')
        ->assertDontSee('PanCalculator')
        ->assertDontSee('tianpan[')
        ->assertDontSee('sanchuan0')
        ->assertDontSee('YIN=')
        ->assertDontSee('OR 路径')
        ->assertDontSee('fixture')
        ->assertDontSee('pointer')
        ->assertDontSee('已 executable')
        ->assertDontSee('本命=')
        ->assertDontSee('行年=')
        ->assertDontSee('旬首=')
        ->assertDontSee('戊子日（4/0）')
        ->assertDontSee('亥月（11）');
});

test('every executable kejing case link reproduces its lesson on the pan page', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            if (($case['status'] ?? 'executable') !== 'executable') {
                continue;
            }

            $params = [
                'datetime' => $case['datetime'],
                'birth' => $case['birth'],
                'gender' => $case['gender'],
            ];

            if (! empty($case['people'])) {
                $params['people'] = $case['people'];
            }

            $this->get(route('pan.create', $params))
                ->assertOk()
                ->assertSee($lesson['name']);
        }
    }
});

test('he-mei catalog case reproduces the daquan cross-liuhe structure', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.he_mei');
    $case = collect($lesson['cases'])->firstWhere('case_id', 'lesson.he_mei.ren_wu_si_shi_chou_jiang');

    expect($case)->not->toBeNull()
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-01-08T09:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.he_mei');

    expect([$pan['rigan'], $pan['rizhi']])->toBe([8, 6])
        ->and($pan['yuejiang'])->toBe(1)
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([10, 6, 2])
        ->and($pan['tianpan'][11])->toBe(7)
        ->and($pan['tianpan'][6])->toBe(2)
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['matched_structures'])->toContain('cross_liuhe')
        ->and($match['evidence']['cross_liuhe'])->toBeTrue();
});

test('every kejing case declares its selection reason', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            expect($case['reason'])->not->toBeEmpty();
        }
    }
});

test('yincong catalog covers all seven textual structures and both noble directions', function () {
    $yincong = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.yincong');

    expect($yincong)->not->toBeNull();

    $labels = implode('', array_column($yincong['cases'], 'label'));

    expect($yincong['cases'])->toHaveCount(8)
        ->and($labels)
        ->toContain('拱天干', '拱地支', '两贵引从', '贵临干支拱年命', '干支拱日禄', '干支拱夜贵', '干支拱昼贵', '反向');
});

test('hengtong catalog covers all five grids and both di-sheng directions', function () {
    $hengtong = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.hengtong');

    expect($hengtong)->not->toBeNull();

    $labels = implode('', array_column($hengtong['cases'], 'label'));

    expect($hengtong['cases'])->toHaveCount(6)
        ->and($labels)
        ->toContain('递生格', '俱生格', '互生格', '互旺格', '俱旺格', '逆');
});

test('fanchang catalog covers both the de-yun and wang-yun grids', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    expect($fanchang)->not->toBeNull();

    $labels = implode('', array_column($fanchang['cases'], 'label'));

    expect($fanchang['cases'])->toHaveCount(2)
        ->and($labels)
        ->toContain('德孕格', '旺孕格');
});

test('rong-hua catalog contains the daquan bing-yin and ren-shen executable cases', function () {
    $rongHua = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.rong_hua');

    expect($rongHua)->not->toBeNull()
        ->and($rongHua['gua'])->toBe('渐')
        ->and($rongHua['guaSymbol'])->toBe('䷴');

    $labels = implode('', array_column($rongHua['cases'], 'label'));

    expect($rongHua['cases'])->toHaveCount(2)
        ->and($labels)
        ->toContain('丙寅日正文结构', '壬申日正文结构');
});

test('rong-hua catalog and kejing page preserve every named daquan example', function () {
    $rongHua = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.rong_hua');
    $labels = array_column($rongHua['source_examples'], 'label');

    expect($rongHua['source_examples'])->toHaveCount(16)
        ->and($labels)->toContain(
            '丙寅日', '壬申日', '癸丑日', '甲申日', '乙酉日', '丁卯日',
            '甲子日', '丁酉日', '丙丁日', '丁丑日', '六丁日',
            '丙申日卯时子将', '庚辰日亥加寅',
        );

    $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('古籍相关课例与旁证')
        ->assertSee('壬申日')
        ->assertSee('贵人蹉跎')
        ->assertSee('遍地贵人')
        ->assertSee('丙申日卯时子将')
        ->assertSee('庚辰日亥加寅');
});

test('he-huan catalog declares the daquan wu-shen executable case and references its path', function () {
    $heHuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.he_huan');

    expect($heHuan)->not->toBeNull()
        ->and($heHuan['gua'])->toBe('井')
        ->and($heHuan['guaSymbol'])->toBe('䷯')
        ->and($heHuan['cases'])->toHaveCount(1);

    $case = collect($heHuan['cases'])->firstWhere('case_id', 'lesson.he_huan.wu_shen_ri');

    expect($case)->not->toBeNull()
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2000-06-19T00:00')
        ->and($case['gender'])->toBe('male');

    $sourceLabels = array_column($heHuan['source_examples'], 'label');

    expect($sourceLabels)->toContain(
        '戊申日子时申将', '丙申日反吟', '辛卯日', '壬寅日', '甲申日',
        '丁丑、己丑日', '戊辰日', '辛酉日', '乙酉日',
    );
});

test('he-huan executable case reproduces the lesson on the pan page', function () {
    $heHuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.he_huan');

    $case = collect($heHuan['cases'])->firstWhere('case_id', 'lesson.he_huan.wu_shen_ri');

    $component = Livewire::withQueryParams([
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $component->assertSee('合欢课')
        ->assertSee('井卦')
        ->assertSee('䷯')
        ->assertSee('乾坤匹配');
});

test('rong-hua bing-yin case is executable and reproduces the lesson on the pan page', function () {
    $rongHua = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.rong_hua');

    $case = collect($rongHua['cases'])->firstWhere('case_id', 'lesson.rong_hua.bing_yin');

    expect($case)->not->toBeNull();
    expect($case['status'])->toBe('executable');

    $component = Livewire::withQueryParams([
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $component->assertSee('荣华课')
        ->assertSee('渐卦')
        ->assertSee('䷴')
        ->assertSee('干支吉神，入宅俱利');
});

test('kejing page fanchang case links carry the spouse context', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $case = $fanchang['cases'][0];

    $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('people%5B0%5D%5Brole%5D=spouse', false)
        ->assertSee(rawurlencode($case['people'][0]['birth_datetime']), false);
});

test('fanchang de-yun case is executable and reproduces the pan evidence with de-yun grid', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $case = collect($fanchang['cases'])->firstWhere('case_id', 'lesson.fanchang.de_yun');

    expect($case)->not->toBeNull();
    expect($case['status'])->toBe('executable');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->set('people', $case['people'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');

    // 盘面证据：月将巳、三传午辰寅、地盘寅上见子、地盘亥上见酉。
    expect($pan['yuejiang'])->toBe(5)
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([6, 4, 2])
        ->and($pan['tianpan'][2])->toBe(0)
        ->and($pan['tianpan'][11])->toBe(9);

    // 德孕格按《观月经》口径命中：行年甲己合 + 寅亥合。
    $component->assertSee('德孕格');
    $component->assertSee('甲己');
    $component->assertSee('寅亥');
    $component->assertSee('《观月经》');
    $component->assertSee('不论三传');

    // 旺孕格在三合不成立的盘面下不命中。
    $component->assertDontSee('旺孕格依据');
});

test('every kejing case status is executable or reference_only', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            expect(in_array($case['status'] ?? 'executable', ['executable', 'reference_only'], true))
                ->toBeTrue("课例 {$lesson['code']} / {$case['label']} 出现非法 status: ".($case['status'] ?? 'null'));
        }
    }
});

test('kejing page never renders the not-covered badge when no reference_only case exists', function () {
    // 当前所有课例均为 executable；reference_case 机制保留供未来争议课例使用。
    $response = $this->get(route('kejing'))->assertOk();

    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            expect(($case['status'] ?? 'executable'))->toBe('executable');
        }
    }

    $response->assertDontSee('原文参考盘·尚未覆盖');
});

test('fanchang de-yun executable case never exposes the not-covered banner on the pan page', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $case = collect($fanchang['cases'])->firstWhere('case_id', 'lesson.fanchang.de_yun');

    $params = [
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ];

    if (! empty($case['people'])) {
        $params['people'] = $case['people'];
    }

    $component = Livewire::withQueryParams($params)
        ->test(CreatePan::class)
        ->assertHasNoErrors();

    // executable 课例永远不应出现「原文参考盘」字样。
    $component->assertDontSee('原文参考盘');
    $component->assertDontSee('尚未覆盖');

    // 但德孕格本身应被正确显示。
    $component->assertSee('繁昌课');
    $component->assertSee('德孕格');
});

test('kejing page executable links never carry the reference_case query', function () {
    $response = $this->get(route('kejing'))->assertOk();
    $body = $response->getContent();

    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            if (($case['status'] ?? 'executable') !== 'executable') {
                continue;
            }

            expect($case)->not->toHaveKey('case_id_reference_only');
            expect($body)->not->toContain('reference_case='.rawurlencode($case['case_id'] ?? '___nope___'));
        }
    }
});

test('executable case links from kejing do not expose the reference banner on the pan page', function () {
    $wangYun = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang')['cases'][1];

    expect($wangYun['status'])->toBe('executable');

    $params = [
        'datetime' => $wangYun['datetime'],
        'birth' => $wangYun['birth'],
        'gender' => $wangYun['gender'],
        'people' => $wangYun['people'],
    ];

    $component = Livewire::withQueryParams($params)
        ->test(CreatePan::class)
        ->assertHasNoErrors()
        ->assertSee('繁昌课')
        ->assertSee('旺孕格');

    $component->assertDontSee('原文参考盘');
});

test('KeJingCatalog::findReferenceCase returns null for executable case ids and forged ids', function () {
    // 当前所有课例均为 executable；findReferenceCase 仍存在以备未来争议课例使用。
    expect(KeJingCatalog::findReferenceCase('lesson.fanchang.de_yun'))->toBeNull();
    expect(KeJingCatalog::findReferenceCase('lesson.fanchang.wang_yun'))->toBeNull();
    expect(KeJingCatalog::findReferenceCase('totally-forged-id'))->toBeNull();
    expect(KeJingCatalog::findReferenceCase(''))->toBeNull();
});

test('forged or unknown reference_case never shows the reference banner', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $case = $fanchang['cases'][0];

    foreach (['totally-forged-id', 'lesson.fanchang.wang_yun', 'lesson.fugui.xin_si_ri_yin_ming', ''] as $forged) {
        $params = [
            'datetime' => $case['datetime'],
            'birth' => $case['birth'],
            'gender' => $case['gender'],
        ];

        if (! empty($case['people'])) {
            $params['people'] = $case['people'];
        }

        $params['reference_case'] = $forged;

        $component = Livewire::withQueryParams($params)
            ->test(CreatePan::class)
            ->assertHasNoErrors();

        $component->assertDontSee('原文参考盘');
    }
});

test('zhan-guan catalog declares the daquan jia-yin and modern yang-hai executable cases and references its paths', function () {
    $zhanGuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.zhan_guan');

    expect($zhanGuan)->not->toBeNull()
        ->and($zhanGuan['gua'])->toBe('遁')
        ->and($zhanGuan['guaSymbol'])->toBe('䷠')
        ->and($zhanGuan['cases'])->toHaveCount(2);

    $jiaYin = collect($zhanGuan['cases'])->firstWhere('case_id', 'lesson.zhan_guan.jia_yin_hai_shi_wei_jiang');
    $yiHai = collect($zhanGuan['cases'])->firstWhere('case_id', 'lesson.zhan_guan.yi_hai_zi_shi');

    expect($jiaYin)->not->toBeNull()
        ->and($jiaYin['status'])->toBe('executable')
        ->and($jiaYin['datetime'])->toBe('1904-07-19T21:00');

    expect($yiHai)->not->toBeNull()
        ->and($yiHai['status'])->toBe('executable')
        ->and($yiHai['datetime'])->toBe('2026-01-01T01:00');

    $sourceLabels = array_column($zhanGuan['source_examples'], 'label');

    expect($sourceLabels)->toContain(
        '甲寅日亥时未将', '神藏煞没（四大吉时）', '魁渡天门（反向描述）', '传有虎阴申酉（斩关得断）', '凡见辰戌加日辰发用者'
    );
});

test('zhan-guan executable case reproduces the lesson on the pan page', function () {
    $zhanGuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.zhan_guan');

    $case = collect($zhanGuan['cases'])->firstWhere('case_id', 'lesson.zhan_guan.yi_hai_zi_shi');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.zhan_guan');

    // 乙亥日子时，伏吟：tianpan == range(0,11)，干上 = tianpan[寄宫辰] = 辰，支上 = tianpan[亥] = 亥
    expect([$pan['rigan'], $pan['rizhi']])->toBe([1, 11])
        ->and($pan['tianpan'][4])->toBe(4)        // 干上 = 辰 = 寄宫位
        ->and($pan['tianpan'][11])->toBe(11)      // 支上 = 亥
        ->and($pan['sanchuan0'])->toBe(4)         // 初传辰
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['is_tian_gang'])->toBeTrue()
        ->and($match['evidence']['on_day_stem'])->toBeTrue();

    $component->assertSee('斩关课')
        ->assertSee('遁卦')
        ->assertSee('䷠')
        ->assertSee('天罡（辰）');
});

test('zhan-guan production xiang text uses the shidianguji base copy, not the modern paraphrase', function () {
    // 课经文档明确采用识典底本"捉贼难获，出行自强，厌祷吉详"，并声明
    // "出行无殃"为已修正的旧整理本文字。本测试确认前台生产文案与底本一致。
    $zhanGuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.zhan_guan');

    $case = collect($zhanGuan['cases'])->firstWhere('case_id', 'lesson.zhan_guan.yi_hai_zi_shi');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $component
        ->assertSee('捉贼难获')
        ->assertSee('出行自强')
        ->assertSee('厌祷吉详')
        ->assertDontSee('捕贼难获')
        ->assertDontSee('出行无殃')
        ->assertDontSee('厌祷吉祥');
});

test('zhan-guan jia-yin case reproduces the daquan xu-jia-yin structure', function () {
    $zhanGuan = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.zhan_guan');

    $case = collect($zhanGuan['cases'])->firstWhere('case_id', 'lesson.zhan_guan.jia_yin_hai_shi_wei_jiang');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.zhan_guan');

    // 甲寅日：日干甲寄寅(2)，日支寅(2)。要求"戌加寅为用"：初传=戌(10) + 支上神=戌(10)
    // 即 tianpan[rizhi=2] = 10 (戌)，且 sanchuan0 = 10。三传应为戌午寅。
    expect([$pan['rigan'], $pan['rizhi']])->toBe([0, 2])
        ->and($pan['yuejiang'])->toBe(7)        // 月将未
        ->and($pan['tianpan'][2])->toBe(10)     // 戌加寅
        ->and($pan['sanchuan0'])->toBe(10)      // 初传戌
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([10, 6, 2])
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['is_tian_kui'])->toBeTrue()
        ->and($match['evidence']['on_day_branch'])->toBeTrue();
});

test('bikou catalog example is executable and reproduces the daquan structure', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.bikou');
    $case = $lesson['cases'][0];

    expect($lesson['gua'])->toBe('谦')
        ->and($lesson['guaSymbol'])->toBe('䷎')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('1904-02-20T05:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.bikou');
    expect([$pan['rigan'], $pan['rizhi'], $pan['yuejiang']])->toBe([0, 8, 0])
        ->and($pan['tianpan'][8])->toBe(5)
        ->and($pan['sanchuan0'])->toBe(5)
        ->and($match['evidence']['tail_on_head'])->toBeTrue();
});

test('bikou catalog reproduces the daquan jia-zi upper-god-riding-xuanwu path', function () {
    // 第三路：甲子日·地盘子位上神=辰、地盘子位天将=玄武(9)、初传=辰
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.bikou');
    $case = collect($lesson['cases'])->firstWhere('case_id', 'lesson.bikou.jia_zi_chen_jia_zi_fa_yong');

    expect($case)->not->toBeNull()
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('1920-01-07T17:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.bikou');

    expect([$pan['rigan'], $pan['rizhi']])->toBe([0, 0])
        ->and($pan['tianpan'][0])->toBe(4)        // 地盘子位上神 = 辰
        ->and($pan['tianjiang'][0])->toBe(9)      // 地盘子位天将 = 玄武
        ->and($pan['sanchuan0'])->toBe(4)         // 初传 = 辰
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['head_upper_riding_xuanwu'])->toBeTrue();
});

test('bikou catalog source_examples labels one path per daquan textual case', function () {
    // 三路各对应 1 条 source_examples，明确路径标签。
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.bikou');

    expect($lesson['source_examples'])->toHaveCount(4);

    $labels = array_column($lesson['source_examples'], 'label');
    $paths = array_column($lesson['source_examples'], 'path');

    expect($labels)->toContain('甲申日卯时子将', '丁酉日午加酉', '甲子日辰加子')
        ->and($paths)->toContain('第一路·旬尾加旬首', '第二路·旬首乘玄武', '第三路·地盘旬首位上神乘玄武');
});

test('bikou catalog records yixun zhoubian as an independent related grid', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.bikou');
    $example = collect($lesson['source_examples'])->firstWhere('label', '乙未日卯时寅将');

    expect($example)->not->toBeNull()
        ->and($example['detail'])->toContain('独立成立')
        ->and($example['detail'])->toContain('不要求闭口课命中');
});

test('youzi catalog example is executable and reproduces the daquan structure', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.youzi');
    $case = $lesson['cases'][0];

    expect($lesson['name'])->toBe('游子课')
        ->and($lesson['gua'])->toBe('观')
        ->and($lesson['guaSymbol'])->toBe('䷓')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2022-04-22T11:00')
        ->and(collect($lesson['source_examples'])->pluck('label'))->toContain('三月将乙巳日午时');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.youzi');

    expect([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([7, 10, 1])
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['xun_ding'])->toBe(7)
        ->and($match['evidence']['month_tianma'])->toBe(10)
        ->and($match['evidence']['initial'])->toBe(7)
        ->and($match['evidence']['xun_ding_as_initial'])->toBeTrue()
        ->and($match['evidence']['month_tianma_as_initial'])->toBeFalse();
});

test('zhuixu catalog examples are executable and reproduce both daquan paths', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.zhuixu');

    expect($lesson)->not->toBeNull()
        ->and($lesson['name'])->toBe('赘婿课')
        ->and($lesson['gua'])->toBe('旅')
        ->and($lesson['guaSymbol'])->toBe('䷷')
        ->and($lesson['cases'])->toHaveCount(2)
        ->and(collect($lesson['cases'])->pluck('status')->all())->toBe(['executable', 'executable']);

    foreach ($lesson['cases'] as $index => $case) {
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])
            ->set('birthDatetime', $case['birth'])
            ->set('gender', $case['gender'])
            ->call('calculate')
            ->assertHasNoErrors();

        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.zhuixu');
        expect($match)->not->toBeNull();

        if ($index === 0) {
            expect($match['evidence']['branch_path'])->toBeTrue()
                ->and($match['evidence']['stem_path'])->toBeFalse();
        } else {
            expect($match['evidence']['stem_path'])->toBeTrue()
                ->and($match['evidence']['stem_lodging_branch'])->toBe(5);
        }
    }
});

test('sanjiao is ordered between youzi and zhuixu and its catalog case is executable', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $sanjiao = $lessons->firstWhere('code', 'lesson.sanjiao');
    $case = $sanjiao['cases'][0];

    expect(array_search('lesson.sanjiao', $codes, true))->toBe(array_search('lesson.youzi', $codes, true) + 1)
        ->and(array_search('lesson.zhuixu', $codes, true))->toBe(array_search('lesson.sanjiao', $codes, true) + 1)
        ->and($sanjiao['name'])->toBe('三交课')
        ->and($sanjiao['gua'])->toBe('姤')
        ->and($sanjiao['guaSymbol'])->toBe('䷫')
        ->and($case['case_id'])->toBe('lesson.sanjiao.wu_zi_wu_shi_you_jiang')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-05-14T11:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.sanjiao');

    expect($pan['sike'])->toBe([4, 8, 8, 11, 0, 3, 3, 6])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([3, 6, 9])
        ->and([$pan['sanchuan0tianjiang'], $pan['sanchuan1tianjiang'], $pan['sanchuan2tianjiang']])->toBe([10, 7, 4])
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['matched_yin_he_occurrences'])->not->toBeEmpty();
});

test('chongpo follows zhuixu and its combined-source reproduction is executable', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.chongpo');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.chongpo', $codes, true))->toBe(array_search('lesson.zhuixu', $codes, true) + 1)
        ->and($lesson['name'])->toBe('冲破课')
        ->and($lesson['gua'])->toBe('夬')
        ->and($lesson['guaSymbol'])->toBe('䷪')
        ->and($lesson['summary'])->toContain('自身破位')
        ->and($case['case_id'])->toBe('lesson.chongpo.zi_nian_geng_zi_wei_shi_xu_jiang')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2056-04-18T13:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.chongpo');

    expect([$pan['nianzhi'], $pan['rigan'], $pan['rizhi'], $pan['shizhi'], $pan['yuejiang']])->toBe([0, 6, 0, 7, 10])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([6, 9, 0])
        ->and($pan['tianpan'][3])->toBe(6)
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['branch_path'])->toBeTrue()
        ->and($match['evidence']['initial_ground'])->toBe(3)
        ->and($match['evidence']['initial_break_ground'])->toBe(3);
});

test('yinyi follows chongpo and its catalog keeps yinv as an independent source example', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.yinyi');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.yinyi', $codes, true))->toBe(array_search('lesson.chongpo', $codes, true) + 1)
        ->and($lesson['name'])->toBe('淫泆课')
        ->and($lesson['gua'])->toBe('既济')
        ->and($lesson['guaSymbol'])->toBe('䷾')
        ->and($case['case_id'])->toBe('lesson.yinyi.xin_wei_shen_shi_chen_jiang')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2020-09-25T15:00')
        ->and(collect($lesson['source_examples'])->firstWhere('path', '独立附格·泆女格')['detail'])->toContain('淫泆主体不成立');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();
    $pan = $component->get('pan');
    $matches = collect($component->get('ruleMatches'));

    expect([$pan['rigan'], $pan['rizhi'], $pan['shizhi'], $pan['yuejiang']])->toBe([7, 7, 8, 4])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([3, 11, 7])
        ->and([$pan['sanchuan0tianjiang'], $pan['sanchuan1tianjiang'], $pan['sanchuan2tianjiang']])->toBe([3, 7, 11])
        ->and($matches->firstWhere('code', 'lesson.yinyi'))->not->toBeNull()
        ->and($matches->firstWhere('code', 'structure.jiaotong'))->not->toBeNull()
        ->and($matches->firstWhere('code', 'structure.yinv'))->toBeNull();
});

test('wuyin follows yinyi and all three daquan cases execute through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.wuyin');
    expect(array_search('lesson.wuyin', $codes, true))->toBe(array_search('lesson.yinyi', $codes, true) + 1)
        ->and($lesson['name'])->toBe('芜淫课')->and($lesson['gua'])->toBe('小畜')->and($lesson['guaSymbol'])->toBe('䷈')
        ->and(count($lesson['cases']))->toBe(3);

    foreach ($lesson['cases'] as $case) {
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        expect($case['status'])->toBe('executable')
            ->and(collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.wuyin'))->not->toBeNull();
    }
});

test('jieli follows wuyin and its daquan structure reproduction is executable without a hexagram', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.jieli');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.jieli', $codes, true))->toBe(array_search('lesson.wuyin', $codes, true) + 1)
        ->and($lesson['name'])->toBe('解离课')
        ->and($lesson['gua'])->toBeNull()
        ->and($lesson['guaSymbol'])->toBeNull()
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-03-01T05:00')
        ->and($case['people'])->toHaveCount(1)
        ->and($case['people'][0]['role'])->toBe('spouse');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->set('people', $case['people'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.jieli');

    expect([$pan['shizhi'], $pan['yuejiang']])->toBe([3, 11])
        ->and($pan['tianpan'])->toBe([8, 9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7])
        ->and($match)->not->toBeNull()
        ->and([$match['evidence']['fu_xingnian'], $match['evidence']['qi_xingnian']])->toBe([6, 0])
        ->and([$match['evidence']['fu_upper'], $match['evidence']['qi_upper']])->toBe([2, 8]);
});

test('due follows jieli and both daquan cases execute through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.due');
    expect(array_search('lesson.due', $codes, true))->toBe(array_search('lesson.jieli', $codes, true) + 1)
        ->and($lesson['name'])->toBe('度厄课')->and($lesson['gua'])->toBe('剥')->and($lesson['guaSymbol'])->toBe('䷖')
        ->and($lesson['summary'])->toContain('恰有三课')->and(count($lesson['cases']))->toBe(2);

    foreach ($lesson['cases'] as $case) {
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        expect($case['status'])->toBe('executable')
            ->and(collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.due'))->not->toBeNull();
    }
});

test('wulu-juesi follows due and both daquan cases execute through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.wulu_juesi');
    expect(array_search('lesson.wulu_juesi', $codes, true))->toBe(array_search('lesson.due', $codes, true) + 1)
        ->and($lesson['name'])->toBe('无禄绝嗣课')->and($lesson['gua'])->toBe('否')->and($lesson['guaSymbol'])->toBe('䷋')
        ->and($lesson['summary'])->toBe('四上俱克下为无禄，四下俱贼上为绝嗣。')->and(count($lesson['cases']))->toBe(2);

    foreach ($lesson['cases'] as $case) {
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        expect($case['status'])->toBe('executable')
            ->and(collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.wulu_juesi'))->not->toBeNull();
    }
});

test('qinhai follows wulu-juesi and its daquan case executes through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.qinhai');
    expect(array_search('lesson.qinhai', $codes, true))->toBe(array_search('lesson.wulu_juesi', $codes, true) + 1)
        ->and($lesson['name'])->toBe('侵害课')->and($lesson['gua'])->toBe('损')->and($lesson['guaSymbol'])->toBe('䷨')
        ->and($lesson['cases'])->toHaveCount(1)->and($lesson['cases'][0]['status'])->toBe('executable');

    $case = $lesson['cases'][0];
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.qinhai');
    expect($match)->not->toBeNull()->and($match['evidence']['matched_routes'])->toBe(['branch'])
        ->and($match['evidence']['routes'][0]['initial_role'])->toBe('lower');
});
