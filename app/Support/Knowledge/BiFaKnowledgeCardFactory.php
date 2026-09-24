<?php

namespace App\Support\Knowledge;

use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Support\BiFaCaseCatalog;
use App\Support\BiFaCatalog;
use App\Support\BiFaResearchDocument;
use LogicException;

/** 将毕法领域结果、目录、案例及研究资料适配为统一知识卡片。 */
final readonly class BiFaKnowledgeCardFactory
{
    public function __construct(private BiFaResearchDocument $research) {}

    public function fromMatch(BiFaRuleMatch $match): KnowledgeCard
    {
        $law = BiFaCatalog::findByCode($match->code);
        if ($law === null) {
            throw new LogicException('毕法目录与判定结果不一致。');
        }

        $matchedCount = count($match->matchedRoutes);

        return new KnowledgeCard(
            type: '毕法',
            code: '第 '.$match->number.' 法',
            title: $match->name,
            summary: $match->summary,
            status: $match->matchedRoutes !== []
                ? ['label' => '已成立（'.$matchedCount.' 个分格成立）', 'tone' => 'success']
                : ['label' => '待补充人物资料后评估', 'tone' => 'warning'],
            conditions: array_values(array_map(
                static function (array $subMatch): array {
                    $needsPeople = ! empty($subMatch['requires_people']) && ! empty($subMatch['people_missing']);

                    return [
                        'marker' => '⏺',
                        'title' => (string) ($subMatch['title'] ?? ''),
                        'description' => (string) ($subMatch['description'] ?? ''),
                        'status' => ! empty($subMatch['matched'])
                            ? ['label' => '已成立', 'tone' => 'success']
                            : ($needsPeople
                                ? ['label' => '待评估', 'tone' => 'warning']
                                : ['label' => '未成立', 'tone' => 'neutral']),
                        'detail' => $subMatch['detail'] ?? ($needsPeople
                            ? '需要占测者本命或行年资料，当前资料不足。'
                            : null),
                    ];
                },
                $match->subMatches,
            )),
            evidence: [],
            sections: [],
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
            type: '毕法',
            code: '第 '.$catalogLaw['number'].' 法',
            title: $catalogLaw['name'],
            summary: $catalogLaw['summary'],
            status: ['label' => '已完成研究', 'tone' => 'info'],
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
            sections: [
                [
                    'title' => '总纲与现代说明',
                    'content' => (string) ($definition['description'] ?? $catalogLaw['summary']),
                ],
                [
                    'title' => '成立条件（9 类古籍分格）',
                    'content' => '引从天干与初末引从地支均须“初在前、末在后”，前后方向不可互换；其余夹拱结构不区分两端次序。本法与课经“引从课”虽有相近结构，但属于不同知识体系。',
                ],
            ],
            examples: $this->examples(BiFaCaseCatalog::casesForLaw((string) $law['code']), $routeNames),
            actions: $actions,
        );
    }

    /** @param array<string, mixed> $match */
    public function fromLegacyMatch(array $match): KnowledgeCard
    {
        return $this->fromMatch(new BiFaRuleMatch(
            code: (string) ($match['code'] ?? ''),
            number: (int) ($match['number'] ?? 0),
            name: (string) ($match['name'] ?? ''),
            summary: (string) ($match['summary'] ?? ''),
            subMatches: $match['sub_matches'] ?? [],
            matchedRoutes: $match['matched_routes'] ?? [],
            pendingRoutes: $match['pending_routes'] ?? [],
            evidence: $match['evidence'] ?? [],
        ));
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
                : '本案例用于说明“'.implode('、', $names).'”的成立或边界情形。')
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
                : '《六壬大全》正文案例 · '.(string) ($case['source'] ?? '第一法'),
            'status' => $isExecutable
                ? ['label' => '可查看排盘', 'tone' => 'success']
                : ['label' => '原文参考盘 · 尚未完整复现', 'tone' => 'warning'],
            'url' => $url,
        ];
    }
}
