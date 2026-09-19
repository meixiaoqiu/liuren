<?php

use App\Domain\Pan\Rules\RuleRegistry;
use App\Livewire\Pan\CreatePan;
use App\Support\KeJingCatalog;
use Livewire\Livewire;

test('kejing index lists every lesson with its hexagram and detail link', function () {
    $response = $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('课经');

    $lessons = KeJingCatalog::lessons();

    foreach ($lessons as $lesson) {
        $slug = str_replace('_', '-', substr($lesson['code'], strlen('lesson.')));

        $response->assertSee($lesson['name']);

        if ($lesson['gua'] !== null) {
            $response->assertSee($lesson['gua'].'卦');
        }

        if ($lesson['guaSymbol'] !== null) {
            $response->assertSee($lesson['guaSymbol']);
        }

        $response->assertSee(route('kejing.show', ['lesson' => $slug]), false);

        expect($lesson['cases'])->not->toBeEmpty();
    }

    $response
        ->assertDontSee($lessons[0]['summary'])
        ->assertDontSee($lessons[0]['cases'][0]['reason'])
        ->assertDontSee('古籍相关课例与旁证');
});

test('kejing detail page shows the product summary executable cases and research record', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');
    $case = $lesson['cases'][0];

    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee('三光课')
        ->assertSee('䷕')
        ->assertSee('贲卦')
        ->assertSee($lesson['summary'])
        ->assertSee($case['label'])
        ->assertSee($case['reason'])
        ->assertSee('完整研究记录')
        ->assertSee('11-%E4%B8%89%E5%85%89%E8%AF%BE.md', false)
        ->assertSee('三阳课 · 下一课 →')
        ->assertDontSee('上一课 ·');
});

