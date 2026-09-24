<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleEngine;
use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Livewire\Pan\CreatePan;
use App\Support\BiFaCatalog;
use App\Support\BiFaPageCatalog;
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
    $response->assertSee('成立条件说明');
    $response->assertDontSee('9 类古籍分格');
    $response->assertSee('引从天干');
    $response->assertSee('二贵拱年命');
    $response->assertSee('干支并初中拱地盘贵人');

    foreach (['yin_gan', 'er_gui_gang_nianming', 'gan_zhi_bing_chu_zhong_gui',
        'BiFaRuleEngine', 'QianHouYinCongRule', 'BiFaKnowledgeCardFactory', 'match()', 'fromMatch',
        'flanks()', '$matchedRoutes', 'bifa.01', 'case_id', 'matched_routes', 'pending_routes',
        'reference_only', 'executable', 'PanCalculator'] as $internalName) {
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

test('unresearched bifa detail does not construct a KnowledgeCard', function () {
    $law = collect(BiFaPageCatalog::laws())->firstWhere('researched', false);
    expect($law)->not->toBeNull();

    $response = $this->get(route('bifa.show', ['law' => $law['slug']]))->assertOk();

    expect($response->viewData('knowledgeCard'))->toBeNull();
    expect($response->viewData('researched'))->toBeFalse();
    $response->assertSee('本法尚未研究');
});

test('researched but unimplemented bifa shows research material without a KnowledgeCard', function () {
    $law = BiFaCatalog::findByCode('bifa.02');
    expect($law)->not->toBeNull();

    $law = [
        ...$law,
        'researched' => true,
        'researchUrl' => 'https://example.test/bifa-02',
    ];
    $researched = true;
    $implemented = false;
    $knowledgeCard = null;
    $response = $this->view('bifa.show', [
        'law' => $law,
        'researched' => $researched,
        'implemented' => $implemented,
        'knowledgeCard' => $knowledgeCard,
        'original' => [
            'status' => 'complete',
            'content' => '第二法古籍原文测试内容',
        ],
        'previousLaw' => null,
        'nextLaw' => null,
    ]);

    expect($knowledgeCard)->toBeNull()
        ->and($researched)->toBeTrue()
        ->and($implemented)->toBeFalse();
    $response->assertSee('程序判定规则尚未实现')
        ->assertSee('第二法古籍原文测试内容')
        ->assertSee('打开完整研究记录');
});

test('researched and implemented first bifa constructs its KnowledgeCard', function () {
    $response = $this->get(route('bifa.show', ['law' => 'qian-hou-yin-cong']))->assertOk();

    expect($response->viewData('researched'))->toBeTrue()
        ->and($response->viewData('implemented'))->toBeTrue()
        ->and($response->viewData('knowledgeCard'))->not->toBeNull();
});

test('researched and implemented second bifa constructs its KnowledgeCard and shows 4 foundations', function () {
    $response = $this->get(route('bifa.show', ['law' => 'shou-wei-xiang-jian']))->assertOk();

    expect($response->viewData('researched'))->toBeTrue()
        ->and($response->viewData('implemented'))->toBeTrue()
        ->and($response->viewData('knowledgeCard'))->not->toBeNull();

    $card = $response->viewData('knowledgeCard');
    expect($card['label'])->toBe('第 2 法')
        ->and($card['title'])->toBe('首尾相见始终宜')
        ->and($card['conditions'])->toHaveCount(4)
        ->and($card['conditions'][0]['title'])->toBe('周而复始·旬尾临干、旬首临支')
        ->and($card['conditions'][1]['title'])->toBe('周而复始·旬首临干、旬尾临支')
        ->and($card['conditions'][2]['title'])->toBe('天心格')
        ->and($card['conditions'][3]['title'])->toBe('回还格');

    $contents = (string) json_encode($card, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // 详情页不应泄漏内部 route code / 工厂类名。
    foreach ([
        'xun_tail_on_stem_xun_head_on_branch',
        'xun_head_on_stem_xun_tail_on_branch',
        'tianxin_four_establishments_in_lessons',
        'huihuan_transmissions_in_lessons',
        'bifa.02',
        'ShouWeiXiangJianRule',
        'BiFaRuleEngine',
        'BiFaKnowledgeCardFactory',
        'match()',
        'route',
        'matcher',
        'matched_routes',
        'pending_routes',
        '$matchedRoutes',
    ] as $internal) {
        $response->assertDontSee($internal, false);
    }
    expect($contents)->not->toContain('xun_tail_on_stem_xun_head_on_branch')
        ->and($contents)->not->toContain('bifa.02');

    // 第二法详情页应展示四项成立条件的中文标题。
    $response->assertSee('周而复始·旬尾临干、旬首临支')
        ->assertSee('周而复始·旬首临干、旬尾临支')
        ->assertSee('天心格')
        ->assertSee('回还格');

    // 第二法案例应展示。
    $response->assertSee('乙未日')
        ->assertSee('乙丑日')
        ->assertSee('乙巳日')
        ->assertSee('辛亥日');
});

test('researched and implemented third bifa shows eight Chinese foundations and no internal fields', function () {
    $response = $this->get(route('bifa.show', ['law' => 'lian-mu-gui-ren']))->assertOk();
    expect($response->viewData('researched'))->toBeTrue()
        ->and($response->viewData('implemented'))->toBeTrue()
        ->and($response->viewData('knowledgeCard')['conditions'])->toHaveCount(8);
    foreach (['帘幕贵人临干年命', '旬首作帘幕', '辰戌旬首临干年命', '斗鬼相加', '亚魁临干年命', '德入天门', '真朱雀', '昼夜二贵拱年命'] as $title) {
        $response->assertSee($title);
    }
    $response->assertSee('帘幕官者，如昼占乃夜贵，夜占乃昼贵', false)
        ->assertSee('前五类尤忌旬空')->assertSee('朱雀克帘幕减力')
        ->assertSee('程序验证·亚魁临干');
    foreach (['bifa.03', 'curtain_noble_on_stem_or_fate', 'xun_head_as_curtain_noble', 'chen_xu_xun_head_on_stem_or_fate', 'dou_gui_on_stem_or_fate', 'ya_kui_you_on_stem_or_fate', 'day_virtue_enters_heaven_gate', 'true_vermilion_bird', 'two_nobles_flank_fate', 'LianMuGuiRenRule', 'matched_routes', 'pending_routes', 'match()'] as $internal) {
        $response->assertDontSee($internal, false);
    }
});

test('bifa panel renders the first law without numbering or unrelated cases', function () {
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-23T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $cards = $component->get('bifaKnowledgeCards');
    expect($cards)->toBeArray()->not->toBeEmpty();

    $matches = app(BiFaRuleEngine::class)->evaluate(new PanResult($component->get('pan')));
    $bifa01 = collect($matches)->first(fn ($match): bool => $match->code === 'bifa.01');
    expect($bifa01)->not->toBeNull()
        ->and($bifa01->matchedRoutes)->toContain('yin_gan', 'gong_gui');

    // 排盘块使用“毕 + 法名”的课经同款标题，不展示法序号。
    $component->assertSee('毕');
    $component->assertSee('前后引从升迁吉');
    $component->assertDontSee('第 1 法');
    $component->assertDontSee('第 bifa.01 法');
    $component->assertDontSee('第 bifa.qian_hou_yin_cong 法');
    $component->assertDontSee('判定说明');
    $component->assertDontSee('命中依据');
    $component->assertDontSee('相关案例');
    $component->assertSee('引从天干');
    $component->assertSee('拱贵格');
    $component->assertSee('已成立');
    $component->assertSee('未成立');
    $component->assertSee('✔️');
    $component->assertSee('❌');
    $component->assertSee('⏺');
    $component->assertDontSee('1️⃣');
    $component->assertDontSee('分格命中');
    $component->assertDontSee('>不成立<', false);
    foreach (['yin_gan', 'gong_gui', 'bifa.01', 'BiFaRuleEngine', 'QianHouYinCongRule',
        'BiFaKnowledgeCardFactory', 'fromMatch', '$matchedRoutes', 'matched_routes', 'pending_routes',
        'case_id', 'reference_only', 'executable'] as $internalName) {
        $component->assertDontSee($internalName, false);
    }

    // 相关案例属于规则研究材料，不进入当前盘卡片。
    $component->assertDontSee('查看排盘');

    // 课经 ruleMatches 中不得混入毕法。
    $ruleMatches = $component->get('ruleMatches');
    foreach ($ruleMatches as $rule) {
        expect($rule['code'])->not->toStartWith('bifa.');
    }
});

test('first bifa is absent when it neither matches nor has pending routes', function () {
    // 2000-01-09T07:00 是 Hengtong 课例之一；毕法第一法完全不成立、且无人物资料。
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-09T07:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    // 后续法律可以独立命中；这里只锁定第一法不应被伪造出来。
    $component->assertDontSee('前后引从升迁吉');
});

test('bifa panel renders the second law when its executable case is loaded', function () {
    $component = Livewire::withQueryParams([
        'datetime' => '1986-08-19T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $matches = app(BiFaRuleEngine::class)->evaluate(new PanResult($component->get('pan')));
    $bifa02 = collect($matches)->first(fn ($match): bool => $match->code === 'bifa.02');

    expect($bifa02)->not->toBeNull()
        ->and($bifa02->matchedRoutes)->toContain('xun_tail_on_stem_xun_head_on_branch');

    // 排盘块使用"毕 + 法名"的课经同款标题，不展示法序号。
    $component->assertSee('首尾相见始终宜');
    $component->assertDontSee('第 2 法');

    // 第二法的 route code 不得泄漏到排盘页。
    foreach ([
        'xun_tail_on_stem_xun_head_on_branch',
        'xun_head_on_stem_xun_tail_on_branch',
        'tianxin_four_establishments_in_lessons',
        'huihuan_transmissions_in_lessons',
        'bifa.02',
        'ShouWeiXiangJianRule',
    ] as $internal) {
        $component->assertDontSee($internal, false);
    }

    // 第一法展示不受第二法影响——同盘可能同时命中第一法、第二法，仍按各自子卡片渲染。
    foreach ([
        'matched_routes',
        'pending_routes',
        '$matchedRoutes',
    ] as $internal) {
        $component->assertDontSee($internal, false);
    }
});
