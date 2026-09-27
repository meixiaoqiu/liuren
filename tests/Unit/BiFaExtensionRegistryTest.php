<?php

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Facts\PanFacts;
use App\Extensions\BiFaContribution;
use App\Extensions\BiFaExtensionRegistry;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use App\Support\BiFaPageCatalog;
use Tests\TestCase;

uses(TestCase::class);

/**
 * 本测试只使用"虚构贡献"，故意不写入任何具体毕法的真实判定知识或案例。
 * BiFaExtensionRegistry 是公开宿主为外部插件准备的最小钩子；具体内容由插件侧提供。
 *
 * 测试范围：
 *  - rules 注入 BiFaRuleRegistry 后能被合并去重；
 *  - summaries 注入 BiFaCatalog 后能覆盖默认空串；
 *  - cases 注入 BiFaCaseCatalog 后能扩展案例；
 *  - researchDocuments 注入 BiFaPageCatalog 后能改变 researched/researchPath 状态。
 */
function fakeRule(string $code, string $name, string $slug): BiFaRule
{
    return new class($code, $name, $slug) implements BiFaRule
    {
        public function __construct(
            private readonly string $code,
            private readonly string $name,
            private readonly string $slug,
        ) {}

        public function code(): string
        {
            return $this->code;
        }

        public function law(): array
        {
            return [
                'number' => 99,
                'name' => $this->name,
                'code' => $this->code,
                'slug' => $this->slug,
                'summary' => '',
            ];
        }

        public function definition(): array
        {
            return [
                'description' => 'fake rule description',
                'foundations' => [['code' => 'fake_route', 'title' => '虚构分格', 'description' => 'fake']],
                'judgments' => [],
                'sections' => [],
            ];
        }

        public function match(PanFacts $facts): ?BiFaRuleMatch
        {
            return null;
        }
    };
}

function freshRegistry(): BiFaExtensionRegistry
{
    return new BiFaExtensionRegistry;
}

afterEach(function () {
    // 任何测试都不得把假数据漏到全局单例，影响后续测试。
    $registry = app(BiFaExtensionRegistry::class);
    $reflection = new ReflectionClass($registry);
    foreach (['rules', 'summaries', 'cases', 'researchDocuments'] as $field) {
        $property = $reflection->getProperty($field);
        $property->setAccessible(true);
        $property->setValue($registry, match ($field) {
            'rules' => [],
            'summaries' => [],
            'cases' => [],
            'researchDocuments' => [],
        });
    }
});

test('extension rules are merged into BiFaRuleRegistry and de-duplicated by code', function () {
    $registry = freshRegistry();
    $registry->registerRule(fakeRule('bifa.99', '虚构一法', 'fake-rule'));

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $codes = array_map(static fn (BiFaRule $rule): string => $rule->code(), app(BiFaRuleRegistry::class)->rules());

    expect($codes)->toContain('bifa.99')
        ->and(array_count_values($codes)['bifa.99'] ?? 0)->toBe(1);
});

test('extension rules with duplicate code are rejected by the extension registry', function () {
    $registry = freshRegistry();
    $registry->registerRule(fakeRule('bifa.99', '虚构一法', 'fake-rule'));

    expect(fn () => $registry->registerRule(fakeRule('bifa.99', '同名异slug', 'fake-rule-2')))
        ->toThrow(LogicException::class, 'Duplicate BiFa extension rule code: bifa.99');
});

test('extension summary overrides the empty BiFaCatalog summary by code', function () {
    $registry = freshRegistry();
    $registry->registerSummary('bifa.99', '虚构简介：用于验证扩展摘要钩子。');

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $law = BiFaCatalog::findByCode('bifa.99');

    expect($law)->not->toBeNull()
        ->and($law['summary'])->toBe('虚构简介：用于验证扩展摘要钩子。');
});

