<?php

use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCatalog;
use App\Support\Knowledge\BiFaKnowledgeCardFactory;
use App\Support\Knowledge\KnowledgeCard;

/**
 * KnowledgeCard 统一契约——保证未来扩展到课经 / 格 / 任意知识体系时，
 * 不会向 Blade 泄漏任何内部标识符或调试字段。
 *
 * 适用范围：
 *  - `KnowledgeCard` DTO 构造器与字段约束；
 *  - `BiFaKnowledgeCardFactory` 全部公开方法；
 *  - `KnowledgeCard` / `BiFaKnowledgeCardFactory` 的 toArray() 序列化输出；
 *  - 第一法 `QianHouYinCongRule` 的产物在工厂内的"漂白"完整性。
 *
 * 任何知识体系（bifa / kejing / pattern）都必须通过本套契约。
 */
test('KnowledgeCard bifa 体系可经由完整信息序列生成', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    expect($match)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);

    expect($card)->toBeInstanceOf(KnowledgeCard::class);
});

test('KnowledgeCard 仅暴露用户可读字段，不泄漏 rule code / matcher class / debug 字段', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);

    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // 程序规则码（如 `bifa.01`、`yin_gan`、`yin_zhi`、`qian_gong`、`hou_gong`、`matched_routes`）不得出现
    expect($payload)
        ->not->toContain('bifa.01', '不应泄漏 bifa.NN 程序编码')
        ->not->toContain('yin_gan', '不应泄漏子格名')
        ->not->toContain('yin_zhi', '不应泄漏子格名')
        ->not->toContain('matched_routes', '不应泄漏命中明细字段')
        ->not->toContain('pending_routes', '不应泄漏待评估字段')
        ->not->toContain('sub_matches', '不应泄漏子匹配结构')
        ->not->toContain('QianHouYinCongRule', '不应泄漏 matcher 类名')
        ->not->toContain('BiFaRuleEngine', '不应泄漏引擎类名')
        ->not->toContain('BiFaKnowledgeCardFactory', '不应泄漏工厂类名');
});

test('KnowledgeCard type 使用稳定内部类型键，UI 不得基于此字段判断', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);

    expect($card->type)->toBe('bifa')              // 稳定内部类型键
        ->and($card->typeLabel)->toBe('毕法')         // 展示中文由 Factory 注入
        // 排盘上下文不展示法序号，仅 type；法序号由 fromDetail() 注入
        ->and($card->label)->toBe('');
});

test('KnowledgeCard status.tone 必须是 success / warning / info / neutral 枚举之一', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);

    expect($card->status)->not->toBeNull()
        ->and($card->status['tone'])->toBeIn(KnowledgeCard::validTones());

    // conditions 内部条目的 status.tone 同样受约束
    foreach ($card->conditions as $condition) {
        if ($condition['status'] !== null) {
            expect($condition['status']['tone'])->toBeIn(KnowledgeCard::validTones());
        }
    }
});

test('KnowledgeCard 拒绝非法 status.tone', function () {
    KnowledgeCard::assertValidStatus(['label' => 'x', 'tone' => 'success']);
    KnowledgeCard::assertValidStatus(['label' => 'x', 'tone' => 'warning']);
    KnowledgeCard::assertValidStatus(['label' => 'x', 'tone' => 'info']);
    KnowledgeCard::assertValidStatus(['label' => 'x', 'tone' => 'neutral']);

    expect(fn () => KnowledgeCard::assertValidStatus(['label' => 'x', 'tone' => 'bogus']))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => KnowledgeCard::assertValidStatus(['label' => 'x']))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => KnowledgeCard::assertValidStatus(['tone' => 'success']))
        ->toThrow(InvalidArgumentException::class);
});

test('KnowledgeCard 构造器拒绝空 type / typeLabel，但允许空 label（排盘上下文省略法序号）', function () {
    expect(fn () => new KnowledgeCard(type: '', typeLabel: '毕法', label: '第 1 法', title: 't', summary: ''))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => new KnowledgeCard(type: 'bifa', typeLabel: '', label: '第 1 法', title: 't', summary: ''))
        ->toThrow(InvalidArgumentException::class);
    // label 可为空字符串：详情/排盘上下文按需注入
    $card = new KnowledgeCard(type: 'bifa', typeLabel: '毕法', label: '', title: 't', summary: '');
    expect($card->label)->toBe('')
        ->and($card->type)->toBe('bifa')
        ->and($card->typeLabel)->toBe('毕法');
});

