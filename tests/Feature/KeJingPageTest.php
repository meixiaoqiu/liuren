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
