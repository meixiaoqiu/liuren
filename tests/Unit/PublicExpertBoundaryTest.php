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
