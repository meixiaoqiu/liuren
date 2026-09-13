<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：让依赖有界外部事实的规则在资料不足时报告“未评估”，区别于条件不成立。 */
interface ConditionalEvaluationRule
{
    /** @return array{name: string, notice: string}|null */
    public function evaluationIssue(PanFacts $facts): ?array;
}
