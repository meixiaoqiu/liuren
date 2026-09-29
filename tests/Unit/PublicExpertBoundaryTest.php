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

    foreach ([['lesson.de_qing', 26, 'de-qing'], ['lesson.he_huan', 27, 'he-huan'], ['lesson.zhunfu', 40, 'zhunfu'], ['lesson.qinhai', 41, 'qinhai']] as [$code, $number, $slug]) {
        $lesson = collect($lessons)->firstWhere('code', $code);
        expect([$lesson['number'], $lesson['slug']])->toBe([$number, $slug]);
    }
});

test('AGENTS.md / LessonDefinitionDefaults / BiFaRuleMatch 不再泄漏专家实现线索', function () {
    $root = base_path();

    $textFiles = [
        $root.'/AGENTS.md',
        $root.'/app/Domain/Pan/Rules/LessonDefinitionDefaults.php',
        $root.'/app/Domain/Pan/BiFa/BiFaRuleMatch.php',
    ];

    foreach ($textFiles as $file) {
        expect(is_file($file))->toBeTrue("$file 必须存在");
        $content = file_get_contents($file);
        // 具体 route code
        expect($content)
            ->not->toContain('yin_gan', "$file 不应再出现具体 route code yin_gan")
            ->not->toContain('er_gui_gang_nianming', "$file 不应再出现具体 route code er_gui_gang_nianming")
            ->not->toContain('gan_zhi_bing_chu_zhong_gui', "$file 不应再出现具体 route code gan_zhi_bing_chu_zhong_gui");
        // 具体专家类名
        expect($content)
            ->not->toContain('QianHouYinCongRule', "$file 不应再出现具体专家类名 QianHouYinCongRule")
            ->not->toContain('XiuMuNanDiaoRule', "$file 不应再出现具体专家类名 XiuMuNanDiaoRule")
            ->not->toContain('SanguangRule', "$file 不应再出现具体专家类名 SanguangRule")
            ->not->toContain('YinCongRule', "$file 不应再出现具体专家类名 YinCongRule")
            ->not->toContain('LideRule', "$file 不应再出现具体专家类名 LideRule")
            ->not->toContain('JieliRule', "$file 不应再出现具体专家类名 JieliRule");
    }

    // BiFaRuleMatch docblock 不应再保留具体 route 名作为示例
    $bifaMatch = file_get_contents($root.'/app/Domain/Pan/BiFa/BiFaRuleMatch.php');
    expect($bifaMatch)
        ->not->toContain('引从天干', 'BiFaRuleMatch 不应再保留 matcher 内部中文描述作为示例');

    // LessonDefinitionDefaults docblock 不应再保留已迁移的具体类名清单
    $lesson = file_get_contents($root.'/app/Domain/Pan/Rules/LessonDefinitionDefaults.php');
    expect($lesson)
        ->not->toContain('SanguangRule', 'LessonDefinitionDefaults 不应再保留已迁移类名')
        ->not->toContain('YinCongRule', 'LessonDefinitionDefaults 不应再保留已迁移类名')
        ->not->toContain('LideRule', 'LessonDefinitionDefaults 不应再保留已迁移类名')
        ->not->toContain('JieliRule', 'LessonDefinitionDefaults 不应再保留已迁移类名');
});

test('AGENTS.md 不再列出具体 URL slug、route name 与 JSON 字段名作为禁词', function () {
    $agents = file_get_contents(base_path().'/AGENTS.md');
    expect($agents)
        ->not->toContain('cui-guan-shi-zhe', 'AGENTS.md 不应再出现具体 URL slug cui-guan-shi-zhe')
        ->not->toContain('bifa.show', 'AGENTS.md 不应再出现具体 route name bifa.show')
        ->not->toContain('sanchuan0', 'AGENTS.md 不应再出现具体 JSON 字段名 sanchuan0')
        ->not->toContain('guirenPeriod', 'AGENTS.md 不应再出现具体 JSON 字段名 guirenPeriod');
});

test('PluginLoader 仍能识别 Public 的已注册扩展点', function () {
    expect(class_exists(KeJingExtensionRegistry::class))->toBeTrue();
    expect(class_exists(BiFaExtensionRegistry::class))->toBeTrue();
    expect(app(KeJingExtensionRegistry::class))->toBeInstanceOf(KeJingExtensionRegistry::class);
    expect(app(BiFaExtensionRegistry::class))->toBeInstanceOf(BiFaExtensionRegistry::class);
});
