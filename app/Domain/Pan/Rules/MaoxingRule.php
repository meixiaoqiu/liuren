<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课昴星课，涵盖虎视格与冬蛇掩目格。 */
final class MaoxingRule implements PanRule
{
    /** @var list<string> */
    protected const METHODS = ['hushi', 'dongshe_yanmu'];

    protected const RULE_CODE = 'selection.maoxing';

    protected const NAME = '昴星课';

    protected const GROUP = '初传取法';

    protected const DESCRIPTION = '四课无上下克贼、无遥克且四课俱全，依日干阴阳从酉位取初传。';

    protected const GUA = '履';

    protected const GUA_SYMBOL = '䷉';

    protected const XIANG = '关梁闭塞，越度稽留，行人作禁，孕男无忧，事恐惟外，祸起无由，家居守静，方免闲忧。';

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
