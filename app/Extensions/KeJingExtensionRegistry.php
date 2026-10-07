<?php

namespace App\Extensions;

use App\Domain\Pan\Rules\PanRule;
use LogicException;

/**
 * 文件作用：保存外部插件在 register 阶段贡献给课经体系的通用扩展资产。
 *
 * 本注册表不扫描路径、不识别具体插件，也没有静态可变状态；实例由 Laravel 容器管理。
 */
final class KeJingExtensionRegistry
{
    /** @var list<PanRule> */
    private array $rules = [];

    /** @var array<string, string> */
    private array $summaries = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $cases = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $sourceExamples = [];

    /** @var array<int, array{number: int, filename: string, path: string, research_url?: ?string}> */
    private array $researchDocuments = [];

    /** @var array<string, array{gua: mixed, guaSymbol: mixed}> */
    private array $lessonMetadata = [];

    /** @var array<string, array{view: string, contexts: list<string>, title?: string}> */
    private array $traceViews = [];

    public function contribute(KeJingContribution $contribution): void
    {
        $this->registerRules($contribution->rules);
        $this->registerSummaries($contribution->summaries);
        $this->registerCasesBatch($contribution->cases);
        $this->registerSourceExamplesBatch($contribution->sourceExamples);
        $this->registerResearchDocuments($contribution->researchDocuments);
        $this->registerLessonMetadataBatch($contribution->lessonMetadata);
        $this->registerTraceViewsBatch($contribution->traceViews);
    }

    public function registerLessonMetadataBatch(array $metadata): void
    {
        foreach ($metadata as $code => $spec) {
            $this->registerLessonMetadata($code, $spec);
        }
    }

    public function registerLessonMetadata(string $code, array $metadata): void
    {
        if (array_key_exists($code, $this->lessonMetadata)) {
            throw new LogicException('Duplicate KeJing lesson metadata code: '.$code);
        }

        $this->lessonMetadata[$code] = [
            'gua' => $metadata['gua'] ?? null,
            'guaSymbol' => $metadata['guaSymbol'] ?? null,
        ];
    }

    public function lessonMetadataFor(string $code): ?array
    {
        return $this->lessonMetadata[$code] ?? null;
    }

    public function registerTraceViewsBatch(array $views): void
    {
        foreach ($views as $code => $spec) {
            $this->registerTraceView($code, $spec);
        }
    }

    public function registerTraceView(string $code, array $spec): void
    {
        if (array_key_exists($code, $this->traceViews)) {
            throw new LogicException('Duplicate KeJing trace view code: '.$code);
        }

        $view = trim((string) ($spec['view'] ?? ''));
        if ($view === '') {
            throw new LogicException('KeJing trace view must not be empty: '.$code);
        }

        $contexts = array_values($spec['contexts'] ?? []);
        foreach ($contexts as $context) {
            if (! in_array($context, ['pan', 'detail'], true)) {
                throw new LogicException('Unknown KeJing trace context: '.(string) $context);
            }
        }

        $normalized = ['view' => $view, 'contexts' => $contexts];
        if (isset($spec['title']) && trim((string) $spec['title']) !== '') {
            $normalized['title'] = (string) $spec['title'];
        }
        $this->traceViews[$code] = $normalized;
    }

    public function traceViewFor(string $code): ?array
    {
        return $this->traceViews[$code] ?? null;
    }

    /** @param list<PanRule> $rules */
    public function registerRules(array $rules): void
    {
        foreach ($rules as $rule) {
            $this->registerRule($rule);
        }
    }

    public function registerRule(PanRule $rule): void
    {
        foreach ($this->rules as $existing) {
            if ($existing->code() === $rule->code()) {
                throw new LogicException('Duplicate KeJing extension rule code: '.$rule->code());
            }
        }

        $this->rules[] = $rule;
    }

    /** @param array<string, string> $summaries */
    public function registerSummaries(array $summaries): void
    {
        foreach ($summaries as $code => $summary) {
            $this->registerSummary($code, $summary);
        }
    }

    public function registerSummary(string $code, string $summary): void
    {
        $this->summaries[$code] = $summary;
    }

    /** @param array<string, list<array<string, mixed>>> $cases */
    public function registerCasesBatch(array $cases): void
    {
        foreach ($cases as $lessonCode => $lessonCases) {
            $this->registerCases($lessonCode, $lessonCases);
        }
    }

    /** @param list<array<string, mixed>> $cases */
    public function registerCases(string $lessonCode, array $cases): void
    {
        $this->cases[$lessonCode] = [...($this->cases[$lessonCode] ?? []), ...$cases];
    }

    /** @param array<string, list<array<string, mixed>>> $examples */
    public function registerSourceExamplesBatch(array $examples): void
    {
        foreach ($examples as $lessonCode => $lessonExamples) {
            $this->registerSourceExamples($lessonCode, $lessonExamples);
        }
    }

    /** @param list<array<string, mixed>> $examples */
    public function registerSourceExamples(string $lessonCode, array $examples): void
    {
        $this->sourceExamples[$lessonCode] = [...($this->sourceExamples[$lessonCode] ?? []), ...$examples];
    }

    /** @param list<array{number: int, filename: string, path: string, research_url?: ?string}> $documents */
    public function registerResearchDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->registerResearchDocument(
                (int) $document['number'],
                (string) $document['filename'],
                (string) $document['path'],
                $document['research_url'] ?? null,
            );
        }
    }

    public function registerResearchDocument(int $number, string $filename, string $path, ?string $researchUrl = null): void
    {
        $this->researchDocuments[$number] = [
            'number' => $number,
            'filename' => $filename,
            'path' => $path,
            'research_url' => $researchUrl,
        ];
    }

    /** @return list<PanRule> */
    public function rules(): array
    {
        return $this->rules;
    }

    public function hasRuleCode(string $code): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->code() === $code) {
                return true;
            }
        }

        return false;
    }

    public function summaryFor(string $code): ?string
    {
        return $this->summaries[$code] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public function casesForLesson(string $lessonCode): array
    {
        return $this->cases[$lessonCode] ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function sourceExamplesForLesson(string $lessonCode): array
    {
        return $this->sourceExamples[$lessonCode] ?? [];
    }

    /** @return array{number: int, filename: string, path: string, research_url?: ?string}|null */
    public function researchDocument(int $number): ?array
    {
        return $this->researchDocuments[$number] ?? null;
    }
}