test('unknown kejing detail slug returns 404', function () {
    $this->get('/kejing/not-a-real-lesson')->assertNotFound();
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

test('rong-hua catalog and detail page preserve every named daquan example', function () {
    $rongHua = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.rong_hua');
    $labels = array_column($rongHua['source_examples'], 'label');

    expect($rongHua['source_examples'])->toHaveCount(16)
        ->and($labels)->toContain(
            '丙寅日', '壬申日', '癸丑日', '甲申日', '乙酉日', '丁卯日',
            '甲子日', '丁酉日', '丙丁日', '丁丑日', '六丁日',
            '丙申日卯时子将', '庚辰日亥加寅',
        );

    $this->get(route('kejing.show', ['lesson' => 'rong-hua']))
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

test('kejing fanchang detail links carry the spouse context', function () {
    $fanchang = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $case = $fanchang['cases'][0];

    $this->get(route('kejing.show', ['lesson' => 'fanchang']))
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

test('kejing page renders the not-covered badge for the declared reference-only case', function () {
    $referenceCases = [];
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            if (($case['status'] ?? 'executable') === 'reference_only') {
                $referenceCases[] = $case['case_id'];
            }
        }
    }

    expect($referenceCases)->toBe([
        'lesson.sanyin.classic_gui_chou_mao_time',
        'lesson.siqi.classic_jia_zi_chou_time_si_general',
        'lesson.yangjiu.geng_wu_external_reference',
        'lesson.yangjiu.ji_you_internal_reference',
        'lesson.liuchun.daquan_jia_wu_gan_shang_zi',
    ]);

    $response = $this->get(route('kejing.show', ['lesson' => 'sanyin']))
        ->assertOk();

    $response->assertSee('原文参考盘·尚未覆盖');
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

test('kejing executable detail links never carry the reference_case query', function () {
    $lesson = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.fanchang');

    $response = $this->get(route('kejing.show', ['lesson' => 'fanchang']))->assertOk();
    $body = $response->getContent();

    foreach ($lesson['cases'] as $case) {
        expect($case['status'])->toBe('executable');
        expect($case)->not->toHaveKey('case_id_reference_only');
        expect($body)->not->toContain('reference_case='.rawurlencode($case['case_id'] ?? '___nope___'));
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

test('zhunfu follows qinhai and its supplemented daquan case executes through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.zhunfu');

    expect(array_search('lesson.zhunfu', $codes, true))->toBe(array_search('lesson.qinhai', $codes, true) + 1)
        ->and($lesson['name'])->toBe('迍福课')->and($lesson['gua'])->toBe('屯')->and($lesson['guaSymbol'])->toBe('䷂')
        ->and($lesson['cases'])->toHaveCount(1);

    $case = $lesson['cases'][0];
    expect($case['status'])->toBe('executable')
        ->and($case['case_id'])->toBe('lesson.zhunfu.gui_you_wu_shi_hai_jiang')
        ->and($case['datetime'])->toBe('2026-02-28T11:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.zhunfu');

    expect($match)->not->toBeNull()
        ->and([$pan['rigan'], $pan['rizhi']])->toBe([9, 9])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([7, 0, 5])
        ->and($match['evidence']['conditions'])->toHaveCount(13)
        ->and(in_array(false, array_values($match['evidence']['conditions']), true))->toBeFalse()
        ->and($match['evidence']['zhun_count'])->toBe(8)
        ->and($match['evidence']['fu_count'])->toBe(5);
});

test('xingshang follows zhunfu and its program-verified case executes through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.xingshang');

    expect(array_search('lesson.xingshang', $codes, true))->toBe(array_search('lesson.zhunfu', $codes, true) + 1)
        ->and($lesson['name'])->toBe('刑伤课')->and($lesson['gua'])->toBe('讼')->and($lesson['guaSymbol'])->toBe('䷅')
        ->and($lesson['cases'])->toHaveCount(1)->and($lesson['cases'][0]['status'])->toBe('executable')
        ->and($lesson['cases'][0]['label'])->toContain('程序验证样本')
        ->and($lesson['cases'][0]['birth'])->toBe('2000-01-01T00:00')
        ->and($lesson['cases'][0]['gender'])->toBe('male');

    $case = $lesson['cases'][0];
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.xingshang');
    expect($match)->not->toBeNull()->and($match['evidence']['matched_routes'])->toBe(['stem'])
        ->and($match['evidence']['initial'])->toBe(4)->and($match['evidence']['punished_branch'])->toBe(4);
});

test('tianhuo is lesson 44 after erfan and both catalog directions execute through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.tianhuo');

    expect(array_search('lesson.tianhuo', $codes, true))->toBe(array_search('lesson.erfan', $codes, true) + 1)
        ->and($lesson['name'])->toBe('天祸课')->and($lesson['gua'])->toBe('大过')->and($lesson['guaSymbol'])->toBe('䷛')
        ->and($lesson['summary'])->toBe('四立日，今日干支临昨日干支，或昨日干支临今日干支。')
        ->and($lesson['summary'])->not->toContain('只看干')->and($lesson['cases'])->toHaveCount(2);

    foreach ($lesson['cases'] as $case) {
        expect($case['status'])->toBe('executable');
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.tianhuo');
        expect($match)->not->toBeNull()->and($match['evidence']['matched_directions'])->toHaveCount(1);
    }
});

test('erfan is lesson 43 with a real executable four-ping case', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.erfan');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.erfan', $codes, true))->toBe(array_search('lesson.xingshang', $codes, true) + 1)
        ->and(array_search('lesson.tianhuo', $codes, true))->toBe(array_search('lesson.erfan', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['二烦课', '明夷', '䷣'])
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-04-20T11:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.erfan');

    expect($match)->not->toBeNull()
        ->and($match['evidence']['four_ping'])->toBeTrue()
        ->and($match['evidence']['four_zheng'])->toBeFalse()
        ->and([$match['evidence']['day_lodge_ground'], $match['evidence']['moon_lodge'], $match['evidence']['moon_lodge_ground'], $match['evidence']['dougang_ground']])->toBe([6, 9, 6, 1]);
});

test('tianyu is lesson 45 after tianhuo and both frozen routes execute through production rules', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.tianyu');

    expect(array_search('lesson.tianyu', $codes, true))->toBe(array_search('lesson.tianhuo', $codes, true) + 1)
        ->and($lesson['name'])->toBe('天狱课')->and($lesson['gua'])->toBe('噬嗑')->and($lesson['guaSymbol'])->toBe('䷔')
        ->and($lesson['summary'])->toBe('初传为时令囚、死或日墓，且天罡辰临日干长生位。')
        ->and($lesson['summary'])->not->toContain('休囚死')->and($lesson['cases'])->toHaveCount(2);

    foreach ($lesson['cases'] as $index => $case) {
        expect($case['status'])->toBe('executable');
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.tianyu');
        expect($match)->not->toBeNull()->and($match['evidence']['dou_xi_ri_ben'])->toBeTrue();

        if ($index === 0) {
            expect($match['evidence']['matched_initial_routes'])->toBe(['seasonal_si', 'day_grave']);
        } else {
            expect($match['evidence']['matched_initial_routes'])->toBe(['day_grave'])
                ->and($match['evidence']['initial_seasonal_state'])->toBe('相');
        }
    }
});

