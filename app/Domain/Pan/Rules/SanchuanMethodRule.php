<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为中传、末传采用同一方法标识的具体三传规则提供公共机制。 */
abstract readonly class SanchuanMethodRule implements PanRule
{
    protected const METHOD = '';

    protected const RULE_CODE = '';

    protected const NAME = '';

    protected const DESCRIPTION = '';

    public function code(): string
    {
        return 'sanchuan.'.static::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->sanchuanMethod('middle') !== static::METHOD || $facts->sanchuanMethod('final') !== static::METHOD) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: '中末传取法',
            description: static::DESCRIPTION,
            evidence: [
                'middle_method' => static::METHOD,
                'final_method' => static::METHOD,
            ],
            coverageAreas: ['middle_transmission', 'final_transmission'],
        );
    }
}
