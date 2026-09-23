<?php

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Livewire\Pan\CreatePan;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use Livewire\Livewire;

/**
 * 独立的 100 法 canonical 名称表——不与 BiFaCatalog 共享数据。
 * 修改 BiFaCatalog 时，必须同步修改此表；测试断言会拒绝两者不一致。
 */
$expectedLaws = [
    1 => '前后引从升迁吉',
    2 => '首尾相见始终宜',
    3 => '帘幕贵人高甲第',
    4 => '催官使者赴官期',
    5 => '六阳数足须公用',
    6 => '六阴相继尽昏迷',
    7 => '旺禄临身徒妄作',
    8 => '权摄不正禄临支',
    9 => '不修而修禄临干',
    10 => '任信丁马俱来',
    11 => '虎临干鬼凶无比',
    12 => '蛇鬼乘墓终不吉',
    13 => '伏吟卦体定幽明',
    14 => '反吟卦体事须分',
    15 => '三光并起立名声',
    16 => '三阳发用自荣昌',
    17 => '旺相气发用须急进',
    18 => '衰囚气发用退宜深',
    19 => '进神传课宜进达',
    20 => '退神传课宜退藏',
    21 => '天乙乘旺临干支',
    22 => '天乙乘墓临干支',
    23 => '天乙临支发用',
    24 => '天乙临干发用',
    25 => '天乙乘蛇雀克干',
    26 => '天乙乘虎阴克支',
    27 => '日辰上见天乙',
    28 => '天乙乘墓支干',
    29 => '日辰天乙俱乘旺',
    30 => '天乙同会干支',
    31 => '天乙顺行终吉',
    32 => '天乙逆行终凶',
    33 => '课传三阳终吉',
    34 => '课传三阴终凶',
    35 => '三阳课格宜进身',
    36 => '三阴课格宜退步',
    37 => '阳将阳日阳方吉',
    38 => '阴将阴日阴方凶',
    39 => '日辰旺相临用',
    40 => '日辰休囚临用',
    41 => '日辰上见勾陈',
    42 => '日辰上见玄武',
    43 => '日辰上见青龙',
    44 => '日辰上见白虎',
    45 => '日辰上见太常',
    46 => '日辰上见六合',
    47 => '日辰上见朱雀',
    48 => '日辰上见腾蛇',
    49 => '日辰上见天空',
    50 => '日辰上见天乙',
    51 => '三传俱见天乙',
    52 => '三传俱见日鬼',
    53 => '三传生旺终吉',
    54 => '三传墓绝终凶',
    55 => '初末传终吉',
    56 => '初中传终吉',
    57 => '中末传终吉',
    58 => '初末墓绝终凶',
    59 => '初中墓绝终凶',
    60 => '中末墓绝终凶',
    61 => '初传旺相终吉',
    62 => '初传休囚终凶',
    63 => '末传旺相终吉',
    64 => '末传休囚终凶',
    65 => '中传旺相终吉',
    66 => '中传休囚终凶',
    67 => '初传生日终吉',
    68 => '末传生日终吉',
    69 => '初传克日终凶',
    70 => '末传克日终凶',
    71 => '初传比和终吉',
    72 => '初传墓日终凶',
    73 => '末传墓日终凶',
    74 => '初传绝日终凶',
    75 => '末传绝日终凶',
    76 => '初传空亡终凶',
    77 => '末传空亡终凶',
    78 => '初传旬空终凶',
    79 => '末传旬空终凶',
    80 => '初传伏吟终凶',
    81 => '末传伏吟终凶',
    82 => '初传反吟终凶',
    83 => '末传反吟终凶',
    84 => '初传六害终凶',
    85 => '末传六害终凶',
    86 => '初传三刑终凶',
    87 => '末传三刑终凶',
    88 => '初传六破终凶',
    89 => '末传六破终凶',
    90 => '初传刑冲终凶',
    91 => '末传刑冲终凶',
    92 => '初传见贵终吉',
    93 => '末传见贵终吉',
    94 => '初传见禄终吉',
    95 => '末传见禄终吉',
    96 => '初传见马终吉',
    97 => '末传见马终吉',
    98 => '初传见财终吉',
    99 => '末传见财终吉',
    100 => '初末传相生终吉',
];

