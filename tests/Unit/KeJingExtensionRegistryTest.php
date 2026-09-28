<?php

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PanRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Extensions\BiFaExtensionRegistry;
use App\Extensions\KeJingContribution;
use App\Extensions\KeJingExtensionRegistry;
use App\Support\KeJingCatalog;
use App\Support\KeJingPageCatalog;
use App\Support\KeJingResearchDocument;
use Tests\TestCase;

uses(TestCase::class);

function fakeKeJingRule(string $code): PanRule
{
    return new class($code) implements PanRule
    {
        public function __construct(private readonly string $code) {}

        public function code(): string
        {
            return $this->code;
        }

        public function definition(): array
        {
            return [
                'description' => '虚构课经规则',
                'xiang' => null,
                'foundations' => [],
                'judgments' => [],
            ];
        }

        public function match(PanFacts $facts): ?RuleMatch
        {
            return null;
        }
    };
}

function freshKeJingRegistry(): KeJingExtensionRegistry
{
    return new KeJingExtensionRegistry;
}

beforeEach(function () {
    $this->app->instance(KeJingExtensionRegistry::class, freshKeJingRegistry());
    $this->app->instance(BiFaExtensionRegistry::class, new BiFaExtensionRegistry);
});

afterEach(function () {
    $this->app->instance(KeJingExtensionRegistry::class, freshKeJingRegistry());
    $this->app->instance(BiFaExtensionRegistry::class, new BiFaExtensionRegistry);
});

test('plugin off preserves the 54 built-in lessons in number and code order', function () {
    $lessons = KeJingCatalog::lessons();
    $pages = KeJingPageCatalog::lessons();

    expect($lessons)->toHaveCount(54)
        ->and($pages)->toHaveCount(54)
        ->and(array_column($pages, 'number'))->toBe(range(11, 64));

    foreach ($pages as $page) {
        $lesson = collect($lessons)->firstWhere('code', $page['code']);
        $filename = sprintf('%02d-%s.md', $page['number'], $lesson['name']);

        expect($page['cases'])->toBe($lesson['cases'])
            ->and($page['source_examples'] ?? [])->toBe($lesson['source_examples'] ?? [])
            ->and($page['name'])->toBe($lesson['name'])
            ->and($page['gua'])->toBe($lesson['gua'])
            ->and($page['guaSymbol'])->toBe($lesson['guaSymbol'])
            ->and($page['summary'])->toBe($lesson['summary'])
            ->and($page['researchFilename'])->toBe($filename)
            ->and($page['researchPath'])->toBe('docs/课经/'.$filename)
            ->and($page['researchUrl'])->toContain(rawurlencode($filename));
    }
});

test('extension rule is appended once while direct construction remains compatible', function () {
    $registry = freshKeJingRegistry();
    $registry->registerRule(fakeKeJingRule('lesson.fake_extension'));
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    $containerCodes = array_map(static fn (PanRule $rule): string => $rule->code(), app(RuleRegistry::class)->rules());
    $directCodes = array_map(static fn (PanRule $rule): string => $rule->code(), (new RuleRegistry)->rules());

    expect(array_count_values($containerCodes)['lesson.fake_extension'] ?? 0)->toBe(1)
        ->and(array_key_last($containerCodes))->toBe(count($containerCodes) - 1)
        ->and($containerCodes[array_key_last($containerCodes)])->toBe('lesson.fake_extension')
        ->and($directCodes)->not->toContain('lesson.fake_extension')
        ->and(new PanRuleEngine)->toBeInstanceOf(PanRuleEngine::class);
});

test('duplicate extension rule code is rejected', function () {
    $registry = freshKeJingRegistry();
    $registry->registerRule(fakeKeJingRule('lesson.fake_extension'));

    expect(fn () => $registry->registerRule(fakeKeJingRule('lesson.fake_extension')))
        ->toThrow(LogicException::class, 'Duplicate KeJing extension rule code: lesson.fake_extension');
});

test('extension rule cannot duplicate a built-in rule code', function () {
    $registry = freshKeJingRegistry();
    $registry->registerRule(fakeKeJingRule('lesson.sanguang'));

    expect(fn () => (new RuleRegistry($registry))->rules())
        ->toThrow(LogicException::class, 'Duplicate Pan rule code: lesson.sanguang');
});