test('tiankou is lesson 46 after tianyu with a real executable production case', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.tiankou');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.tiankou', $codes, true))->toBe(array_search('lesson.tianyu', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['天寇课', '蹇', '䷦'])
        ->and($lesson['summary'])->toBe('春分、夏至、秋分或冬至日，月宿加临前一日地支（离辰）。')
        ->and($lesson['summary'])->not->toContain('四离日前一日')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-03-20T07:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.tiankou');

    expect($match)->not->toBeNull()
        ->and($match['evidence']['fen_zhi_name'])->toBe('春分')
        ->and($match['evidence']['day'])->toBe('癸巳')
        ->and($match['evidence']['previous_day'])->toBe('壬辰')
        ->and([$match['evidence']['moon_palace'], $match['evidence']['moon_palace_ground'], $match['evidence']['li_branch']])->toBe([11, 4, 4]);
});

test('tianwang is lesson 47 after tiankou with a different-branch executable production case', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.tianwang');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.tianwang', $codes, true))->toBe(array_search('lesson.tiankou', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['天网课', '蒙', '䷃'])
        ->and($lesson['summary'])->toBe('占时支与初传分别克日干。')
        ->and($lesson['summary'])->not->toContain('相同')
        ->and($case['case_id'])->toBe('lesson.tianwang.xin_si_si_time_wu_initial')
        ->and($case['status'])->toBe('executable')
        ->and($case['datetime'])->toBe('2026-01-07T09:00')
        ->and($case['reason'])->toContain('真实生产盘')
        ->and($lesson['source_examples'][0]['detail'])->toContain('现代候选')
        ->and($lesson['source_examples'][0]['detail'])->not->toContain('古籍原日期');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.tianwang');

    expect($match)->not->toBeNull()
        ->and($match['evidence']['time_equals_initial'])->toBeFalse()
        ->and([$match['evidence']['day_stem'], $match['evidence']['time_branch'], $match['evidence']['initial']])->toBe([7, 5, 6]);
});

test('pohua is lesson 48 after tianwang and both production cases execute through the frozen routes', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.pohua');

    expect(array_search('lesson.pohua', $codes, true))->toBe(array_search('lesson.tianwang', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['魄化课', '蛊', '䷑'])
        ->and($lesson['summary'])->toBe('白虎乘月神死神或死气，并临日、辰、行年或发用之一。')
        ->and($lesson['cases'])->toHaveCount(2)
        ->and($lesson['source_examples'][1]['detail'])->toContain('未发用')->toContain('不克戌土')->toContain('研究参考');

    foreach ($lesson['cases'] as $index => $case) {
        expect($case['status'])->toBe('executable');
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.pohua');
        expect($match)->not->toBeNull()->and($match['evidence']['tiger_type'])->toBe('death_spirit');
        expect($match['evidence']['matched_routes'])->toBe($index === 0 ? ['day', 'initial'] : ['branch']);
    }
});

