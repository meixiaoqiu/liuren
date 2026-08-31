<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课知一课，统一涵盖九宗门中的比用法与知一法。 */
final class ZhiyiRule implements PanRule
{
    /** @var list<string> */
    protected const METHODS = ['biyong', 'zhiyi'];

    protected const RULE_CODE = 'selection.zhiyi';

    protected const NAME = '知一课';

    protected const GROUP = '初传取法';

    protected const DESCRIPTION = '四课中克贼不止一处，取与日干阴阳相比者为初传。';

    protected const GUA = '比';

    protected const GUA_SYMBOL = '䷇';

    protected const XIANG = '比者为喜，不比为忧。词宜和允，兵利主谋。祸从外起，事向朋谋。寻人失物，近处堪求。';

    /** @var list<string> */
    protected const COVERAGE_AREAS = ['initial_transmission'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $method = $facts->chuchuanMethod();

        if ($facts->hasPlatePattern('fanyin') || ! in_array($method, self::METHODS, true)) {
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
}
