<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为按计算轨迹中的课体标识匹配的具体课体规则提供公共机制。 */
abstract readonly class LessonPatternRule implements PanRule
{
    protected const PATTERN = '';

    protected const RULE_CODE = '';

    protected const NAME = '';

    protected const GROUP = '';

    protected const DESCRIPTION = '';

    protected const MARKER = '课';

    protected const XIANG = null;

    /** @var list<string> */
    protected const COVERAGE_AREAS = [];

    public function code(): string
    {
        return static::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if (! $facts->hasLessonPattern(static::PATTERN)) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: static::GROUP,
            description: static::DESCRIPTION,
            marker: static::MARKER,
            xiang: static::XIANG,
            evidence: ['lesson_pattern' => static::PATTERN],
            coverageAreas: static::COVERAGE_AREAS,
        );
    }
}