test('sanyin is lesson 49 with executable modern case and reference-only classic case', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.sanyin');
    [$modern, $classic] = $lesson['cases'];

    expect(array_search('lesson.sanyin', $codes, true))->toBe(array_search('lesson.pohua', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['三阴课', '中孚', '䷼'])
        ->and($lesson['summary'])->toBe('贵人逆行，日干寄宫与日支均乘贵后六将，初传囚死且乘玄武或白虎，占时支又克占人行年。')
        ->and($modern['status'])->toBe('executable')->and($modern['datetime'])->toBe('2025-12-10T09:00')
        ->and($modern['birth'])->toBe('1959-08-01T00:00')->and($modern['gender'])->toBe('male')
        ->and($classic['status'])->toBe('reference_only')->and($classic['datetime'])->toBe('2025-02-13T05:00')
        ->and($classic['reason'])->toContain('古例卯时取昼贵')->toContain('北京实际日出/日落')
        ->and($lesson['summary'])->not->toContain('大旺克初')->not->toContain('发用传终');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $modern['datetime'])->set('birthDatetime', $modern['birth'])->set('gender', $modern['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.sanyin');
    expect($match)->not->toBeNull()
        ->and($match['evidence']['initial_transmission'])->toMatchArray(['branch' => 10, 'seasonal_state' => '囚', 'general' => 7])
        ->and($match['evidence']['xingnian']['branch'])->toBe(8);

    $this->get(route('kejing'))->assertOk()
        ->assertSee('三阴课')->assertSee('原文参考盘·尚未覆盖')->assertSee('古例卯时取昼贵');
});

test('clicking the sanyin reference-only URL shows lesson-specific reason and preserves only the day-night nobleman difference', function () {
    $case = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanyin')['cases'][1];
    $component = Livewire::withQueryParams([
        'datetime' => $case['datetime'], 'birth' => $case['birth'], 'gender' => $case['gender'],
        'reference_case' => $case['case_id'],
    ])->test(CreatePan::class)->call('calculate')->assertHasNoErrors();

    $pan = $component->get('pan');
    expect([$pan['rigan'], $pan['rizhi'], $pan['yuejiang'], $pan['shizhi']])->toBe([9, 1, 0, 3]);
    $component->assertSee('原文参考盘·尚未覆盖（三阴课）')
        ->assertSee('古例卯时取昼贵')
        ->assertSee('北京实际日出/日落')
        ->assertDontSee('繁昌课·本命五行口径未定');
});

test('longzhan is lesson 50 and its daquan ding-mao case is executable', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.longzhan');
    $case = $lesson['cases'][0];

    expect(array_search('lesson.longzhan', $codes, true))->toBe(array_search('lesson.sanyin', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['龙战课', '离', '䷝'])
        ->and($lesson['summary'])->toBe('卯日卯发用且行年立卯，或酉日酉发用且行年立酉。')
        ->and($case)->toMatchArray([
            'case_id' => 'lesson.longzhan.ding_mao_chen_time_xu_general',
            'datetime' => '2027-04-18T08:00', 'birth' => '2002-06-01T12:00',
            'gender' => 'male', 'status' => 'executable',
        ]);

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors()
        ->assertSee('龙战课')->assertSee('三者同位于卯，龙战课成立。');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.longzhan');
    expect($match['evidence'])->toMatchArray([
        'day_branch' => 3, 'initial' => 3, 'querent_xingnian' => 3, 'matched_branch' => 3,
    ])->and(collect($match['evidence']['uncovered'])->implode(' '))->toContain('未程序化');
});

test('yangjiu is lesson 53 and five classic structures are executable', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $lesson = $lessons->firstWhere('code', 'lesson.yangjiu');
    expect([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['殃咎课', '解', '䷧'])
        ->and($lesson['summary'])->toBe('递克、夹克、三传内外战、干支乘墓或坐墓之一成立。')
        ->and($lesson['cases'])->toHaveCount(7);

    $expectedRoutes = [
        'lesson.yangjiu.ji_si_forward' => 'forward_recursive_overcoming',
        'lesson.yangjiu.bing_zi_reverse' => 'reverse_recursive_overcoming',
        'lesson.yangjiu.ren_zi_sandwiched' => 'initial_transmission_sandwiched_overcoming',
        'lesson.yangjiu.bing_yin_riding_tombs' => 'stem_branch_riding_tombs',
        'lesson.yangjiu.ren_shen_sitting_tombs' => 'stem_branch_sitting_on_tombs',
    ];
    foreach (array_slice($lesson['cases'], 0, 5) as $case) {
        expect($case['status'])->toBe('executable');
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.yangjiu');
        expect($match)->not->toBeNull()
            ->and($match['evidence']['matched_routes'])->toContain($expectedRoutes[$case['case_id']]);
    }
});

