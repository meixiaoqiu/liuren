<?php

namespace App\Domain\Pan\BiFa;

use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;

/**
 * 文件作用：登记毕法规则引擎每次起盘需要执行的具体规则。
 *
 * 当前仅注册 QianHouYinCongRule。后续第 2..100 法的实现逐步追加，
 * 严禁把课经 PanRule 加入本注册表——课经与毕法是两套独立体系，
 * 混入会污染"解盘信息"与"毕法"两个独立区块。
 */
final class BiFaRuleRegistry
{
    /** @return list<BiFaRule> */
    public function rules(): array
    {
        return [
            new QianHouYinCongRule,
        ];
    }
}