test('summary cases reference cases and source examples merge into the target lesson', function () {
    $registry = freshKeJingRegistry();
    $registry->registerSummary('lesson.sanguang', '虚构扩展摘要');
    $registry->registerCases('lesson.sanguang', [
        [
            'case_id' => 'lesson.fake.executable',
            'label' => '虚构可执行案例',
            'reason' => '仅验证扩展合并',
            'datetime' => '2031-01-01T01:00',
            'birth' => '2000-01-01T00:00',
            'gender' => 'male',
            'people' => [],
            'status' => 'executable',
            'source_type' => 'other',
        ],
        [
            'case_id' => 'lesson.fake.reference',
            'label' => '虚构参考案例',
            'reason' => '仅验证参考查找',
            'datetime' => '',
            'birth' => '',
            'gender' => 'male',
            'people' => [],
            'status' => 'reference_only',
            'source_type' => 'other',
        ],
    ]);
    $registry->registerSourceExamples('lesson.sanguang', [[
        'label' => '虚构旁证',
        'path' => '虚构路径',
        'detail' => '仅验证扩展合并',
        'source_type' => 'other',
    ]]);
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');

    expect($lesson['summary'])->toBe('虚构扩展摘要')
        ->and(array_column($lesson['cases'], 'case_id'))->toContain('lesson.fake.executable', 'lesson.fake.reference')
        ->and(array_column($lesson['source_examples'], 'label'))->toContain('虚构旁证')
        ->and(KeJingCatalog::findCase('lesson.fake.executable'))->not->toBeNull()
        ->and(KeJingCatalog::findReferenceCase('lesson.fake.reference'))->not->toBeNull()
        ->and(KeJingCatalog::findReferenceCase('lesson.fake.executable'))->toBeNull();
});

test('extension research document overrides public fallback and defaults its url to empty', function () {
    $path = tempnam(sys_get_temp_dir(), 'fake-kejing-research-');
    file_put_contents($path, "# 虚构课\n\n## 《六壬大全》完整原文\n\n虚构原文\n");

    $registry = freshKeJingRegistry();
    $registry->registerResearchDocument(64, '64-fake.md', $path);
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    $page = KeJingPageCatalog::findByCode('lesson.wulei');
    $original = (new KeJingResearchDocument)->original($page);

    expect($page['researchFilename'])->toBe('64-fake.md')
        ->and($page['researchPath'])->toBe($path)
        ->and($page['researchUrl'])->toBe('')
        ->and($original['status'])->toBe('complete')
        ->and($original['content'])->toBe('虚构原文');

    unlink($path);
});

test('relative public research path remains supported', function () {
    $page = KeJingPageCatalog::findByCode('lesson.youzi');
    $original = (new KeJingResearchDocument)->original($page);

    expect($page['researchPath'])->toBe('docs/课经/31-游子课.md')
        ->and($page['researchUrl'])->toContain('github.com/meixiaoqiu/liuren')
        ->and($original['status'])->not->toBe('missing');
});

test('one contribution registers all five asset categories', function () {
    $registry = freshKeJingRegistry();
    $registry->contribute(new KeJingContribution(
        rules: [fakeKeJingRule('lesson.fake_contribution')],
        summaries: ['lesson.sanguang' => '虚构整包摘要'],
        cases: ['lesson.sanguang' => [['case_id' => 'lesson.fake.bundle']]],
        sourceExamples: ['lesson.sanguang' => [['label' => '虚构整包旁证']]],
        researchDocuments: [['number' => 64, 'filename' => '64-bundle.md', 'path' => '/tmp/fake.md']],
    ));

    expect($registry->rules())->toHaveCount(1)
        ->and($registry->summaryFor('lesson.sanguang'))->toBe('虚构整包摘要')
        ->and($registry->casesForLesson('lesson.sanguang'))->toHaveCount(1)
        ->and($registry->sourceExamplesForLesson('lesson.sanguang'))->toHaveCount(1)
        ->and($registry->researchDocument(64)['filename'])->toBe('64-bundle.md');
});