test('jiuchou is lesson 54 and its non strict executable case really matches', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.jiuchou');
    expect(array_search('lesson.jiuchou', $codes, true))->toBe(array_search('lesson.yangjiu', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['九丑课', '小过', '䷽'])
        ->and($lesson['summary'])->toContain('九丑十日')->toContain('丑加临日支')
        ->and($lesson['cases'])->toHaveCount(2);

    $case = $lesson['cases'][0];
    expect($case)->toMatchArray(['case_id' => 'lesson.jiuchou.ji_mao_non_strict_2026', 'status' => 'executable']);
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
        ->call('calculate')->assertHasNoErrors();
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.jiuchou');
    expect($match)->not->toBeNull()->and($match['evidence'])->toMatchArray([
        'day_ganzhi' => '己卯', 'chou_ground' => 3, 'day_branch' => 3,
        'four_zhong_time' => true, 'chou_fayong' => false, 'strict_daquan_form' => false,
    ]);

    $classic = $lesson['cases'][1];
    expect($classic)->toMatchArray([
        'case_id' => 'lesson.jiuchou.daquan_yi_mao_subject_reproduction',
        'datetime' => '2026-04-11T00:00',
        'status' => 'executable',
    ])->and($classic['reason'])->toContain('当前程序初传亥')->toContain('涉害取传专项研究');

    $referenceComponent = Livewire::test(CreatePan::class)
        ->set('datetime', $classic['datetime'])->set('birthDatetime', $classic['birth'])->set('gender', $classic['gender'])
        ->call('calculate')->assertHasNoErrors();
    $referenceMatch = collect($referenceComponent->get('ruleMatches'))->firstWhere('code', 'lesson.jiuchou');
    expect($referenceMatch)->not->toBeNull()->and($referenceMatch['evidence'])->toMatchArray([
        'day_ganzhi' => '乙卯', 'chou_ground' => 3, 'day_branch' => 3,
        'hour_branch' => 0, 'initial' => 11, 'chou_fayong' => false, 'strict_daquan_form' => false,
    ]);
});

test('guimu is lesson 55 and its three executable cases really match', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.guimu');
    expect(array_search('lesson.guimu', $codes, true))->toBe(array_search('lesson.jiuchou', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['鬼墓课', '困', '䷮'])
        ->and($lesson['summary'])->toContain('日鬼')->toContain('日干墓')->toContain('日支墓')
        ->and($lesson['cases'])->toHaveCount(3);

    $expectedRoutes = [
        'lesson.guimu.ghost_tomb_combined' => ['day_ghost', 'stem_tomb'],
        'lesson.guimu.ghost_branch_tomb' => ['day_ghost', 'branch_tomb'],
        'lesson.guimu.all_three' => ['day_ghost', 'stem_tomb', 'branch_tomb'],
    ];
    foreach ($lesson['cases'] as $case) {
        expect($case['status'])->toBe('executable');
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])->set('birthDatetime', $case['birth'])->set('gender', $case['gender'])
            ->call('calculate')->assertHasNoErrors();
        $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.guimu');
        expect($match)->not->toBeNull()
            ->and($match['evidence']['matched_routes'])->toBe($expectedRoutes[$case['case_id']]);
    }

    $this->get(route('kejing'))->assertOk()->assertSee('鬼墓课')->assertSee('困卦')->assertSee('䷮');
});