test('extension summary is left untouched when BiFaCatalog already has a non-empty summary', function () {
    // 法号 1 已自带 summary；扩展覆盖不得被吞并。
    $registry = freshRegistry();
    $registry->registerSummary('bifa.01', '虚构覆盖一法');

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $law = BiFaCatalog::findByCode('bifa.01');

    expect($law)->not->toBeNull()
        ->and($law['summary'])->toBe('虚构覆盖一法');
});

test('extension cases are merged into BiFaCaseCatalog::cases() for their law_code', function () {
    $registry = freshRegistry();
    $registry->registerCases('bifa.99', [
        [
            'case_id' => 'bifa.99.fake-generated',
            'law_code' => 'bifa.99',
            'label' => '虚构程序验证案例',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2031-01-01T03:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['fake_route'],
            'reason' => '用于扩展测试',
            'source' => '程序验证案例·扩展测试',
        ],
    ]);

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $cases = BiFaCaseCatalog::casesForLaw('bifa.99');
    expect($cases)->toHaveCount(1)
        ->and($cases[0]['case_id'])->toBe('bifa.99.fake-generated')
        ->and($cases[0]['routes'])->toBe(['fake_route']);
});

test('extension research document switches BiFaPageCatalog to researched=true and exposes the contributed path', function () {
    $registry = freshRegistry();
    $registry->registerResearchDocument(99, '99-fake.md', '/tmp/fake-research.md');

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $page = BiFaPageCatalog::findByCode('bifa.99');

    expect($page)->not->toBeNull()
        ->and($page['researched'])->toBeTrue()
        ->and($page['researchPath'])->toBe('/tmp/fake-research.md')
        ->and($page['researchFilename'])->toBe('99-fake.md');
});

test('extension research document without a research_url leaves researchUrl empty in the page row', function () {
    $registry = freshRegistry();
    $registry->registerResearchDocument(99, '99-fake.md', '/tmp/fake-research.md');

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $page = BiFaPageCatalog::findByCode('bifa.99');

    expect($page['researchUrl'])->toBe('');
});

test('extension research document overrides the public docs/毕法/*.md fallback when it exists for the same number', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'fake-bifa-research-');
    file_put_contents($tmp, "# fake\n## 古籍原文\nfake text");
    $registry = freshRegistry();
    $registry->registerResearchDocument(1, '01-overridden.md', $tmp);

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $page = BiFaPageCatalog::findByCode('bifa.01');
    expect($page['researchPath'])->toBe($tmp)
        ->and($page['researchFilename'])->toBe('01-overridden.md')
        ->and($page['researched'])->toBeTrue();

    @unlink($tmp);
});

test('BiFaContribution DTO can register all four categories in one call', function () {
    $registry = freshRegistry();

    $registry->contribute(new BiFaContribution(
        rules: [fakeRule('bifa.98', '虚构二法', 'fake-rule-2')],
        summaries: ['bifa.98' => '虚构简介 98'],
        cases: [
            'bifa.98' => [[
                'case_id' => 'bifa.98.fake',
                'law_code' => 'bifa.98',
                'label' => '虚构',
                'source_type' => 'generated',
                'status' => 'reference_only',
                'datetime' => null,
                'birth' => null,
                'gender' => null,
                'people' => [],
                'routes' => [],
                'reason' => 'fake',
                'source' => 'fake',
            ]],
        ],
        researchDocuments: [
            ['number' => 98, 'filename' => '98-fake.md', 'path' => '/tmp/fake-98.md'],
        ],
    ));

    $this->app->instance(BiFaExtensionRegistry::class, $registry);

    $codes = array_map(static fn (BiFaRule $rule): string => $rule->code(), app(BiFaRuleRegistry::class)->rules());
    expect($codes)->toContain('bifa.98')
        ->and(BiFaCatalog::findByCode('bifa.98')['summary'])->toBe('虚构简介 98')
        ->and(BiFaCaseCatalog::casesForLaw('bifa.98'))->toHaveCount(1)
        ->and(BiFaPageCatalog::findByCode('bifa.98')['researched'])->toBeTrue();
});
