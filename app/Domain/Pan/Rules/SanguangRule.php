<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断日、辰、用神旺相且三处乘吉将所成的三光课。 */
final class SanguangRule implements PanRule
{
    /** @var list<int> */
    protected const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    protected const RULE_CODE = 'lesson.sanguang';

    protected const NAME = '三光课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干、日支与发用均得月令旺相，日上神、辰上神与发用又均乘吉将。';

    protected const GUA = '贲';

    protected const GUA_SYMBOL = '䷕';

    protected const XIANG = '课入三光，万事吉昌。刑囚释放，疾病安康。市贾得利，谋干俱良。福佑自至，凶祸消亡。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $lessons = $facts->get('sike');

        if (! is_int($stem) || ! is_int($branch) || ! is_int($initial) || ! is_array($lessons)) {
            return null;
        }

        $dayUpper = $lessons[1] ?? null;
        $branchUpper = $lessons[5] ?? null;

        if (! is_int($dayUpper) || ! is_int($branchUpper)) {
            return null;
        }

        $generals = [
            'day_upper' => $facts->generalRidingBranch($dayUpper),
            'branch_upper' => $facts->generalRidingBranch($branchUpper),
            'initial' => $facts->generalRidingBranch($initial),
        ];

        if (! $facts->isStemWangOrXiang($stem)
            || ! $facts->isBranchWangOrXiang($branch)
            || ! $facts->isBranchWangOrXiang($initial)
            || array_filter(
                $generals,
                fn (?int $general): bool => in_array($general, self::AUSPICIOUS_GENERALS, true),
            ) !== $generals) {
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
                'month_branch' => $facts->get('yuezhi'),
                'day_stem' => $stem,
                'day_branch' => $branch,
                'initial_transmission' => $initial,
                'generals' => $generals,
            ],
        );
    }
}