test('lide is lesson 56 and all three executable cases reproduce the frozen lesson and grid paths', function () {
    $lessons = collect(KeJingCatalog::lessons());
    $codes = $lessons->pluck('code')->all();
    $lesson = $lessons->firstWhere('code', 'lesson.lide');

    expect(array_search('lesson.lide', $codes, true))->toBe(array_search('lesson.guimu', $codes, true) + 1)
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['励德课', '随', '䷐'])
        ->and($lesson['summary'])->toContain('贵人临地盘卯或酉')->toContain('只用于分型')
        ->and($lesson['cases'])->toHaveCount(3)
        ->and(array_column($lesson['cases'], 'source_type'))->toBe(['daquan', 'daquan', 'daquan']);

    $expected = [
        'lesson.lide.wu_zi_shen_time_wu_general' => ['ground' => 3, 'pattern' => 'mixed', 'grid' => null],
        'lesson.lide.weifu_xin_chou' => ['ground' => 3, 'pattern' => 'weifu', 'grid' => 'structure.weifu'],
        'lesson.lide.cuotuo_geng_shen' => ['ground' => 9, 'pattern' => 'cuotuo', 'grid' => 'structure.cuotuo'],
    ];

    foreach ($lesson['cases'] as $case) {
        expect($case['status'])->toBe('executable');

        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])
            ->set('birthDatetime', $case['birth'])
            ->set('gender', $case['gender'])
            ->call('calculate')
            ->assertHasNoErrors();

        $matches = collect($component->get('ruleMatches'));
        $match = $matches->firstWhere('code', 'lesson.lide');
        $want = $expected[$case['case_id']];

        expect($match)->not->toBeNull()
            ->and($match['evidence']['nobleman_ground'])->toBe($want['ground'])
            ->and($match['evidence']['pattern'])->toBe($want['pattern']);

        if ($want['grid'] === null) {
            expect($matches->firstWhere('code', 'structure.weifu'))->toBeNull()
                ->and($matches->firstWhere('code', 'structure.cuotuo'))->toBeNull();
        } else {
            expect($matches->firstWhere('code', $want['grid']))->not->toBeNull();
        }
    }

    $this->get(route('kejing'))->assertOk()
        ->assertSee('励德课')->assertSee('随卦')->assertSee('䷐')
        ->assertSee('微服格')->assertSee('蹉跎格');
});

test('lide detail page always lists both yang-front-yin-rear and yin-front-yang-rear as static judgments', function () {
    $rules = app(RuleRegistry::class)->rules();
    $lide = null;
    foreach ($rules as $rule) {
        if ($rule->code() === 'lesson.lide') {
            $lide = $rule;
            break;
        }
    }

    $definition = $lide?->definition();

    expect($definition)->not->toBeNull();
    expect(array_column($definition['judgments'] ?? [], 'code'))
        ->toContain('yang_front_yin_rear', 'yin_front_yang_rear');

    foreach ($definition['judgments'] ?? [] as $judgment) {
        expect($judgment)->not->toHaveKey('effect');
    }

    $this->get(route('kejing.show', ['lesson' => 'lide']))
        ->assertOk()
        ->assertSee('阳前阴后')
        ->assertSee('阴前阳后')
        ->assertDontSee('>增强<', false);
});

test('yincong detail page always lists all six OR paths even when canonical case does not trigger them', function () {
    $rules = app(RuleRegistry::class)->rules();
    $yincong = null;
    foreach ($rules as $rule) {
        if ($rule->code() === 'lesson.yincong') {
            $yincong = $rule;
            break;
        }
    }

    $definition = $yincong?->definition();

    expect($definition)->not->toBeNull();

    $foundationCodes = array_column($definition['foundations'] ?? [], 'code');
    expect($foundationCodes)->toContain(
        'gang_tian_gan', 'gang_di_zhi', 'gui_lin_gan_zhi_gang_nian_ming',
        'gang_ri_lu', 'gang_ye_gui', 'gang_zhou_gui',
    );

    $this->get(route('kejing.show', ['lesson' => 'yincong']))
        ->assertOk()
        ->assertSee('拱天干')
        ->assertSee('拱地支')
        ->assertSee('贵临干支拱年命')
        ->assertSee('干支拱日禄')
        ->assertSee('干支拱夜贵')
        ->assertSee('干支拱昼贵');
});

test('xingshang program-verified case is classified as non-daquan and never appears in daquan section', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.xingshang');
    expect($lesson)->not->toBeNull();

    $case = $lesson['cases'][0];
    expect($case['label'])->toContain('程序验证样本')
        ->and($case['source_type'] ?? null)->toBe('other');

    $this->get(route('kejing.show', ['lesson' => 'xingshang']))
        ->assertOk()
        ->assertSee('刑伤课')
        ->assertSee('非正文课例与旁证');
});

