<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》《订讹》的严格口径判断殃咎课，并保留七条路线的完整盘面证据。
 *
 * 规则边界：递克仅接受两条确定方向；夹克仅检查初传；内外战必须三传同向全部成立；
 * 乘墓、坐墓均要求干支两项同时成立。神指传中天盘支，将指其所乘天将且使用天将固定五行。
 */
final class YangjiuRule implements PanRule
{
    private const STEM_TOMBS = [7, 10, 10, 1, 10, 1, 1, 4, 4, 7];

    private const BRANCH_TOMBS = [4, 4, 7, 7, 4, 10, 10, 4, 1, 1, 4, 4];

    private const ROUTE_NAMES = [
        'forward_recursive_overcoming' => '顺向递克',
        'reverse_recursive_overcoming' => '逆向递克',
        'initial_transmission_sandwiched_overcoming' => '初传夹克',
        'all_three_external_battle' => '三传外战',
        'all_three_internal_battle' => '三传内战',
        'stem_branch_riding_tombs' => '干支乘墓',
        'stem_branch_sitting_on_tombs' => '干支坐墓',
    ];

    public function code(): string
    {
        return 'lesson.yangjiu';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $analysis = $this->analyze($facts);
        if ($analysis === null || $analysis['matched_routes'] === []) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(), name: '殃咎课', group: '六十四课',
            description: '递克、夹克、三传内外战、干支乘墓或坐墓之一成立。',
            gua: '解', guaSymbol: '䷧',
            xiang: '五行克贼，征战凶祸。疾病增危，论讼反坐。官遭弹劾，人罹罪过。营干不成，出行不乐。',
            evidence: $analysis + [
                'foundations' => array_values(array_filter(array_map(
                    static fn (array $route): ?array => $route['matched'] ? ['title' => $route['label'], 'detail' => $route['summary']] : null,
                    $analysis['routes'],
                ))),
                'judgments' => [],
                'uncovered' => [
                    '末助初传克日、三传下贼上、日辰内战、墓神覆日等吉凶增强条件尚未程序化',
                    '墓逢空亡、众鬼有制、旺衰无意兴灾、财太旺及后发时节等救解或迟速条件尚未程序化',
                    '《灵觉经》单传或混合神将克战宽口径仅作候选统计，不进入正式 matcher',
                ],
            ],
        );
    }

    /** @return array{routes: array<string, array<string, mixed>>, matched_routes: list<string>}|null */
    public function analyze(PanFacts $facts): ?array
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $sike = $facts->get('sike');
        $tianpan = $facts->get('tianpan');
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
        $generals = [$facts->get('sanchuan0tianjiang'), $facts->get('sanchuan1tianjiang'), $facts->get('sanchuan2tianjiang')];

        if (! is_int($stem) || ! isset(self::STEM_TOMBS[$stem]) || ! is_int($branch) || ! isset(self::BRANCH_TOMBS[$branch])
            || ! is_array($sike) || count($sike) < 8 || ! is_array($tianpan) || count($tianpan) < 12
            || count(array_filter($transmissions, 'is_int')) !== 3 || count(array_filter($generals, 'is_int')) !== 3) {
            return null;
        }

        [$initial, $middle, $final] = $transmissions;
        $stemElement = $facts->stemElement($stem);
        $relations = [
            'initial_middle' => $this->branchRestrainsBranch($facts, $initial, $middle),
            'middle_final' => $this->branchRestrainsBranch($facts, $middle, $final),
            'final_stem' => $this->restrains($facts->branchElement($final), $stemElement),
            'final_middle' => $this->branchRestrainsBranch($facts, $final, $middle),
            'middle_initial' => $this->branchRestrainsBranch($facts, $middle, $initial),
            'initial_stem' => $this->restrains($facts->branchElement($initial), $stemElement),
        ];

        $transmissionEvidence = [];
        foreach ($transmissions as $index => $transmission) {
            $general = $generals[$index];
            $branchElement = $facts->branchElement($transmission);
            $generalElement = $facts->generalElement($general);
            if ($branchElement === null || $generalElement === null) {
                return null;
            }
            $transmissionEvidence[] = [
                'position' => ['初传', '中传', '末传'][$index],
                'branch' => $transmission,
                'branch_element' => $branchElement,
                'general' => $general,
                'general_element' => $generalElement,
                'external' => $this->restrains($generalElement, $branchElement),
                'internal' => $this->restrains($branchElement, $generalElement),
                'detail' => $this->battleDetail($transmission, $branchElement, $general, $generalElement),
            ];
        }

        $initialGround = $facts->heavenBranchGroundPosition($initial);
        if ($initialGround === null) {
            return null;
        }
        $groundRestrainsInitial = $this->branchRestrainsBranch($facts, $initialGround, $initial);
        $generalRestrainsInitial = $transmissionEvidence[0]['external'];

        $stemTomb = self::STEM_TOMBS[$stem];
        $branchTomb = self::BRANCH_TOMBS[$branch];
        $stemUpper = $sike[1] ?? null;
        $branchUpper = $sike[5] ?? null;
        $stemLodging = $facts->stemLodgingBranch($stem);
        if (! is_int($stemUpper) || ! is_int($branchUpper) || $stemLodging === null) {
            return null;
        }
        $stemRidesTomb = $stemUpper === $stemTomb;
        $branchRidesTomb = $branchUpper === $branchTomb;
        $stemSitsTomb = ($tianpan[$stemTomb] ?? null) === $stemLodging;
        $branchSitsTomb = ($tianpan[$branchTomb] ?? null) === $branch;

        $routes = [
            'forward_recursive_overcoming' => $this->route('forward_recursive_overcoming', $relations['initial_middle'] && $relations['middle_final'] && $relations['final_stem'], [
                $this->branchRelationDetail($facts, $initial, $middle), $this->branchRelationDetail($facts, $middle, $final),
                $this->branchStemRelationDetail($facts, $final, $stem),
            ]),
            'reverse_recursive_overcoming' => $this->route('reverse_recursive_overcoming', $relations['final_middle'] && $relations['middle_initial'] && $relations['initial_stem'], [
                $this->branchRelationDetail($facts, $final, $middle), $this->branchRelationDetail($facts, $middle, $initial),
                $this->branchStemRelationDetail($facts, $initial, $stem),
            ]),
            'initial_transmission_sandwiched_overcoming' => $this->route('initial_transmission_sandwiched_overcoming', $groundRestrainsInitial && $generalRestrainsInitial, [
                '初传'.$this->branchWithElement($facts, $initial),
                '下临地盘'.$this->branchWithElement($facts, $initialGround).'：'.$this->branchRelationDetail($facts, $initialGround, $initial),
                '所乘'.PanCalculator::$tianjiang[$generals[0]].'（'.$this->elementName($facts->generalElement($generals[0])).'）：'.$transmissionEvidence[0]['detail'],
            ]),
            'all_three_external_battle' => $this->route('all_three_external_battle', collect($transmissionEvidence)->every('external', true), array_column($transmissionEvidence, 'detail')),
            'all_three_internal_battle' => $this->route('all_three_internal_battle', collect($transmissionEvidence)->every('internal', true), array_column($transmissionEvidence, 'detail')),
            'stem_branch_riding_tombs' => $this->route('stem_branch_riding_tombs', $stemRidesTomb && $branchRidesTomb, [
                '日干'.PanCalculator::$tiangan[$stem].'墓在'.PanCalculator::$dizhi[$stemTomb].'，干上神为'.PanCalculator::$dizhi[$stemUpper],
                '日支'.PanCalculator::$dizhi[$branch].'墓在'.PanCalculator::$dizhi[$branchTomb].'，支上神为'.PanCalculator::$dizhi[$branchUpper],
            ]),
            'stem_branch_sitting_on_tombs' => $this->route('stem_branch_sitting_on_tombs', $stemSitsTomb && $branchSitsTomb, [
                PanCalculator::$tiangan[$stem].'寄'.PanCalculator::$dizhi[$stemLodging].'，'.PanCalculator::$tiangan[$stem].'墓'.PanCalculator::$dizhi[$stemTomb].'：天盘'.PanCalculator::$dizhi[$tianpan[$stemTomb]].'加地盘'.PanCalculator::$dizhi[$stemTomb],
                '日支'.PanCalculator::$dizhi[$branch].'墓'.PanCalculator::$dizhi[$branchTomb].'：天盘'.PanCalculator::$dizhi[$tianpan[$branchTomb]].'加地盘'.PanCalculator::$dizhi[$branchTomb],
            ]),
        ];

        return ['routes' => $routes, 'matched_routes' => array_keys(array_filter($routes, static fn (array $route): bool => $route['matched']))];
    }

    /** @param list<string> $details @return array{label: string, matched: bool, details: list<string>, summary: string} */
    private function route(string $code, bool $matched, array $details): array
    {
        return ['label' => self::ROUTE_NAMES[$code], 'matched' => $matched, 'details' => $details,
            'summary' => implode('；', $details).' → '.self::ROUTE_NAMES[$code].($matched ? '成立' : '不成立').'。'];
    }

    private function restrains(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && ($source + 2) % 5 === $target;
    }

    private function branchRestrainsBranch(PanFacts $facts, int $source, int $target): bool
    {
        return $this->restrains($facts->branchElement($source), $facts->branchElement($target));
    }

    private function branchRelationDetail(PanFacts $facts, int $source, int $target): string
    {
        return $this->branchWithElement($facts, $source).($this->branchRestrainsBranch($facts, $source, $target) ? '克' : '不克').$this->branchWithElement($facts, $target);
    }

    private function branchStemRelationDetail(PanFacts $facts, int $source, int $stem): string
    {
        return $this->branchWithElement($facts, $source).($this->restrains($facts->branchElement($source), $facts->stemElement($stem)) ? '克' : '不克').'日干'.PanCalculator::$tiangan[$stem].'（'.$this->elementName($facts->stemElement($stem)).'）';
    }

    private function battleDetail(int $branch, int $branchElement, int $general, int $generalElement): string
    {
        $prefix = PanCalculator::$dizhi[$branch].'（'.$this->elementName($branchElement).'）乘'.PanCalculator::$tianjiang[$general].'（'.$this->elementName($generalElement).'）';

        return match (true) {
            $this->restrains($generalElement, $branchElement) => $prefix.'：'.$this->elementName($generalElement).'克'.$this->elementName($branchElement).'（将克神）',
            $this->restrains($branchElement, $generalElement) => $prefix.'：'.$this->elementName($branchElement).'克'.$this->elementName($generalElement).'（神克将）',
            default => $prefix.'：不构成神将克战',
        };
    }

    private function branchWithElement(PanFacts $facts, int $branch): string
    {
        return PanCalculator::$dizhi[$branch].'（'.$this->elementName($facts->branchElement($branch)).'）';
    }

    private function elementName(?int $element): string
    {
        return $element === null ? '?' : (PanCalculator::$wuxing[$element] ?? '?');
    }
}
