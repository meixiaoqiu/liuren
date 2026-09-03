<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断天乙旺相、上下相生并临日辰年命发用所成的富贵课。 */
final class FuguiRule implements PanRule
{
    protected const RULE_CODE = 'lesson.fugui';

    protected const NAME = '富贵课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '天乙贵人乘旺相之神发用，上下五行相生，又临日干寄宫、日支、本命或行年。';

    protected const GUA = '大有';

    protected const GUA_SYMBOL = '䷍';

    protected const XIANG = '天降福德，万事新鲜。财喜双美，富贵两全。孕生贵子，婚配婵娟。狱讼得理，谋望胜前。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $heavenPlate = $facts->get('tianpan');

        if (! is_int($initial) || ! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($heavenPlate)) {
            return null;
        }

        $ground = array_search($initial, $heavenPlate, true);
        $dayStemLodging = $facts->stemLodgingBranch($dayStem);

        if (! is_int($ground) || $dayStemLodging === null) {
            return null;
        }

        $upperElement = $facts->branchElement($initial);
        $lowerElement = $facts->branchElement($ground);
        $generatingDirection = $this->generatingDirection($upperElement, $lowerElement);
        $targetGrounds = array_filter([
            'day_stem' => $dayStemLodging,
            'day_branch' => $dayBranch,
            'birth_year' => $facts->get('nianming'),
            'annual_fate' => $facts->get('xingnian'),
        ], 'is_int');
        $matchingTargets = array_keys($targetGrounds, $ground, true);

        if ($facts->generalRidingBranch($initial) !== 0
            || ! $facts->isBranchWangOrXiang($initial)
            || $generatingDirection === null
            || $matchingTargets === []) {
            return null;
        }

        $modifiers = $this->modifiers($facts, $ground);
        $modifierCodes = array_column($modifiers, 'code');
        $hasReduction = in_array('nobleman_in_prison', $modifierCodes, true)
            && ! in_array('prison_exception', $modifierCodes, true);
        $hasEnhancement = array_filter(
            $modifiers,
            fn (array $modifier): bool => $modifier['effect'] === 'increase_auspicious',
        ) !== [];
        $currentState = $hasReduction
            ? ['key' => 'auspicious_with_obstruction', 'label' => '吉中有阻']
            : ($hasEnhancement
                ? ['key' => 'auspicious_enhanced', 'label' => '吉象增强']
                : ['key' => 'auspicious', 'label' => '吉象成立']);

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'initial_transmission' => $initial,
                'initial_general' => 0,
                'initial_element' => $upperElement,
                'initial_strength' => $facts->branchSeasonalStrength($initial),
                'ground_branch' => $ground,
                'ground_element' => $lowerElement,
                'generating_direction' => $generatingDirection,
                'target_grounds' => $targetGrounds,
                'matching_targets' => $matchingTargets,
                'seasonal_period' => $facts->seasonalPeriod(),
                'base_tendency' => ['key' => 'auspicious', 'label' => '吉'],
                'current_state' => $currentState,
                'modifiers' => $modifiers,
            ],
        );
    }

    /** @return list<array{effect: string, status: string, code: string, label: string, evidence: string}> */
    private function modifiers(PanFacts $facts, int $noblemanGround): array
    {
        $modifiers = [];

        $transmissions = array_filter([
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ], 'is_int');
        $transmissionGenerals = array_map(
            fn (int $branch): ?int => $facts->generalRidingBranch($branch),
            $transmissions,
        );

        if (in_array(8, $transmissionGenerals, true)) {
            $modifiers[] = [
                'effect' => 'increase_auspicious',
                'status' => 'active',
                'code' => 'taichang_ribbon',
                'label' => '太常为绶',
                'evidence' => '三传中见太常。',
            ];
        }

        $horseSources = array_filter([
            '太岁' => $facts->get('nianzhi'),
            '月建' => $facts->get('yuezhi'),
            '本命' => $facts->get('nianming'),
            '行年' => $facts->get('xingnian'),
        ], 'is_int');
        $dragonHorseSources = [];

        foreach ($horseSources as $source => $branch) {
            $horse = $this->travelHorse($branch);

            if ($facts->generalRidingBranch($horse) === 5) {
                $dragonHorseSources[$source] = $horse;
            }
        }

        if ($dragonHorseSources !== []) {
            $branchNames = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
            $details = [];

            foreach ($dragonHorseSources as $source => $horse) {
                $details[] = $source.$branchNames[$horse].'马';
            }

            $modifiers[] = [
                'effect' => 'increase_auspicious',
                'status' => 'active',
                'code' => 'horse_riding_dragon',
                'label' => '驿马乘青龙',
                'evidence' => implode('、', $details).'乘青龙。',
            ];
        }

        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $birthYear = $facts->get('nianming');
        $annualFate = $facts->get('xingnian');
        $prisonException = in_array($dayStem, [1, 7], true)
            || in_array($dayBranch, [4, 10], true)
            || in_array($birthYear, [4, 10], true)
            || in_array($annualFate, [4, 10], true);

        if (in_array($noblemanGround, [4, 10], true)) {
            $modifiers[] = [
                'effect' => 'reduce_auspicious',
                'status' => 'active',
                'code' => 'nobleman_in_prison',
                'label' => '贵人入狱',
                'evidence' => '天乙贵人临地盘辰戌，富贵之势受限。',
            ];

            if ($prisonException) {
                $modifiers[] = [
                    'effect' => 'resolve_inauspicious',
                    'status' => 'exempted',
                    'code' => 'prison_exception',
                    'label' => '不以坐狱论',
                    'evidence' => '贵人虽临辰戌，但当前日或年命符合原典豁免。',
                ];
            }
        }

        return $modifiers;
    }

    private function generatingDirection(?int $upper, ?int $lower): ?string
    {
        if ($upper === null || $lower === null) {
            return null;
        }

        if (($upper + 1) % 5 === $lower) {
            return 'upper_generates_lower';
        }

        return ($lower + 1) % 5 === $upper ? 'lower_generates_upper' : null;
    }

    private function travelHorse(int $branch): int
    {
        return match ($branch % 4) {
            0 => 2,
            1 => 11,
            2 => 8,
            3 => 5,
        };
    }
}
