<?php

namespace App\Extensions;

use App\Domain\Pan\BiFa\BiFaRule;
use LogicException;

/**
 * 文件作用：在公开宿主内登记外部插件对毕法体系的最小贡献。
 *
 * 本注册表只负责"存储"，不扫描任何路径、不读取任何 namespace、不感知任何具体插件。
 * 任何插件均可在 ServiceProvider::register() 中：
 *
 *     $registry = $this->app->make(BiFaExtensionRegistry::class);
 *     $registry->contribute(new BiFaContribution(...));
 *
 * 契约：contribute() 必须在 register() 阶段完成；PluginLoader::loadConfigured() 返回时
 * contribution 已可见，不依赖 boot() 阶段延后注入。
 *
 * 上层消费者按职责读取：
 *
 *   - BiFaRuleRegistry      -> 合并所有 extension rules（dedupe by code）
 *   - BiFaCatalog           -> summaryFor(code) 覆盖
 *   - BiFaCaseCatalog       -> casesForLaw(code) 合并
 *   - BiFaPageCatalog       -> researchDocument(number) 注册
 *   - BiFaResearchDocument  -> 不感知 extension；由 BiFaPageCatalog 把绝对路径喂进来
 *
 * 本类没有 static mutable state；实例由 Laravel 容器管理。
 */
final class BiFaExtensionRegistry
{
    /** @var list<BiFaRule> */
    private array $rules = [];

    /** @var array<string, string> */
    private array $summaries = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $cases = [];

    /** @var array<int, array{number: int, filename: string, path: string, research_url?: ?string}> */
    private array $researchDocuments = [];

    public function contribute(BiFaContribution $contribution): void
    {
        $this->registerRules($contribution->rules);
        $this->registerSummaries($contribution->summaries);
        $this->registerCasesBatch($contribution->cases);
        $this->registerResearchDocuments($contribution->researchDocuments);
    }

    /** @param list<BiFaRule> $rules */
    public function registerRules(array $rules): void
    {
        foreach ($rules as $rule) {
            $this->registerRule($rule);
        }
    }

    public function registerRule(BiFaRule $rule): void
    {
        $code = $rule->code();

        foreach ($this->rules as $existing) {
            if ($existing->code() === $code) {
                throw new LogicException('Duplicate BiFa extension rule code: '.$code);
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
        foreach ($cases as $lawCode => $list) {
            $this->registerCases($lawCode, $list);
        }
    }

    /** @param list<array<string, mixed>> $cases */
    public function registerCases(string $lawCode, array $cases): void
    {
        if (! isset($this->cases[$lawCode])) {
            $this->cases[$lawCode] = [];
        }

        foreach ($cases as $case) {
            $this->cases[$lawCode][] = $case;
        }
    }

    /** @param list<array{number: int, filename: string, path: string, research_url?: ?string}> $documents */
    public function registerResearchDocuments(array $documents): void
    {
        foreach ($documents as $doc) {
            $this->registerResearchDocument(
                (int) $doc['number'],
                (string) $doc['filename'],
                (string) $doc['path'],
                $doc['research_url'] ?? null,
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

    /** @return list<BiFaRule> */
    public function rules(): array
    {
        return $this->rules;
    }

    public function summaryFor(string $code): ?string
    {
        return $this->summaries[$code] ?? null;
    }

    /** @return list<array<string, mixed>> */
    public function casesForLaw(string $lawCode): array
    {
        return $this->cases[$lawCode] ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function allCases(): array
    {
        $all = [];
        foreach ($this->cases as $list) {
            foreach ($list as $case) {
                $all[] = $case;
            }
        }

        return $all;
    }

    /** @return array{number: int, filename: string, path: string, research_url?: ?string}|null */
    public function researchDocument(int $number): ?array
    {
        return $this->researchDocuments[$number] ?? null;
    }
}
