<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按“白虎乘月神死神/死气，并临日、辰、行年或发用之一”判断魄化课；克制与囚死只作课义判断。 */
final class PohuaRule implements PanRule
{
    use LessonDefinitionDefaults;

    /** @var list<string> */
    private const ELEMENTS = ['木', '火', '土', '金', '水'];

    /** @var list<string> */
    private const UNCOVERED = [
        '《订讹》“墓乘虎作鬼加日亦是”属于独立扩义路线，未加入当前《大全》基础判断。',
        '虎衔尸的日墓、魁罡、死囚神与发用组合句法尚未冻结。',
        '年命上又为日鬼的“自己丧魄”，以及金神、三杀、血支、血忌尚未实现。',
        '天河、地井、悬索、勾绞、鬼门、虎阴神制虎尚未实现。',
        '日辰年命冲克、吉神救解及“魄化魂归，先忧后喜”的完整救解公式尚未实现。',
    ];

    public function code(): string
    {
        return 'lesson.pohua';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $monthBranch = $facts->get('yuezhi');
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        if (! is_int($monthBranch) || ! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($initial)) {
            return null;
        }
        if (! in_array($monthBranch, range(0, 11), true)
            || ! in_array($dayStem, range(0, 9), true)
            || ! in_array($dayBranch, range(0, 11), true)
            || ! in_array($initial, range(0, 11), true)) {
            return null;
        }

        $deathSpirit = ($monthBranch + 3) % 12;
        $deathQi = ($monthBranch + 4) % 12;
        $dayStemLodging = $facts->stemLodgingBranch($dayStem);
        if ($dayStemLodging === null) {
            return null;
        }

        $querent = $facts->personByRole('querent');
        $xingnian = is_array($querent) ? ($querent['xingnian'] ?? null) : null;
        $tigerBranch = null;
        $tigerType = null;
        foreach (['death_spirit' => $deathSpirit, 'death_qi' => $deathQi] as $type => $branch) {
            if ($facts->generalRidingBranch($branch) === 7) {
                $tigerBranch = $branch;
                $tigerType = $type;
                break;
            }
        }
        if ($tigerBranch === null || $tigerType === null) {
            return null;
        }

        $ground = $facts->heavenBranchGroundPosition($tigerBranch);
        if ($ground === null) {
            return null;
        }

        $routes = array_values(array_filter([
            $ground === $dayStemLodging ? 'day' : null,
            $ground === $dayBranch ? 'branch' : null,
            is_int($xingnian) && $ground === $xingnian ? 'xingnian' : null,
            $initial === $tigerBranch ? 'initial' : null,
        ]));
        if ($routes === []) {
            return null;
        }

        $typeName = $tigerType === 'death_spirit' ? '死神' : '死气';
        $branchName = PanCalculator::$dizhi[$tigerBranch] ?? '?';
        $groundName = PanCalculator::$dizhi[$ground] ?? '?';
        $monthName = PanCalculator::$dizhi[$monthBranch] ?? '?';
        $deathSpiritName = PanCalculator::$dizhi[$deathSpirit] ?? '?';
        $deathQiName = PanCalculator::$dizhi[$deathQi] ?? '?';
        $routeLabels = array_map(static fn (string $route): string => match ($route) {
            'day' => '临日', 'branch' => '临辰', 'xingnian' => '临行年', 'initial' => '发用',
        }, $routes);

        $positionDetail = "白虎{$typeName}{$branchName}位于地盘{$groundName}";
        $positionDetail .= match (true) {
            $routes === ['branch'] && $initial !== $tigerBranch => "，即{$typeName}{$branchName}临日支{$groundName}；虽未发用，仍符合魄化课。",
            default => '，'.implode('，', array_map(static fn (string $route): string => match ($route) {
                'day' => '加临日干'.(PanCalculator::$tiangan[$dayStem] ?? '?')."寄宫{$groundName}",
                'branch' => "加临日支{$groundName}",
                'xingnian' => "加临占人行年{$groundName}",
                'initial' => "且{$branchName}为初传",
            }, $routes)).'；命中“'.implode('、', $routeLabels).'”'.(count($routes) > 1 ? '多路。' : '一路。'),
        };

        return new RuleMatch(
            code: $this->code(), name: '魄化课', group: '六十四课',
            description: '白虎乘月神死神或死气，并临日、辰、行年或发用之一。',
            gua: '蛊', guaSymbol: '䷑',
            xiang: '人身丧魄，忧患相仍。病多丧死，讼有忧惊。产孕伤子，征战损兵。谋而招祸，切莫远行。',
            evidence: [
                'month_branch' => $monthBranch, 'death_spirit' => $deathSpirit, 'death_qi' => $deathQi,
                'tiger_branch' => $tigerBranch, 'tiger_type' => $tigerType,
                'tiger_ground_position' => $ground,
                'day_stem' => $dayStem, 'day_stem_lodging' => $dayStemLodging,
                'day_branch' => $dayBranch, 'xingnian' => is_int($xingnian) ? $xingnian : null, 'initial' => $initial,
                'matched_routes' => $routes,
                'foundations' => [
                    ['title' => '月神与白虎', 'detail' => "月建{$monthName}，死神在{$deathSpiritName}、死气在{$deathQiName}；本盘白虎乘{$branchName}，因此命中{$typeName}。"],
                    ['title' => '成立位置', 'detail' => $positionDetail],
                ],
                'judgments' => $this->judgments($facts, $tigerBranch, $ground, $dayStem, $dayBranch, $xingnian, $typeName),
                'uncovered' => [...self::UNCOVERED, ...(! is_int($xingnian) ? ['当前缺占人行年，只跳过“临行年”这一可选成立路线，其余盘面路线仍正常判断。'] : [])],
            ],
        );
    }