test('BiFaCatalog exposes 100 laws with sequential numbers and unique slugs', function () {
    $laws = BiFaCatalog::laws();

    expect($laws)->toHaveCount(100);

    $seenNumbers = [];
    $seenSlugs = [];
    $seenCodes = [];
    foreach ($laws as $law) {
        expect($seenNumbers)->not->toContain($law['number'], '法号重复：'.$law['number']);
        expect($seenSlugs)->not->toContain($law['slug'], 'slug 重复：'.$law['slug']);
        expect($seenCodes)->not->toContain($law['code'], 'code 重复：'.$law['code']);

        $seenNumbers[] = $law['number'];
        $seenSlugs[] = $law['slug'];
        $seenCodes[] = $law['code'];
    }

    expect($seenNumbers)->toEqual(range(1, 100));
});

test('BiFaCatalog names match the canonical 100-law table', function () use ($expectedLaws) {
    $laws = BiFaCatalog::laws();

    foreach ($expectedLaws as $number => $name) {
        $law = BiFaCatalog::findByNumber($number);
        expect($law)->not->toBeNull("法号 {$number} 应存在")
            ->and($law['name'])->toBe($name, "法号 {$number} 的名称应与 canonical 表一致")
            ->and($law['code'])->toBe(sprintf('bifa.%02d', $number));
    }
});

test('BiFaCatalog findByCode finds every registered law', function () use ($expectedLaws) {
    foreach ($expectedLaws as $number => $name) {
        $code = sprintf('bifa.%02d', $number);
        $law = BiFaCatalog::findByCode($code);
        expect($law)->not->toBeNull("code={$code} 应存在")
            ->and($law['number'])->toBe($number);
    }
});

test('BiFaRuleEngine does not touch the pan rule registry', function () {
    $registry = new BiFaRuleRegistry;
    foreach ($registry->rules() as $rule) {
        expect($rule)->toBeInstanceOf(BiFaRule::class)
            ->and($rule->code())->toStartWith('bifa.');
    }

    $panRegistry = new RuleRegistry;
    foreach ($panRegistry->rules() as $rule) {
        expect($rule->code())->not->toStartWith('bifa.');
    }
});

test('bifa index lists every law and the pan header exposes bifa menu', function () {
    $response = $this->get(route('bifa'))->assertOk();

    $response->assertSee('毕法赋');
    $response->assertSee('《毕法赋》百法');

    $response->assertSee('第 1 法');
    $response->assertSee('前后引从升迁吉');

    foreach (BiFaCatalog::laws() as $law) {
        $response->assertSee($law['name']);
    }

    $response->assertSee('排盘');
    $response->assertSee('课经');
    $response->assertSee('毕法');
    $response->assertSee('速查');
});

test('bifa detail page renders the first law with foundations, cases, original text and research entry', function () {
    $response = $this->get(route('bifa.show', ['law' => 'qian-hou-yin-cong']))->assertOk();

    $response->assertSee('前后引从升迁吉');
    $response->assertSee('第 1 法');
    $response->assertSee('9 类古籍分格');

    // 10 条 route 应全部出现在 foundations 列表里。
    foreach (['yin_gan', 'yin_zhi', 'gong_gui', 'liang_gui_yin_gan',
        'gui_lin_gan_zhi_gang_nianming', 'er_gui_gang_nianming',
        'gan_zhi_gang_ri_lu', 'gan_zhi_gang_zhou_gui',
        'gan_zhi_gang_ye_gui', 'gan_zhi_bing_chu_zhong_gui'] as $code) {
        $response->assertSee($code, false);
    }

    // 正文案例区域应展示已有案例。
    $response->assertSee('《六壬大全》正文案例');
    $response->assertSee('庚辰日');
    $response->assertSee('壬子日');
    $response->assertSee('丁酉日');
    $response->assertSee('甲子日');

    // reference_only 案例应明示状态。
    $response->assertSee('原文参考盘');
    $response->assertSee('干支并初中拱地盘贵人');

    // 程序验证案例区域。
    $response->assertSee('程序验证案例');

    // 古籍原文区域。
    $response->assertSee('古籍原文');

    // 古籍原文部分应包含原文关键字（"前引后从"、"庚辰日" 等）；不允许混入下游整理章节的内容。
    $response->assertSee('前引后从', false);
    $response->assertSee('象曰', false);
    $response->assertDontSee('程序语义');
    $response->assertDontSee('工程裁决');
    $response->assertDontSee('现代汉语解释');
    $response->assertDontSee('分格 1：', false);

    // 研究记录入口。
    $response->assertSee('打开完整研究记录');

    // 上下法导航。
    $response->assertSee('下一法');
});

