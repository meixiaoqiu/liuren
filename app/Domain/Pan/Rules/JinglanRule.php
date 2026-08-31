<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义返吟课中四课无克、依井栏射法取传所成的井栏格。 */
final class JinglanRule implements PanRule
{
    protected const METHOD = 'fanyin_wuqin';

    protected const RULE_CODE = 'structure.jinglan';

    protected const NAME = '井栏格（无亲格）';

    protected const GROUP = '返吟课体';

    protected const DESCRIPTION = '返吟课无相克，以支辰傍射敌上神为初传，中传取支上神，末传取干上神。';

    protected const MARKER = '格';

    protected const XIANG = '行人阻遏，盗贼相攻。内外多怪，上下不恭。傍求事就，直求道穷。三传救护，喜见青龙。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== self::METHOD
            || $facts->sanchuanMethod('middle') !== self::METHOD
            || $facts->sanchuanMethod('final') !== self::METHOD) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            marker: self::MARKER,
            xiang: self::XIANG,
            evidence: [
                'initial_method' => self::METHOD,
                'middle_method' => self::METHOD,
                'final_method' => self::METHOD,
            ],
        );
    }
}