    /** @return list<array{code: string, effect: string, label: string, evidence: string}> */
    private function judgments(PanFacts $facts, int $tigerBranch, int $ground, int $dayStem, int $dayBranch, mixed $xingnian, string $typeName): array
    {
        $judgments = [];
        $tigerElement = $facts->branchElement($tigerBranch);
        $stemElement = $facts->stemElement($dayStem);
        $branchElement = $facts->branchElement($dayBranch);
        $groundElement = $facts->branchElement($ground);
        $branchName = PanCalculator::$dizhi[$tigerBranch] ?? '?';
        $elementName = self::ELEMENTS[$tigerElement] ?? '?';

        if ($this->restrains($tigerElement, $stemElement)) {
            $stemName = PanCalculator::$tiangan[$dayStem] ?? '?';
            $judgments[] = ['code' => 'tiger_restrains_day', 'effect' => 'increase', 'label' => '白虎死神/死气克日干', 'evidence' => "白虎{$typeName}{$branchName}属{$elementName}，克日干{$stemName}之".(self::ELEMENTS[$stemElement] ?? '?').'；《大全》谓“其神乘虎克日，占忌己身之灾”。'];
        }
        if ($this->restrains($tigerElement, $branchElement)) {
            $dayBranchName = PanCalculator::$dizhi[$dayBranch] ?? '?';
            $judgments[] = ['code' => 'tiger_restrains_branch', 'effect' => 'increase', 'label' => '白虎死神/死气克辰', 'evidence' => "白虎{$typeName}{$branchName}属{$elementName}，克日支{$dayBranchName}之".(self::ELEMENTS[$branchElement] ?? '?').'；古籍以此主门户之灾。'];
        }
        if (is_int($xingnian) && $this->restrains($tigerElement, $facts->branchElement($xingnian))) {
            $xingnianName = PanCalculator::$dizhi[$xingnian] ?? '?';
            $judgments[] = ['code' => 'tiger_restrains_xingnian', 'effect' => 'increase', 'label' => '白虎死神/死气克行年', 'evidence' => "白虎{$typeName}{$branchName}属{$elementName}，克占人行年{$xingnianName}之".(self::ELEMENTS[$facts->branchElement($xingnian)] ?? '?').'，作为行年受克的凶应增强。'];
        }

        $seasonalState = $facts->branchSeasonalState($tigerBranch);
        if (in_array($seasonalState, ['囚', '死'], true)) {
            $judgments[] = ['code' => 'tiger_seasonal_'.($seasonalState === '囚' ? 'qiu' : 'si'), 'effect' => 'increase', 'label' => "白虎所乘神又值时令{$seasonalState}", 'evidence' => "白虎乘{$typeName}{$branchName}；{$branchName}{$elementName}当前又处于时令{$seasonalState}，凶象进一步增强。"];
        }
        if ($this->restrains($tigerElement, $groundElement)) {
            $judgments[] = ['code' => 'upper_restrains_lower', 'effect' => 'increase', 'label' => '上克下', 'evidence' => "天盘{$branchName}{$elementName}克地盘".(PanCalculator::$dizhi[$ground] ?? '?').(self::ELEMENTS[$groundElement] ?? '?').'，古籍断为“外丧”。'];
        } elseif ($this->restrains($groundElement, $tigerElement)) {
            $judgments[] = ['code' => 'lower_restrains_upper', 'effect' => 'increase', 'label' => '下克上', 'evidence' => '地盘'.(PanCalculator::$dizhi[$ground] ?? '?').(self::ELEMENTS[$groundElement] ?? '?')."克天盘{$branchName}{$elementName}，古籍断为“内丧”。"];
        }

        $judgments[] = $tigerBranch % 2 === 0
            ? ['code' => 'tiger_yang', 'effect' => 'neutral', 'label' => '白虎所乘神在阳支', 'evidence' => "白虎乘{$typeName}{$branchName}，{$branchName}为阳支；古籍谓“虎在阳忧男”。"]
            : ['code' => 'tiger_yin', 'effect' => 'neutral', 'label' => '白虎所乘神在阴支', 'evidence' => "白虎乘{$typeName}{$branchName}，{$branchName}为阴支；古籍谓“虎在阴忧女”。"];

        return $judgments;
    }

    private function restrains(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && $target === ($source + 2) % 5;
    }
}
