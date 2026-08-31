<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课返吟课，并覆盖返吟三传的取法。 */
final class FanyinRule implements PanRule
{
    protected const PLATE_PATTERN = 'fanyin';

    protected const RULE_CODE = 'plate.fanyin';

    protected const NAME = '返吟课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '十二天神各居冲位；课有相克则取克发用，无克则依井栏射法取三传。';

    protected const GUA = '震';

    protected const GUA_SYMBOL = '䷲';

    protected const XIANG = '高岸为谷，深谷为陵。得物尤失，败物反成。安营离散，出阵虚惊。得生于外，害人自承。';

    /** @var list<string> */
    protected const PLATE_COVERAGE_AREAS = ['plate_pattern'];

    /** @var list<string> */
    protected const FULL_COVERAGE_AREAS = ['plate_pattern', 'initial_transmission', 'middle_transmission', 'final_transmission'];

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
            coverageAreas: $this->coverageAreas($facts),
        );
    }

    /** @return list<string> */
    private function coverageAreas(PanFacts $facts): array
    {
        if ($facts->hasLessonPattern('fanyin_wuyi') || $facts->chuchuanMethod() === 'fanyin_wuqin') {
            return self::FULL_COVERAGE_AREAS;
        }

        return self::PLATE_COVERAGE_AREAS;
    }
}
