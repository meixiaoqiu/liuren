<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为仅按计算轨迹中的初传方法匹配的具体课格提供公共机制。 */
abstract readonly class ChuchuanMethodRule implements PanRule
{
    protected const METHOD = '';

    protected const NAME = '';

    protected const DESCRIPTION = '';

    protected const GROUP = '初传取法';

    protected const MARKER = '课';

    protected const GUA = null;

    protected const GUA_SYMBOL = null;

    protected const XIANG = null;

    /** @var list<string> */
    protected const COVERAGE_AREAS = ['initial_transmission'];

    /** @var list<string> */
    protected const EXCLUDED_PLATE_PATTERNS = [];

    public function code(): string
    {
        return 'selection.'.static::METHOD;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        foreach (static::EXCLUDED_PLATE_PATTERNS as $pattern) {
            if ($facts->hasPlatePattern($pattern)) {
                return null;
            }
        }

        if ($facts->chuchuanMethod() !== static::METHOD || ! $this->qualifies($facts)) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: static::GROUP,
            description: static::DESCRIPTION,
            marker: static::MARKER,
            gua: static::GUA,
            guaSymbol: static::GUA_SYMBOL,
            xiang: static::XIANG,
            evidence: $facts->chuchuanEvidence(),
            coverageAreas: static::COVERAGE_AREAS,
        );
    }

    protected function qualifies(PanFacts $facts): bool
    {
        return true;
    }
}