test('kejing and bifa extension registries are distinct singleton lifecycles', function () {
    app(KeJingExtensionRegistry::class)->registerRule(fakeKeJingRule('lesson.fake_extension'));
    app(BiFaExtensionRegistry::class)->registerRule(new class implements BiFaRule
    {
        public function code(): string
        {
            return 'bifa.fake_extension';
        }

        public function law(): array
        {
            return [
                'number' => 99,
                'name' => '虚构毕法',
                'code' => 'bifa.fake_extension',
                'slug' => 'fake-extension',
                'summary' => '',
            ];
        }

        public function definition(): array
        {
            return [
                'description' => '虚构毕法规则',
                'foundations' => [],
                'judgments' => [],
                'sections' => [],
            ];
        }

        public function match(PanFacts $facts): ?BiFaRuleMatch
        {
            return null;
        }
    });

    $biFaCodes = array_map(
        static fn ($rule): string => $rule->code(),
        app(BiFaRuleRegistry::class)->rules(),
    );
    $panRuleCodes = array_map(
        static fn (PanRule $rule): string => $rule->code(),
        app(RuleRegistry::class)->rules(),
    );

    expect(app(KeJingExtensionRegistry::class))->toBe(app(KeJingExtensionRegistry::class))
        ->and(app(BiFaExtensionRegistry::class))->toBe(app(BiFaExtensionRegistry::class))
        ->and(app(KeJingExtensionRegistry::class))->not->toBe(app(BiFaExtensionRegistry::class))
        ->and($biFaCodes)->toContain('bifa.fake_extension')
        ->and($biFaCodes)->not->toContain('lesson.fake_extension')
        ->and($panRuleCodes)->toContain('lesson.fake_extension')
        ->and($panRuleCodes)->not->toContain('bifa.fake_extension');
});

test('extension source_examples without source_type auto classify via catalog source normalization', function () {
    $registry = freshKeJingRegistry();
    $registry->registerSourceExamples('lesson.sanguang', [[
        'label' => '虚构正文旁证',
        'source' => '《六壬大全》正文',
        'detail' => '仅测试 source_type 自动归类',
    ]]);
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');
    $page = collect(KeJingPageCatalog::lessons())->firstWhere('code', 'lesson.sanguang');

    expect($lesson['source_examples'][0]['source_type'])->toBe('daquan')
        ->and($page['daquanExamples'][0]['label'])->toBe('虚构正文旁证')
        ->and($page['otherExamples'])->toBe([]);
});

test('extension source_examples with unregistered source string throws LogicException', function () {
    $registry = freshKeJingRegistry();
    $registry->registerSourceExamples('lesson.sanguang', [[
        'label' => '未知来源',
        'source' => '《不存在的测试古籍》',
        'detail' => '测试严格枚举',
    ]]);
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    expect(fn () => KeJingCatalog::lessons())
        ->toThrow(LogicException::class, '未登记的课经旁证来源：《不存在的测试古籍》');
});

test('extension source_examples with explicit source_type other without source is preserved', function () {
    $registry = freshKeJingRegistry();
    $registry->registerSourceExamples('lesson.sanguang', [[
        'label' => '显式 other 旁证',
        'detail' => '无 source 字段但显式声明 other',
        'source_type' => 'other',
    ]]);
    $this->app->instance(KeJingExtensionRegistry::class, $registry);

    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');

    expect($lesson['source_examples'][0]['source_type'])->toBe('other')
        ->and($lesson['source_examples'][0]['label'])->toBe('显式 other 旁证');
});

test('plugin off preserves absence of source_examples key for 14 lessons and presence for 2 lessons', function () {
    $lessons = KeJingCatalog::lessons();

    $absentLessons = [
        'lesson.sanguang',
        'lesson.sanyang',
        'lesson.sanqi',
        'lesson.liuyi',
        'lesson.shitai',
        'lesson.longde',
        'lesson.guanjue',
        'lesson.fugui',
        'lesson.xuangai',
        'lesson.zhuyin',
        'lesson.zhuolun',
        'lesson.yincong',
        'lesson.hengtong',
        'lesson.fanchang',
    ];

    foreach ($absentLessons as $code) {
        $lesson = collect($lessons)->firstWhere('code', $code);
        expect(array_key_exists('source_examples', $lesson))->toBeFalse();
    }

    foreach (['lesson.he_huan', 'lesson.wulei'] as $code) {
        $lesson = collect($lessons)->firstWhere('code', $code);
        expect(array_key_exists('source_examples', $lesson))->toBeTrue();
    }
});
