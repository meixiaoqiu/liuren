<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：规定所有排盘过滤规则必须提供唯一编码和匹配结果。 */
interface PanRule
{
    public function code(): string;

    public function match(PanFacts $facts): ?RuleMatch;
}
