<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为繁昌课下的德孕、旺孕两格提供公共骨架。 */
abstract class FanchangGridRule implements PanRule
{
    use FanchangSupport;

    protected const SLUG = '';

    protected const NAME = '';

    protected const DESCRIPTION = '';

    protected const GROUP = '繁昌课体';

    protected const MARKER = '格';

    public function code(): string
    {
        return 'structure.'.static::SLUG;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $detail = $this->evaluate($facts);

        if ($detail === null) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: static::GROUP,
            description: static::DESCRIPTION,
            marker: static::MARKER,
            evidence: ['detail' => $detail],
        );
    }

    /** @return string|null 成格说明，未命中返回 null。 */
    abstract protected function evaluate(PanFacts $facts): ?string;
}
