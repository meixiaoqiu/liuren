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
use App\Support\BiFaResearchDocument;
use Tests\TestCase;

uses(TestCase::class);

function coreFakeBiFaRule(string $code): BiFaRule
{
    return new class($code) implements BiFaRule
    {
        public function __construct(private readonly string $value) {}

        public function code(): string
        {
            return $this->value;
        }

        public function law(): array
        {
            return ['number' => 1, 'name' => '虚构毕法', 'code' => $this->value, 'slug' => 'fake', 'summary' => ''];
        }

        public function definition(): array
        {
            return ['description' => '虚构规则', 'foundations' => [], 'judgments' => [], 'sections' => []];
        }

        public function match(PanFacts $facts): ?BiFaRuleMatch
        {
            return null;
        }
    };
}

beforeEach(function () {
    $this->app->instance(BiFaExtensionRegistry::class, new BiFaExtensionRegistry);
});

test('bifa.01 extension restores rule summary cases and research metadata', function () {
    app(BiFaExtensionRegistry::class)->contribute(new BiFaContribution(
        rules: [coreFakeBiFaRule('bifa.01')],
        summaries: ['bifa.01' => '虚构摘要'],
        cases: ['bifa.01' => [['case_id' => 'bifa.fake.case', 'law_code' => 'bifa.01', 'source_type' => 'generated', 'status' => 'executable']]],
        researchDocuments: [['number' => 1, 'filename' => '01-fake.md', 'path' => storage_path('framework/testing/01-fake.md')]],
    ));

    expect(array_map(static fn (BiFaRule $rule): string => $rule->code(), app(BiFaRuleRegistry::class)->rules()))->toBe(['bifa.01'])
        ->and(BiFaCatalog::findByCode('bifa.01')['summary'])->toBe('虚构摘要')
        ->and(BiFaCaseCatalog::casesForLaw('bifa.01'))->toHaveCount(1)
        ->and(BiFaPageCatalog::findByCode('bifa.01')['researched'])->toBeTrue()
        ->and(BiFaPageCatalog::findByCode('bifa.01')['researchPath'])->toBe(storage_path('framework/testing/01-fake.md'));
});

test('duplicate bifa extension code fails clearly', function () {
    $registry = app(BiFaExtensionRegistry::class);
    $registry->registerRule(coreFakeBiFaRule('bifa.01'));

    expect(fn () => $registry->registerRule(coreFakeBiFaRule('bifa.01')))
        ->toThrow(LogicException::class, 'Duplicate BiFa extension rule code: bifa.01');
});

test('bifa research reader accepts an absolute extension path', function () {
    $path = storage_path('framework/testing/fake-bifa-research.md');
    file_put_contents($path, "## 二、古籍原文\n\n虚构原文\n\n## 三、解释\n");

    try {
        expect((new BiFaResearchDocument)->original(['researchPath' => $path]))->toMatchArray([
            'status' => 'complete',
            'content' => '虚构原文',
        ]);
    } finally {
        @unlink($path);
    }
});
