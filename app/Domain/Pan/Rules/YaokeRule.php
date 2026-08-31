<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课遥克课，涵盖蒿矢格与弹射格。 */
final class YaokeRule implements PanRule
{
    /** @var list<string> */
    protected const METHODS = ['haoshi', 'tanshe'];

    protected const RULE_CODE = 'selection.yaoke';

    protected const NAME = '遥克课';

    protected const GROUP = '初传取法';

    protected const DESCRIPTION = '四课无上下克贼，取四课上神与日干遥克者发用；上神克日为蒿矢，日克上神为弹射。';

    protected const GUA = '睽';

    protected const GUA_SYMBOL = '䷥';

    protected const XIANG = '始有凶势，愈久愈休。忧喜未实，文书虚谋，外祸干已，有客为仇，兵利为主，不利他求。';

    /** @var list<string> */
    protected const COVERAGE_AREAS = ['initial_transmission'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $method = $facts->chuchuanMethod();

        if (! self::qualifies($facts)) {
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
                ...$facts->chuchuanEvidence(),
                'method' => $method,
            ],
            coverageAreas: self::COVERAGE_AREAS,
        );
    }

    public static function qualifies(PanFacts $facts): bool
    {
        return ! $facts->hasPlatePattern('fanyin')
            && in_array($facts->chuchuanMethod(), self::METHODS, true);
    }
}
