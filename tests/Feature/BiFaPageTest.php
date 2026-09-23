<?php

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Livewire\Pan\CreatePan;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use Livewire\Livewire;

/**
 * 直接读取独立的 canonical fixture 文件（tests/Fixtures/bifa_canonical_100_laws.json），
 * 不从 BiFaCatalog 取数据；该 fixture 是《六壬大全·毕法赋》百法目录的唯一权威来源。
 *
 * 修改 BiFaCatalog 时必须同步修改该 fixture；测试断言会拒绝 BiFaCatalog 与 fixture 不一致。
 *
 * 用静态缓存避免每个测试都重新读盘；Laravel app context 在测试体内部已建立。
 *
 * @return array<int, string> number => name
 */
function bifa_canonical_laws(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $path = base_path('tests/Fixtures/bifa_canonical_100_laws.json');
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $cache = [];
    foreach ($data['laws'] as $row) {
        $cache[(int) $row['number']] = (string) $row['name'];
    }

    return $cache;
}

test('canonical 100-law fixture itself is well-formed (100 laws, sequential numbers)', function () {
    $expectedLaws = bifa_canonical_laws();

    // 直接断言 fixture 自身：100 条、number 连续 1..100——保证 fixture 不被悄悄改成残表。
    expect($expectedLaws)->toHaveCount(100)
        ->and(array_keys($expectedLaws))->toEqual(range(1, 100));

    // 用户特别要求核验的几个关键法号必须出现在 fixture 中（覆盖 name 数量）。
    foreach ([1, 2, 3, 8, 9, 10, 50, 100] as $keyNumber) {
        expect($expectedLaws)->toHaveKey($keyNumber);
        expect($expectedLaws[$keyNumber])->not->toBeEmpty();
    }
});

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

test('BiFaCatalog names match the canonical 100-law table', function () {
    $expectedLaws = bifa_canonical_laws();

    foreach ($expectedLaws as $number => $name) {
        $law = BiFaCatalog::findByNumber($number);
        expect($law)->not->toBeNull("法号 {$number} 应存在")
            ->and($law['name'])->toBe($name, "法号 {$number} 的名称应与 canonical 表一致")
            ->and($law['code'])->toBe(sprintf('bifa.%02d', $number));
    }
});

test('BiFaCatalog equals the canonical fixture (no extra / no missing / name exact match)', function () {
    $expectedLaws = bifa_canonical_laws();
    $laws = BiFaCatalog::laws();
    $catalogMap = [];
    foreach ($laws as $law) {
        $catalogMap[(int) $law['number']] = (string) $law['name'];
    }

    // 双侧集合必须完全一致——既能防止 BiFaCatalog 漏法，也能防止 BiFaCatalog 自作主张增加额外条目。
    expect($catalogMap)->toEqual($expectedLaws);
});

test('BiFaCatalog findByCode finds every registered law', function () {
    $expectedLaws = bifa_canonical_laws();

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
    $response->assertSee('引从天干');
    $response->assertSee('二贵拱年命');
    $response->assertSee('干支并初中拱地盘贵人');

    foreach (['yin_gan', 'er_gui_gang_nianming', 'gan_zhi_bing_chu_zhong_gui',
        'BiFaRuleEngine', 'QianHouYinCongRule', 'match()', 'flanks()', 'case_id',
        'matched_routes', 'pending_routes', 'reference_only', 'executable', 'PanCalculator'] as $internalName) {
        $response->assertDontSee($internalName, false);
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
    $component->assertSee('引从天干');
    $component->assertSee('拱贵格');
    foreach (['yin_gan', 'gong_gui', 'BiFaRuleEngine', 'QianHouYinCongRule',
        'matched_routes', 'pending_routes', 'case_id', 'reference_only', 'executable'] as $internalName) {
        $component->assertDontSee($internalName, false);
    }

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