test('sanguang detail and pan views expose foundations with the same code keys', function () {
    $rules = app(RuleRegistry::class)->rules();
    $sanguang = null;
    foreach ($rules as $rule) {
        if ($rule->code() === 'lesson.sanguang') {
            $sanguang = $rule;
            break;
        }
    }

    $definition = $sanguang?->definition();

    expect($definition)->not->toBeNull();

    $foundationCodes = array_column($definition['foundations'] ?? [], 'code');
    $expected = [
        'day_stem_wang_xiang', 'day_branch_wang_xiang', 'initial_wang_xiang',
        'day_upper_auspicious_general', 'branch_upper_auspicious_general', 'initial_auspicious_general',
    ];

    foreach ($expected as $code) {
        expect($foundationCodes)->toContain($code);
    }

    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee('日干得旺相')
        ->assertSee('日支得旺相')
        ->assertSee('初传得旺相')
        ->assertSee('日上神乘吉将')
        ->assertSee('辰上神乘吉将')
        ->assertSee('初传乘吉将');
});

test('jieli detail page preserves the empty gua slot without collapse', function () {
    $jieli = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.jieli');

    expect($jieli['gua'])->toBeNull()
        ->and($jieli['guaSymbol'])->toBeNull();

    $response = $this->get(route('kejing.show', ['lesson' => 'jieli']))
        ->assertOk()
        ->assertSee('解离课');

    expect($response->getContent())->toContain('data-kejing-gua-slot="empty"');
});

test('liuchun catalog exposes two daquan cases plus two modern executable production examples', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.liuchun');

    expect($lesson)->not->toBeNull()
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['六纯课', '革', '䷰'])
        ->and($lesson['cases'])->toHaveCount(4)
        ->and(array_column($lesson['cases'], 'case_id'))->toBe([
            'lesson.liuchun.production_liuyang_20310104_0500',
            'lesson.liuchun.production_liuyin_20310103_0500',
            'lesson.liuchun.daquan_jia_wu_gan_shang_zi',
            'lesson.liuchun.daquan_ji_mao_you_jia_wei',
        ])
        ->and(array_column($lesson['cases'], 'datetime'))->toBe([
            '2031-01-04T05:00',
            '2031-01-03T05:00',
            '2024-01-31T03:00',
            '2024-01-16T21:00',
        ])
        ->and(array_column($lesson['cases'], 'status'))->toBe([
            'executable', 'executable', 'reference_only', 'executable',
        ])
        ->and(array_column($lesson['cases'], 'source_type'))->toBe([
            'other', 'other', 'daquan', 'daquan',
        ])
        ->and($lesson['source_examples'])->toHaveCount(4)
        ->and(array_unique(array_column($lesson['source_examples'], 'source')))->toBe(['《六壬大全》正文']);

    foreach ($lesson['cases'] as $case) {
        if (($case['status'] ?? 'executable') !== 'executable') {
            continue;
        }
        $component = Livewire::test(CreatePan::class)
            ->set('datetime', $case['datetime'])
            ->set('birthDatetime', $case['birth'])
            ->set('gender', $case['gender'])
            ->call('calculate')
            ->assertHasNoErrors();

        expect(collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.liuchun'))->not->toBeNull();
    }
});

test('liuchun detail page shows definition sources complete original and uncovered boundaries', function () {
    $this->get(route('kejing.show', ['lesson' => 'liuchun']))
        ->assertOk()
        ->assertSee('第 62 课')
        ->assertSee('六纯课')->assertSee('革卦')->assertSee('䷰')
        ->assertSee('四课上神皆阳（或皆阴）')
        ->assertSee('六阳／六阴两条独立入口')
        ->assertSee('六阳动达，如登三天')
        ->assertSee('《六壬大全》正文课例')
        ->assertSee('甲午日·干上子·退间传 戌申午')
        ->assertSee('己卯日·酉加未·三传亥丑卯')
        ->assertSee('2031-01-04·卯时')
        ->assertSee('2031-01-03·卯时')
        ->assertSee('《六壬大全》完整原文')
        ->assertSee('尚未程序化或尚待冻结的课义')
        ->assertSee('五阳、五阴及年命填实暂未程序化');
});

