<?php

use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Extensions\BiFaExtensionRegistry;
use App\Extensions\KeJingExtensionRegistry;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use App\Support\KeJingCatalog;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->app->instance(KeJingExtensionRegistry::class, new KeJingExtensionRegistry);
    $this->app->instance(BiFaExtensionRegistry::class, new BiFaExtensionRegistry);
});

test('plugin off public pan registry contains exactly the 28 core rules in order', function () {
    $classes = array_map(static fn ($rule): string => $rule::class, app(RuleRegistry::class)->rules());
    $expected = array_map(
        static fn (string $name): string => 'App\\Domain\\Pan\\Rules\\'.$name,
        [
            'FanyinRule', 'FuyinRule', 'ZirenRule', 'ZixinRule', 'DuzhuanRule',
            'YuanshouRule', 'ChongshenRule', 'ZhiyiRule', 'ShehaiRule', 'JianjiRule',
            'ChaweiRule', 'ZhuixiaRule', 'YaokeRule', 'HaoshiRule', 'TansheRule',
            'MaoxingRule', 'HushiRule', 'DongsheYanmuRule', 'BiezheRule', 'BazhuanRule',
            'ChongSanchuanRule', 'TianpanShunchuanRule', 'HushiSanchuanRule',
            'DongsheYanmuSanchuanRule', 'GanShangshenSanchuanRule',
            'JinglanRule', 'DuzuRule', 'WeibuBuxiuRule',
        ],
    );

    expect($classes)->toBe($expected)
        ->and((new RuleRegistry)->rules())->toHaveCount(28);
});

test('plugin off public catalogs contain identity only', function () {
    $lessons = KeJingCatalog::lessons();
    $laws = BiFaCatalog::laws();

    expect($lessons)->toHaveCount(54)
        ->and(array_column($lessons, 'number'))->toBe(range(11, 64))
        ->and(array_unique(array_column($lessons, 'code')))->toHaveCount(54)
        ->and(array_unique(array_column($lessons, 'slug')))->toHaveCount(54)
        ->and(array_filter($lessons, static fn (array $lesson): bool => $lesson['summary'] !== ''))->toBe([])
        ->and(array_filter($lessons, static fn (array $lesson): bool => isset($lesson['cases']) || isset($lesson['source_examples'])))->toBe([])
        ->and(array_filter($lessons, static fn (array $lesson): bool => $lesson['gua'] !== null))->toBe([])
        ->and(array_filter($lessons, static fn (array $lesson): bool => $lesson['guaSymbol'] !== null))->toBe([])
        ->and(app(KeJingExtensionRegistry::class)->lessonMetadataFor('lesson.wulei'))->toBeNull()
        ->and(app(KeJingExtensionRegistry::class)->traceViewFor('lesson.wulei'))->toBeNull()
        ->and($laws)->toHaveCount(100)
        ->and(array_column($laws, 'number'))->toBe(range(1, 100))
        ->and(array_filter($laws, static fn (array $law): bool => $law['summary'] !== ''))->toBe([])
        ->and(BiFaCaseCatalog::cases())->toBe([])
        ->and(app(BiFaRuleRegistry::class)->rules())->toBe([]);

    // 课号映射：4 个非顺序课必须按 KeJingPageCatalog golden source 注册，
    // 不允许按 Catalog 数组下标机械生成。
    foreach ([['lesson.de_qing', 26, 'de-qing'], ['lesson.he_huan', 27, 'he-huan'], ['lesson.zhunfu', 40, 'zhunfu'], ['lesson.qinhai', 41, 'qinhai']] as [$code, $number, $slug]) {
        $lesson = collect($lessons)->firstWhere('code', $code);
        expect([$lesson['number'], $lesson['slug']])->toBe([$number, $slug]);
    }
});

test('LessonDefinitionDefaults docblock 只描述 generic behavior', function () {
    $trait = file_get_contents(base_path().'/app/Domain/Pan/Rules/LessonDefinitionDefaults.php');

    // 必须存在核心声明语句
    expect($trait)
        ->toContain('definition()')
        ->toContain('foundations')
        ->toContain('judgments')
        ->toContain('xiang')
        ->toContain('description');

    // docblock 不能直接列举具体专家规则类名（不能写 `App\Domain\Pan\Rules\XxxRule`）。
    // 走结构性白名单：合法提及只能是 namespace 前缀 `App\Domain\Pan\Rules\`。
    preg_match_all(
        '/\b(App\\\\Domain\\\\Pan\\\\Rules\\\\[A-Z][A-Za-z0-9_]+Rule)\b/',
        $trait,
        $matches,
    );
    $mentioned = array_unique($matches[1] ?? []);

    // 只允许出现 trait 自身的调用样板，不允许列举"已迁移的具体课"。
    // 直接排除任何可能写出的具体规则名：
    expect($mentioned)->toBe([]);
});

test('BiFaRuleMatch docblock 只描述 generic route / evidence 语义', function () {
    $match = file_get_contents(base_path().'/app/Domain/Pan/BiFa/BiFaRuleMatch.php');

    // 必须存在通用字段
    expect($match)
        ->toContain('public string $code')
        ->toContain('public int $number')
        ->toContain('public array $subMatches')
        ->toContain('public array $matchedRoutes')
        ->toContain('public array $pendingRoutes')
        ->toContain('public array $evidence')
        ->toContain('public array $matchedJudgments')
        ->toContain('matched_routes')
        ->toContain('pending_routes');

    // docblock 中提及 "code" 时不得伪装成具体 bifa 编号形式。
    // 结构上禁止出现形如 `bifa.NN`（N 为数字）的内联示例。
    preg_match_all('/\bbifa\.\d+\b/', $match, $matches);
    expect($matches[0] ?? [])->toBe([]);
});

test('AGENTS.md 只写抽象治理规则，不绑定具体实现', function () {
    $agents = file_get_contents(base_path().'/AGENTS.md');

    // 必须保留通用治理条目
    expect($agents)
        ->toContain('# 仓库工作规则')
        ->toContain('## Git 安全')
        ->toContain('## README 归属')
        ->toContain('## PHP 与 Docker 运行环境');

    // 结构上禁止内联任何具体 route / rule / class 字面量。
    // 这里只做粗粒度结构性约束：禁止出现 `App\` 完整命名空间（除注释的合法引用）。
    preg_match_all('/\bApp\\\\[A-Z][A-Za-z0-9_\\\\]+/', $agents, $matches);
    expect($matches[0] ?? [])->toBe([]);

    // docblock 中不允许出现 `Route code`、`Rule class` 等具体领域示例前缀。
    // 仅做结构性断言：禁止出现 `rule code = '` 之类的具体字面量。
    expect($agents)
        ->not->toMatch("/rule\s*code\s*=\s*['\"]/i")
        ->not->toMatch("/route\s*code\s*=\s*['\"]/i");

    // 只允许在抽象语境使用 `内部实现名称` / `内部标识` 之类抽象表述。
    expect($agents)
        ->toContain('内部实现名称')
        ->toContain('route code');
});

test('PluginLoader 仍能识别 Public 的已注册扩展点', function () {
    expect(class_exists(KeJingExtensionRegistry::class))->toBeTrue();
    expect(class_exists(BiFaExtensionRegistry::class))->toBeTrue();
    expect(app(KeJingExtensionRegistry::class))->toBeInstanceOf(KeJingExtensionRegistry::class);
    expect(app(BiFaExtensionRegistry::class))->toBeInstanceOf(BiFaExtensionRegistry::class);
});
