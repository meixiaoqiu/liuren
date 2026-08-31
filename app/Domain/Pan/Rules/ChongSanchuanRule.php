<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：识别中传、末传逐次取冲神的三传规则，并返回命中证据。 */
final class ChongSanchuanRule implements PanRule
{
    protected const METHOD = 'chong';

    protected const RULE_CODE = 'sanchuan.chong';

    protected const NAME = '冲神递取';

    protected const GROUP = '中末传取法';

    protected const DESCRIPTION = '中传取初传之冲神，末传再取中传之冲神。';

    protected const MARKER = '传';

    /** @var list<string> */
    protected const COVERAGE_AREAS = ['middle_transmission', 'final_transmission'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->sanchuanMethod('middle') !== self::METHOD || $facts->sanchuanMethod('final') !== self::METHOD) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            marker: self::MARKER,
            evidence: [
                'middle_method' => self::METHOD,
                'final_method' => self::METHOD,
            ],
            coverageAreas: self::COVERAGE_AREAS,
        );
    }
}
