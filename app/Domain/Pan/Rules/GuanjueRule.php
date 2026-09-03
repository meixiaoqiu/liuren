<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》课经正文判断驿马发用且魁常入传所成的官爵课。 */
final class GuanjueRule implements PanRule
{
    protected const RULE_CODE = 'lesson.guanjue';

    protected const NAME = '官爵课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '太岁、月建、本命或行年的驿马发用，同时天魁戌与太常入传；占日驿马只作课内参考。';

    protected const GUA = '益';

    protected const GUA_SYMBOL = '䷩';

    protected const XIANG = '官爵印绶，得之荣华。财名吉利，病讼堪嗟。访人不在，行者还家。孕生贵子，仕宦尤佳。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = $this->transmissions($facts);

        if ($transmissions === null) {
            return null;
        }

        $sourceBranches = array_filter([
            'year' => $facts->get('nianzhi'),
            'month' => $facts->get('yuezhi'),
            'birth_year' => $facts->get('nianming'),
            'annual_fate' => $facts->get('xingnian'),
        ], 'is_int');
        $sourceHorses = array_map($this->travelHorse(...), $sourceBranches);
        $dayBranch = $facts->get('rizhi');
        $dayHorse = is_int($dayBranch) ? $this->travelHorse($dayBranch) : null;
        $initial = $transmissions[0];
        $matchingHorseSources = array_keys($sourceHorses, $initial, true);
        $transmissionGenerals = array_map(
            fn (int $branch): ?int => $facts->generalRidingBranch($branch),
            $transmissions,
        );
        $tianKuiPositions = array_keys($transmissions, 10, true);
        $taiChangPositions = array_keys($transmissionGenerals, 8, true);

        if ($matchingHorseSources === [] || $tianKuiPositions === [] || $taiChangPositions === []) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'source_branches' => $sourceBranches,
                'source_horses' => $sourceHorses,
                'day_branch' => $dayBranch,
                'day_horse' => $dayHorse,
                'day_horse_matches_initial' => $dayHorse === $initial,
                'initial_transmission' => $initial,
                'matching_horse_sources' => $matchingHorseSources,
                'transmissions' => $transmissions,
                'transmission_generals' => $transmissionGenerals,
                'tiankui_positions' => $tianKuiPositions,
                'taichang_positions' => $taiChangPositions,
                'initial_strength' => $facts->branchSeasonalStrength($initial),
            ],
        );
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

    /** @return list<int>|null */
    private function transmissions(PanFacts $facts): ?array
    {
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        return count(array_filter($transmissions, 'is_int')) === 3 ? $transmissions : null;
    }
}
