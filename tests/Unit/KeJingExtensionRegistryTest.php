<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PanRule;
use App\Domain\Pan\Rules\RuleMatch;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Extensions\KeJingContribution;
use App\Extensions\KeJingExtensionRegistry;
use App\Support\KeJingCatalog;
use App\Support\KeJingPageCatalog;
use App\Support\KeJingResearchDocument;
use Tests\TestCase;

uses(TestCase::class);

function coreFakeKeJingRule(string $code): PanRule
{
    return new class($code) implements PanRule
    {
        public function __construct(private readonly string $value) {}

        public function code(): string
        {
            return $this->value;
        }

        public function definition(): array
        {
            return ['description' => '虚构规则', 'xiang' => null, 'foundations' => [], 'judgments' => []];
        }

        public function match(PanFacts $facts): ?RuleMatch
        {
            return null;
        }
    };
}

beforeEach(function () {
    $this->app->instance(KeJingExtensionRegistry::class, new KeJingExtensionRegistry);
});

test('lesson.wulei extension restores all five expert asset types', function () {
    $registry = app(KeJingExtensionRegistry::class);
    $registry->contribute(new KeJingContribution(
        rules: [coreFakeKeJingRule('lesson.wulei')],
        summaries: ['lesson.wulei' => '虚构扩展摘要'],
        cases: ['lesson.wulei' => [['case_id' => 'lesson.fake.case', 'status' => 'executable']]],
        sourceExamples: ['lesson.wulei' => [['label' => '虚构旁证', 'source' => '《六壬大全》正文']]],
        researchDocuments: [['number' => 64, 'filename' => '64-fake.md', 'path' => storage_path('framework/testing/64-fake.md')]],
    ));

    $codes = array_map(static fn (PanRule $rule): string => $rule->code(), app(RuleRegistry::class)->rules());
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.wulei');
    $page = KeJingPageCatalog::findByCode('lesson.wulei');

    expect($codes)->toContain('lesson.wulei')
        ->and($lesson['summary'])->toBe('虚构扩展摘要')
        ->and($lesson['cases'])->toHaveCount(1)
        ->and($lesson['source_examples'][0]['source_type'])->toBe('daquan')
        ->and($page['researchPath'])->toBe(storage_path('framework/testing/64-fake.md'))
        ->and($page['researchUrl'])->toBe('');
});

test('duplicate extension and core rule conflicts fail clearly', function () {
    $registry = app(KeJingExtensionRegistry::class);
    $registry->registerRule(coreFakeKeJingRule('lesson.wulei'));

    expect(fn () => $registry->registerRule(coreFakeKeJingRule('lesson.wulei')))
        ->toThrow(LogicException::class, 'Duplicate KeJing extension rule code: lesson.wulei');

    $coreConflict = new KeJingExtensionRegistry;
    $coreConflict->registerRule(coreFakeKeJingRule('plate.fanyin'));
    $this->app->instance(KeJingExtensionRegistry::class, $coreConflict);

    expect(fn () => app(RuleRegistry::class)->rules())
        ->toThrow(LogicException::class, 'Duplicate Pan rule code: plate.fanyin');
});

test('unknown source without explicit source_type remains rejected', function () {
    app(KeJingExtensionRegistry::class)->registerSourceExamples('lesson.wulei', [[
        'label' => '未知来源',
        'source' => '未登记来源',
    ]]);

    expect(fn () => KeJingCatalog::lessons())->toThrow(LogicException::class, '未登记的课经旁证来源');
});

test('kejing research reader accepts an absolute extension path', function () {
    $path = storage_path('framework/testing/fake-kejing-research.md');
    file_put_contents($path, "## 《六壬大全》完整原文\n\n虚构原文\n");

    try {
        expect((new KeJingResearchDocument)->original(['researchPath' => $path]))->toBe([
            'status' => 'complete',
            'heading' => '《六壬大全》完整原文',
            'content' => '虚构原文',
        ]);
    } finally {
        @unlink($path);
    }
});