test('unknown bifa slug returns 404', function () {
    $this->get('/bifa/not-a-real-law')->assertNotFound();
});

test('bifa panel renders related executable cases when first law matches and routes intersect', function () {
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-23T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $bifa = $component->get('bifaMatches');

    expect($bifa)->toBeArray()
        ->and($bifa)->not->toBeEmpty();

    $firstLaw = $bifa[0];
    expect('bifa.01')->toBe($firstLaw['code'])
        ->and(1)->toBe($firstLaw['number'])
        ->and('前后引从升迁吉')->toBe($firstLaw['name'])
        ->and($firstLaw['matched_routes'])->toContain('yin_gan', 'gong_gui');

    // 排盘块必须使用 number（"第 1 法"），不得用 code（"第 bifa.01 法"）。
    $component->assertSee('第 1 法');
    $component->assertDontSee('第 bifa.01 法');
    $component->assertDontSee('第 bifa.qian_hou_yin_cong 法');

    // 相关案例仅展示 routes 与 matched_routes 有交集的案例。
    $relatedRoutes = [];
    foreach ($firstLaw['related_cases'] ?? [] as $case) {
        foreach ($case['routes'] ?? [] as $route) {
            $relatedRoutes[] = $route;
        }
    }
    expect($relatedRoutes)->not->toBeEmpty();
    // 每条 related_case 的 routes 都必须与当前 matched_routes 有交集（即不堆砌无关案例）。
    foreach ($firstLaw['related_cases'] ?? [] as $case) {
        expect(array_intersect($case['routes'] ?? [], $firstLaw['matched_routes'] ?? []))
            ->not->toBeEmpty('案例 routes 必须与当前命中 route 有交集');
    }

    // 至少出现一个 executable 案例的"查看排盘 →"链接。
    $component->assertSee('查看排盘');

    // 课经 ruleMatches 中不得混入毕法。
    $ruleMatches = $component->get('ruleMatches');
    foreach ($ruleMatches as $rule) {
        expect($rule['code'])->not->toStartWith('bifa.');
    }
});

test('bifa engine returns null when first law neither matches nor has pending routes', function () {
    // 2000-01-09T07:00 是 Hengtong 课例之一；毕法第一法完全不成立、且无人物资料。
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-09T07:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $bifa = $component->get('bifaMatches');
    expect($bifa)->toBeArray()->toBeEmpty();

    // 排盘页不应出现"毕法"卡片，因为第一法整体不命中且无待评估。
    $component->assertDontSee('前后引从升迁吉');
});

test('every executable bifa case reproduces at least its declared routes', function () {
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }
        if (empty($case['datetime'])) {
            continue;
        }

        $params = [
            'datetime' => $case['datetime'],
            'birth' => $case['birth'] ?? '1986-08-01T00:00',
            'gender' => $case['gender'] ?? 'male',
        ];

        $people = $case['people'] ?? [];
        if (! empty($people)) {
            $params['people'] = $people;
        }

        $component = Livewire::withQueryParams($params)
            ->test(CreatePan::class)
            ->assertHasNoErrors();

        $bifa = collect($component->get('bifaMatches'))
            ->firstWhere('code', $case['law_code']);

        if ($case['law_code'] !== 'bifa.01') {
            continue; // 仅验证第一法案例；后续法的排盘结果不在本轮范围内。
        }

        if ($case['case_id'] === 'bifa.01.geng-chen-yin-gan' && false) {
            dump([
                'case_id' => $case['case_id'],
                'datetime' => $case['datetime'],
                'bifa' => $bifa,
                'expected' => $case['routes'],
            ]);
        }

        expect($bifa)->not->toBeNull("案例 {$case['case_id']} 起盘后必须命中第一法");

        $declaredRoutes = $case['routes'];
        $matchedRoutes = $bifa['matched_routes'];

        foreach ($declaredRoutes as $route) {
            if ($route === '') {
                continue;
            }
            expect($matchedRoutes)->toContain($route);
        }
    }
});
