<?php

namespace App\Domain\Pan\BiFa;

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use LogicException;

/**
 * 文件作用：逐一执行注册的毕法规则、收集全部命中结果。
 *
 * 与 PanRuleEngine 不同：
 *  - 本引擎不会因为"上下文缺失"而跳过规则——毕法允许单条规则的多个分格
 *    各自独立给出"未评估"标记；
 *  - 本引擎不会输出 coverageNotices / notEvaluated 通用提示，
 *    缺人资料的提示直接由各 BiFaRule 在 subMatches 内自带 people_missing 标记。
 */
final readonly class BiFaRuleEngine
{
    public function __construct(private BiFaRuleRegistry $registry = new BiFaRuleRegistry) {}

    /** @return list<BiFaRuleMatch> */
    public function evaluate(PanResult $pan): array
    {
        $facts = PanFacts::from($pan);
        $matches = [];
        $registeredCodes = [];

        foreach ($this->registry->rules() as $rule) {
            if (isset($registeredCodes[$rule->code()])) {
                throw new LogicException('Duplicate BiFa rule code: ' . $rule->code());
            }

            $registeredCodes[$rule->code()] = true;

            $match = $rule->match($facts);
            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
    }
}