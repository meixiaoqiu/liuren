<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为仅按计算轨迹中的初传方法匹配的具体课格提供公共机制。 */
abstract readonly class ChuchuanMethodRule implements PanRule
{
    protected const METHOD = '';

    protected const NAME = '';

    protected const DESCRIPTION = '';

    public function code(): string
    {
        return 'selection.'.static::METHOD;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== static::METHOD) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: '初传取法',
            description: static::DESCRIPTION,
            evidence: $facts->chuchuanEvidence(),
            coverageAreas: ['initial_transmission'],
        );
    }
}
