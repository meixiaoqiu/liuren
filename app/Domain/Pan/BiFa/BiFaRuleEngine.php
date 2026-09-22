<?php

namespace App\Domain\Pan\BiFa;

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use LogicException;

/**
 * 文件作用：逐一执行注册的毕法规则、收集应当进入排盘结果的命中。
 *
 * 正常排盘只返回：
 *
 *  - 至少一个分格命中的 BiFaRule；；
 *  - 或所有分格均不命中、但存在至少一个满足前置条件、仅因人物资料缺失而无法判断的 route
 *    ——这种情况下 BiFaRuleMatch 仍返回，前台展示为"待评估"。
 *
 * 全部 route 均不命中且无待评估路线时，BiFaRule::match() 必须返回 null——
 * 不进入排盘页面。这是为了 100 法全部实现后，排盘页只显示真正相关的毕法，
 * 而不是把"100 法全部未命中"也展示一长串卡片。
 *
 * 未来如需"100 法全判定"做开发调试，请单独引入 BiFaRuleEngine::evaluateAll()，
 * 不要污染正常排盘结果。
 */
final class BiFaRuleEngine
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
                throw new LogicException('Duplicate BiFa rule code: '.$rule->code());
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