test('liuchun daquan ji-mao case is executable and reproduces liuyin via registry engine', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.liuchun');
    $case = collect($lesson['cases'])->firstWhere('case_id', 'lesson.liuchun.daquan_ji_mao_you_jia_wei');

    expect($case)->not->toBeNull()
        ->and($case['status'])->toBe('executable')
        ->and($case['source_type'])->toBe('daquan')
        ->and($case['datetime'])->toBe('2024-01-16T21:00');

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.liuchun');

    // 己卯日，tianpan[未]=酉，四课上神 酉亥巳未，三传 亥丑卯，六阴命中。
    expect([$pan['rigan'], $pan['rizhi']])->toBe([5, 3])
        ->and($pan['tianpan'][7])->toBe(9)
        ->and([$pan['sike'][1], $pan['sike'][3], $pan['sike'][5], $pan['sike'][7]])->toBe([9, 11, 5, 7])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([11, 1, 3])
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['type'])->toBe('liuyin');

    $component->assertSee('六纯课')
        ->assertSee('六阴课')
        ->assertSee('查看六纯课详解')
        ->assertDontSee('原文参考盘·尚未覆盖');
});

test('liuchun daquan jia-wu reference-only case shows the not-covered badge and is whitelisted', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.liuchun');
    $case = collect($lesson['cases'])->firstWhere('case_id', 'lesson.liuchun.daquan_jia_wu_gan_shang_zi');

    expect($case)->not->toBeNull()
        ->and($case['status'])->toBe('reference_only')
        ->and($case['source_type'])->toBe('daquan')
        ->and($case['datetime'])->toBe('2024-01-31T03:00');

    expect(KeJingCatalog::findReferenceCase('lesson.liuchun.daquan_jia_wu_gan_shang_zi'))
        ->not->toBeNull();

    $response = $this->get(route('kejing.show', ['lesson' => 'liuchun']));
    $response->assertSee('原文参考盘·尚未覆盖');
    $response->assertSee('甲午日·干上子·退间传 戌申午');

    // reference_only 案例进入排盘页必须经白名单校验通过后才能显示"原文参考盘"标识；
    // 这里使用伪造 ID 时应被 KeJingCatalog::findReferenceCase 拒绝。
    expect(KeJingCatalog::findReferenceCase('lesson.liuchun.daquan_jia_wu_gan_shang_zi_forged'))
        ->toBeNull();
});

test('liuchun daquan jia-wu case never claims liuyang on the pan page', function () {
    // 甲午正文例 reference_only：当前时间点（2024-01-31 03:00）实际盘面是六阳寅子戌，与正文退间传 戌申午 不一致；
    // 即便展示，也不应作为六纯命中案例隐藏此冲突。
    $component = Livewire::withQueryParams([
        'datetime' => '2024-01-31T03:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
        'reference_case' => 'lesson.liuchun.daquan_jia_wu_gan_shang_zi',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $pan = $component->get('pan');
    $match = collect($component->get('ruleMatches'))->firstWhere('code', 'lesson.liuchun');

    expect([$pan['rigan'], $pan['rizhi']])->toBe([0, 6])
        ->and($pan['sike'][1])->toBe(0)
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([2, 0, 10])
        ->and($match)->not->toBeNull()
        ->and($match['evidence']['type'])->toBe('liuyang');

    // reference_only 提示必须出现；且当前盘面三传 ≠ 戌申午，必须显示这一冲突。
    $component->assertSee('原文参考盘·尚未覆盖');
    $component->assertSee('原文参考盘');
});

test('liuchun daqan ji-mao executable case reproduces the lesson on the pan page', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.liuchun');
    $case = collect($lesson['cases'])->firstWhere('case_id', 'lesson.liuchun.daquan_ji_mao_you_jia_wei');

    $component = Livewire::withQueryParams([
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $component->assertSee('六纯课')
        ->assertSee('六阴课')
        ->assertSee('查看六纯课详解')
        ->assertDontSee('原文参考盘·尚未覆盖');
});
