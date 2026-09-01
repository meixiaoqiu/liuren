<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断贵人顺行、日辰乘贵前五将且发用旺相所成的三阳课。 */
final class SanyangRule implements PanRule
{
    protected const RULE_CODE = 'lesson.sanyang';

    protected const NAME = '三阳课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '贵人顺行，日干寄宫与日支均乘贵前五将，发用又得季节旺相。';

    protected const GUA = '晋';

    protected const GUA_SYMBOL = '䷢';

    protected const XIANG = '课入三阳，官爵翱翔。讼狱得释，疾病无妨。财喜遂意，行人还乡。贼来不战，孕产贤郎。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');

        if (! is_int($stem) || ! is_int($branch) || ! is_int($initial)) {
            return null;
        }

        $stemLodging = $facts->stemLodgingBranch($stem);

        if ($stemLodging === null
            || ! $facts->isNoblemanMovingForward()
            || ! $facts->isGroundPositionRidingNoblemanFrontGeneral($stemLodging)
            || ! $facts->isGroundPositionRidingNoblemanFrontGeneral($branch)
            || ! $facts->isBranchWangOrXiang($initial)) {
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
                'wang_xiang_elements' => $facts->wangXiangElements(),
                'nobleman_forward' => true,
                'nobleman_ground' => $facts->noblemanGroundPosition(),
                'day_stem' => [
                    'stem' => $stem,
                    'lodging_branch' => $stemLodging,
                    'front_general_rank' => $facts->noblemanFrontGeneralRankAtGroundPosition($stemLodging),
                    'general' => $facts->generalAtGroundPosition($stemLodging),
                ],
                'day_branch' => [
                    'branch' => $branch,
                    'front_general_rank' => $facts->noblemanFrontGeneralRankAtGroundPosition($branch),
                    'general' => $facts->generalAtGroundPosition($branch),
                ],
                'initial_transmission' => [
                    'branch' => $initial,
                    'element' => $facts->branchElement($initial),
                    'strength' => $facts->branchSeasonalStrength($initial),
                ],
            ],
        );
    }
}
