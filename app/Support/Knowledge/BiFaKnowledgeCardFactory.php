<?php

namespace App\Support\Knowledge;

use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 将毕法领域结果、目录适配为统一知识卡片。
 *
 * 本工厂的所有用户可见字符串（typeLabel、status.label、conditions.title、marker、
 * status label 等）由本类负责生成；Blade 不做业务判断、不区分体系、
 * 不硬编码任何"毕法 / 课经 / 格"中文。
 *
 * 案例展示由 bifa/show.blade.php 配合 BiFaCaseCatalog 直接渲染（详见
 * resources/views/bifa/partials/case-row.blade.php），不在本工厂内构造 examples。
 *
 * 古籍原文的获取由 routes/web.php 通过 BiFaResearchDocument 独立完成；
 * 详情页的"打开完整研究记录"按钮已统一在 show.blade.php 顶部渲染，
 * 因此本工厂不再生成 research action（避免重复）。
 */
final readonly class BiFaKnowledgeCardFactory
{
    public const TYPE = 'bifa';

    public const TYPE_LABEL = '毕法';

    private const TONE_SUCCESS = KnowledgeCard::TONE_SUCCESS;

    private const TONE_WARNING = KnowledgeCard::TONE_WARNING;

    private const TONE_NEUTRAL = KnowledgeCard::TONE_NEUTRAL;

    public function fromMatch(BiFaRuleMatch $match): KnowledgeCard
    {
        $law = BiFaCatalog::findByCode($match->code);
        if ($law === null) {
            throw new LogicException('毕法目录与判定结果不一致。');
        }

        $matchedCount = count($match->matchedRoutes);

        // 排盘块使用"毕 + 法名"的课经同款标题，不展示法序号；
        // 法序号仅出现在毕法详情页（由 fromDetail 提供）。
        return new KnowledgeCard(
            type: self::TYPE,
            typeLabel: self::TYPE_LABEL,
            label: '',
            title: $match->name,
            summary: $match->summary,
            status: $match->matchedRoutes !== []
                ? ['label' => '已成立（'.$matchedCount.' 个分格成立）', 'tone' => self::TONE_SUCCESS]
                : ['label' => '待补充人物资料后评估', 'tone' => self::TONE_WARNING],
            conditions: array_values(array_map(
                static function (array $subMatch): array {
                    $needsPeople = ! empty($subMatch['requires_people']) && ! empty($subMatch['people_missing']);

                    return [
                        'marker' => '⏺',
                        'title' => (string) ($subMatch['title'] ?? ''),
                        'description' => (string) ($subMatch['description'] ?? ''),
                        'status' => ! empty($subMatch['matched'])
                            ? ['label' => '已成立', 'tone' => self::TONE_SUCCESS]
                            : ($needsPeople
                                ? ['label' => '待评估', 'tone' => self::TONE_WARNING]
                                : ['label' => '未成立', 'tone' => self::TONE_NEUTRAL]),
                        'detail' => $subMatch['detail'] ?? ($needsPeople
                            ? '需要占测者本命或行年资料，当前资料不足。'
                            : null),
                    ];
                },
                $match->subMatches,
            )),
            evidence: [],
            sections: array_values(array_map(
                fn (array $judgment): array => $this->judgmentSection($judgment),
                $match->matchedJudgments,
            )),
            examples: [],
            actions: [[
                'label' => '查看本法详解',
                'url' => route('bifa.show', ['law' => $law['slug']]),
                'icon' => 'o-arrow-right',
                'external' => false,
            ]],
        );
    }

    /**
     * @param  array<string, mixed>  $law
     * @param  array<string, mixed>  $definition
     */
    public function fromDetail(array $law, array $definition): KnowledgeCard
    {
        $catalogLaw = BiFaCatalog::findByCode((string) ($law['code'] ?? ''));
        if ($catalogLaw === null) {
            throw new LogicException('毕法详情与目录不一致。');
        }

        $foundations = $definition['foundations'] ?? [];
        $description = trim((string) ($definition['description'] ?? ''));
        $sections = array_values($definition['sections'] ?? []);
        foreach ($definition['judgments'] ?? [] as $judgment) {
            $sections[] = $this->judgmentSection($judgment);
        }

        return new KnowledgeCard(
            type: self::TYPE,
            typeLabel: self::TYPE_LABEL,
            label: '第 '.$catalogLaw['number'].' 法',
            title: $catalogLaw['name'],
            summary: $description !== '' ? $description : $catalogLaw['summary'],
            status: null,
            conditions: array_values(array_map(
                static fn (array $foundation): array => [
                    'marker' => '⏺',
                    'title' => (string) ($foundation['title'] ?? ''),
                    'description' => (string) ($foundation['description'] ?? ''),
                    'status' => null,
                    'detail' => null,
                ],
                $foundations,
            )),
            evidence: [],
            sections: $sections,
            examples: [],
            actions: [],
        );
    }

    /** @param array<string, mixed> $judgment @return array{title: string, content: string} */
    private function judgmentSection(array $judgment): array
    {
        $effectLabels = [
            'increase' => '增强',
            'reduce' => '减损',
            'resolve' => '例外',
            'neutral' => '中性',
        ];
        $label = (string) ($judgment['label'] ?? '');
        $effectLabel = $effectLabels[$judgment['effect'] ?? ''] ?? null;

        return [
            'title' => $effectLabel === null ? $label : $effectLabel.' · '.$label,
            'content' => (string) ($judgment['description'] ?? ''),
        ];
    }
}