test('KnowledgeCard fromDetail 不输出内部 rule code / debug 字段', function () {
    $law = collect(BiFaCatalog::laws())->first(fn (array $row): bool => $row['code'] === 'bifa.01');
    expect($law)->not->toBeNull();

    $definition = [
        'description' => '测试用定义。',
        'foundations' => [
            ['code' => 'yin_gan', 'title' => '引从天干', 'description' => '初传居日干前，末传居日干后。'],
        ],
    ];

    $card = app(BiFaKnowledgeCardFactory::class)->fromDetail($law, $definition);
    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($card->type)->toBe('bifa')
        ->and($card->typeLabel)->toBe('毕法')
        ->and($card->label)->toBe('第 1 法')
        ->and($payload)->not->toContain('yin_gan', '子格 code 不应进入展示序列化')
        ->and($payload)->not->toContain('bifa.01', '程序编码 bifa.NN 不应进入展示序列化');
});

test('KnowledgeCard 全部公开字段都是 toArray 可序列化、类型稳定', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);

    $array = $card->toArray();

    expect($array)->toHaveKeys([
        'type', 'type_label', 'label', 'title', 'summary',
        'status', 'conditions', 'evidence', 'sections', 'examples', 'actions',
    ])->not->toHaveKey('code')
        ->and($array['type'])->toBeString()
        ->and($array['type_label'])->toBeString()
        ->and($array['label'])->toBeString()
        ->and($array['title'])->toBeString()
        ->and($array['conditions'])->toBeArray()
        ->and($array['examples'])->toBeArray();
});

test('BiFaKnowledgeCardFactory fromDetail 只转换 definition 提供的法条知识', function () {
    $law = BiFaCatalog::findByCode('bifa.02');
    expect($law)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromDetail($law, [
        'description' => '第二法测试说明',
        'foundations' => [[
            'code' => 'fake_route',
            'title' => '测试分格',
            'description' => '测试条件',
        ]],
        'judgments' => [],
        'sections' => [[
            'title' => '测试说明',
            'content' => '这是第二法专属说明',
        ]],
    ]);

    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($payload)
        ->toContain('测试分格', '测试说明', '第二法专属说明')
        ->not->toContain('引从天干')
        ->not->toContain('初末引从地支')
        ->not->toContain('9 类古籍分格')
        ->not->toContain('引从课')
        ->not->toContain('第一法');
});

test('BiFaKnowledgeCardFactory 优先使用 definition description 并在空值时回退目录摘要', function () {
    // 必须用目录摘要非空的法律（如第一法）来验证 fallback，
    // 否则若用 bifa.02（summary=''），断言 '' === '' 即使 fallback 完全失效也会通过。
    $law = BiFaCatalog::findByCode('bifa.01');
    expect($law)->not->toBeNull();
    expect($law['summary'])->not->toBe('');

    $factory = app(BiFaKnowledgeCardFactory::class);
    $definition = ['description' => '第一法现代汉语说明', 'foundations' => [], 'judgments' => []];
    $card = $factory->fromDetail($law, $definition);

    expect($card->summary)->toBe('第一法现代汉语说明');

    // description 为空白字符串也应回退到目录摘要——这条断言必须用真实非空目录摘要，
    // 才能锁死"只有 definition description 真正可用时才采用"的契约。
    $definition['description'] = '   ';
    $fallbackCard = $factory->fromDetail($law, $definition);

    expect($fallbackCard->summary)
        ->toBe('初末传分临日干（或日支）前后宫，前引后从，主迁官进职、修宅迁居。');
});

test('BiFaKnowledgeCardFactory 将 judgments 转为用户可读 sections 且不泄漏 effect', function () {
    $law = BiFaCatalog::findByCode('bifa.02');
    expect($law)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromDetail($law, [
        'description' => '第二法现代汉语说明',
        'foundations' => [],
        'sections' => [['title' => '既有说明', 'content' => '既有内容']],
        'judgments' => [[
            'label' => '某增强条件',
            'description' => '这是增强后的用户说明。',
            'effect' => 'increase',
        ]],
    ]);
    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($card->sections)->toContain(['title' => '既有说明', 'content' => '既有内容'])
        ->and($card->sections)->toContain([
            'title' => '增强 · 某增强条件',
            'content' => '这是增强后的用户说明。',
        ])
        ->and($payload)->not->toContain('increase');
});

test('BiFaKnowledgeCardFactory 对未知 judgment effect 不泄漏内部值到前台', function () {
    // 未来若新增 effect 分类（如 future_internal_effect）但 Factory 尚未同步翻译，
    // 该内部值必须落到普通标题上，绝不直接显示给用户、绝不进入序列化。
    $law = BiFaCatalog::findByCode('bifa.02');
    expect($law)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromDetail($law, [
        'description' => '第二法现代汉语说明',
        'foundations' => [],
        'judgments' => [[
            'label' => '未知判断',
            'description' => '未知效果测试',
            'effect' => 'future_internal_effect',
        ]],
    ]);
    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    // 未知 effect 必须退化为"只展示用户标签"，不得拼接任何 effect 前缀
    expect($card->sections)->toContain([
        'title' => '未知判断',
        'content' => '未知效果测试',
    ])->and($payload)->not->toContain('future_internal_effect')
        ->and($payload)->not->toContain('future_internal')
        ->and($payload)->not->toContain('internal_effect');
});
