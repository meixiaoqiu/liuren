<?php

namespace App\Support\Knowledge;

use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use App\Support\BiFaResearchDocument;
use LogicException;

/**
 * 将毕法领域结果、目录、案例及研究资料适配为统一知识卡片。
 *
 * 本工厂的所有用户可见字符串（typeLabel、status.label、conditions.title、marker、
 * examples.source、status label 等）由本类负责生成；Blade 不做业务判断、不区分体系、
 * 不硬编码任何"毕法 / 课经 / 格"中文。
 */
final readonly class BiFaKnowledgeCardFactory
{
    public const TYPE = 'bifa';

    public const TYPE_LABEL = '毕法';

    private const TONE_SUCCESS = KnowledgeCard::TONE_SUCCESS;

    private const TONE_WARNING = KnowledgeCard::TONE_WARNING;

    private const TONE_INFO = KnowledgeCard::TONE_INFO;

    private const TONE_NEUTRAL = KnowledgeCard::TONE_NEUTRAL;

    public function __construct(private BiFaResearchDocument $research) {}

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
        $routeNames = $this->routeNames($foundations);
        $description = trim((string) ($definition['description'] ?? ''));
        $sections = array_values($definition['sections'] ?? []);
        foreach ($definition['judgments'] ?? [] as $judgment) {
            $sections[] = $this->judgmentSection($judgment);
        }
        $original = $this->research->original($law);
        $actions = [];
        if (($law['researchUrl'] ?? '') !== '' && $original['status'] !== 'missing') {
            $actions[] = [
                'label' => '打开完整研究记录',
                'url' => (string) $law['researchUrl'],
                'icon' => 'o-book-open',
                'external' => true,
            ];
        }

        return new KnowledgeCard(
            type: self::TYPE,
            typeLabel: self::TYPE_LABEL,
            label: '第 '.$catalogLaw['number'].' 法',
            title: $catalogLaw['name'],
            summary: $description !== '' ? $description : $catalogLaw['summary'],
            status: ['label' => '已完成研究', 'tone' => self::TONE_INFO],
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
            examples: $this->examples(BiFaCaseCatalog::casesForLaw((string) $law['code']), $routeNames),
            actions: $actions,
        );
    }

    /** @param array<string, mixed> $judgment @return array{title: string, content: string} */
    private function judgmentSection(array $judgment): array
    {
        $effectLabels = [
            'increase' => '增强',
            'enhance' => '增强',
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

    /**
     * @param  list<array<string, mixed>>  $cases
     * @param  array<string, string>  $routeNames
     * @return list<array{title: string, description: string, source: string, status: array{label: string, tone: string}, url: ?string}>
     */
    public function examples(array $cases, array $routeNames): array
    {
        return array_values(array_map(
            fn (array $case): array => $this->example($case, $routeNames),
            $cases,
        ));
    }

    /** @param list<array<string, mixed>> $items @return array<string, string> */
    private function routeNames(array $items): array
    {
        $names = [];
        foreach ($items as $item) {
            $names[(string) ($item['code'] ?? '')] = (string) ($item['title'] ?? '');
        }

        return $names;
    }

    /** @param array<string, mixed> $case @param array<string, string> $routeNames @return array<string, mixed> */
    private function example(array $case, array $routeNames): array
    {
        $isExecutable = ($case['status'] ?? '') === 'executable';
        $isGenerated = ($case['source_type'] ?? '') === 'generated';
        $names = array_values(array_filter(array_map(
            static fn (string $route): string => $routeNames[$route] ?? '',
            array_map('strval', $case['routes'] ?? []),
        )));
        $description = $isGenerated
            ? ($names === []
                ? '本案例用于说明本法不成立且无需继续评估的情形。'
                : '本案例用于说明"'.implode('、', $names).'"的成立或边界情形。')
            : (string) ($case['reason'] ?? '');

        $url = null;
        if ($isExecutable) {
            $params = array_filter([
                'datetime' => $case['datetime'] ?? null,
                'birth' => $case['birth'] ?? null,
                'gender' => $case['gender'] ?? null,
                'people' => empty($case['people']) ? null : $case['people'],
            ], static fn ($value): bool => $value !== null && $value !== '');
            $url = route('pan.create', $params);
        }

        return [
            'title' => (string) ($case['label'] ?? ''),
            'description' => $description,
            'source' => $isGenerated
                ? '现代程序验证案例'
                : '《六壬大全》正文案例 · '.(string) ($case['source'] ?? '本法'),
            'status' => $isExecutable
                ? ['label' => '可查看排盘', 'tone' => self::TONE_SUCCESS]
                : ['label' => '原文参考盘 · 尚未完整复现', 'tone' => self::TONE_WARNING],
            'url' => $url,
        ];
    }
}
