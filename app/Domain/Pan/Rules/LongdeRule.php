<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断太岁乘贵人发用且月将入传所成的龙德课。 */
final class LongdeRule implements PanRule
{
    protected const RULE_CODE = 'lesson.longde';

    protected const NAME = '龙德课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '太岁乘天乙贵人发用，月将又入于三传。';

    protected const GUA = '萃';

    protected const GUA_SYMBOL = '䷬';

    protected const XIANG = '君恩及下，万姓欢忻。罪囚出狱，财喜临身。和名易萃，争讼休陈。官爵超擢，利见大人。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $yearBranch = $facts->get('nianzhi');
        $monthGeneral = $facts->get('yuejiang');
        $transmissions = $this->transmissions($facts);

        if (! is_int($yearBranch) || ! is_int($monthGeneral) || $transmissions === null) {
            return null;
        }

        $initialGeneral = $facts->generalRidingBranch($transmissions[0]);
        $monthGeneralPositions = array_keys($transmissions, $monthGeneral, true);

        if ($transmissions[0] !== $yearBranch
            || $initialGeneral !== 0
            || $monthGeneralPositions === []) {
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
                'month_general' => $monthGeneral,
                'transmissions' => $transmissions,
                'initial_general' => $initialGeneral,
                'month_general_positions' => $monthGeneralPositions,
                'year_and_month_general_coincide' => $yearBranch === $monthGeneral,
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
