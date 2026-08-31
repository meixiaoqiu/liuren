<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课伏吟课，并覆盖伏吟三传的取法。 */
final class FuyinRule implements PanRule
{
    protected const PLATE_PATTERN = 'fuyin';

    protected const RULE_CODE = 'plate.fuyin';

    protected const NAME = '伏吟课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '月将加时，十二天神各居本宫；有克取克，无克则分日干阴阳取用，中末传依刑冲法定。';

    protected const GUA = '艮';

    protected const GUA_SYMBOL = '䷳';

    protected const XIANG = '科举高中，求名荣归。病忧土怪，讼争田庐。春冬灾浅，秋夏势危。律身谨慎，动作无虞。';

    /** @var list<string> */
    protected const COVERAGE_AREAS = ['plate_pattern', 'initial_transmission', 'middle_transmission', 'final_transmission'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if (! $facts->hasPlatePattern(self::PLATE_PATTERN)) {
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
            evidence: ['plate_pattern' => self::PLATE_PATTERN],
            coverageAreas: self::COVERAGE_AREAS,
        );
    }
}
