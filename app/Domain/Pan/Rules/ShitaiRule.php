<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》正文及订讹，判断龙合首尾且入传岁月兼作日财德所成的时泰课。 */
final class ShitaiRule implements PanRule
{
    /** @var array<int, int> 甲至癸十日的日德。 */
    protected const DAY_VIRTUES = [2, 8, 5, 11, 5, 2, 8, 5, 11, 5];

    protected const RULE_CODE = 'lesson.shitai';

    protected const NAME = '时泰课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '初末传乘青龙、六合相对，太岁或月建入传并兼作日财或日德；发用更佳，但并非必要。';

    protected const GUA = '泰';

    protected const GUA_SYMBOL = '䷊';

    protected const XIANG = '课入时泰，皇恩欲拜。灾患潜消，谋为无碍。逃亡必归，盗贼自败。孕育贵儿，前程浩大。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $yearBranch = $facts->get('nianzhi');
        $monthBranch = $facts->get('yuezhi');
        $dayStem = $facts->get('rigan');
        $transmissions = $this->transmissions($facts);

        if (! is_int($yearBranch)
            || ! is_int($monthBranch)
            || ! is_int($dayStem)
            || $transmissions === null) {
            return null;
        }

        $transmissionGenerals = array_map(
            fn (int $branch): ?int => $facts->generalRidingBranch($branch),
            $transmissions,
        );
        $yearPositions = array_keys($transmissions, $yearBranch, true);
        $monthPositions = array_keys($transmissions, $monthBranch, true);
        $dayVirtue = self::DAY_VIRTUES[$dayStem] ?? null;
        $yearIsDayWealth = $facts->isDayWealthBranch($yearBranch);
        $monthIsDayWealth = $facts->isDayWealthBranch($monthBranch);
        $yearIsDayVirtue = $yearBranch === $dayVirtue;
        $monthIsDayVirtue = $monthBranch === $dayVirtue;
        $dragonUnionAtEdges = ($transmissionGenerals[0] === 5 && $transmissionGenerals[2] === 3)
            || ($transmissionGenerals[0] === 3 && $transmissionGenerals[2] === 5);
        $qualifyingCalendarGods = [];

        if ($yearPositions !== [] && ($yearIsDayWealth || $yearIsDayVirtue)) {
            $qualifyingCalendarGods[] = 'year';
        }

        if ($monthPositions !== [] && ($monthIsDayWealth || $monthIsDayVirtue)) {
            $qualifyingCalendarGods[] = 'month';
        }

        if (! $dragonUnionAtEdges || $qualifyingCalendarGods === []) {
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
                'year_branch' => $yearBranch,
                'month_branch' => $monthBranch,
                'day_stem' => $dayStem,
                'day_virtue' => $dayVirtue,
                'transmissions' => $transmissions,
                'transmission_generals' => $transmissionGenerals,
                'year_positions' => $yearPositions,
                'month_positions' => $monthPositions,
                'year_is_day_wealth' => $yearIsDayWealth,
                'month_is_day_wealth' => $monthIsDayWealth,
                'year_is_day_virtue' => $yearIsDayVirtue,
                'month_is_day_virtue' => $monthIsDayVirtue,
                'year_is_initial' => $transmissions[0] === $yearBranch,
                'month_is_initial' => $transmissions[0] === $monthBranch,
                'qualifying_calendar_gods' => $qualifyingCalendarGods,
                'dragon_union_at_edges' => true,
                'initial_strength' => $facts->branchSeasonalStrength($transmissions[0]),
                'dragon_positions' => array_keys($transmissionGenerals, 5, true),
                'union_positions' => array_keys($transmissionGenerals, 3, true),
            ],
        );
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
