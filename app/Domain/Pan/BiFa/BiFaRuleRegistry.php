<?php

namespace App\Domain\Pan\BiFa;

use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\BiFa\Rules\LianMuGuiRenRule;
use App\Domain\Pan\BiFa\Rules\LiuYangShuZuRule;
use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\BiFa\Rules\ShouWeiXiangJianRule;
use App\Domain\Pan\BiFa\Rules\WangLuLinShenRule;

/**
 * 文件作用：登记毕法规则引擎每次起盘需要执行的具体规则。
 *
 * 已注册规则（按法号升序）：
 *
 *  - QianHouYinCongRule          第一法 · 前后引从升迁吉
 *  - ShouWeiXiangJianRule        第二法 · 首尾相见始终宜
 *  - LianMuGuiRenRule            第三法 · 帘幕贵人高甲第
 *  - CuiGuanShiZheRule           第四法 · 催官使者赴官期
 *  - LiuYangShuZuRule            第五法 · 六阳数足须公用
 *  - LiuYinXiangJiRule           第六法 · 六阴相继尽昏迷
 *  - WangLuLinShenRule           第七法 · 旺禄临身徒妄作
 *
 * 后续第 8..100 法的实现逐步追加；严禁把课经 PanRule 加入本注册表——
 * 课经与毕法是两套独立体系，混入会污染"解盘信息"与"毕法"两个独立区块。
 */
final class BiFaRuleRegistry
{
    /** @return list<BiFaRule> */
    public function rules(): array
    {
        return [
            new QianHouYinCongRule,
            new ShouWeiXiangJianRule,
            new LianMuGuiRenRule,
            new CuiGuanShiZheRule,
            new LiuYangShuZuRule,
            new LiuYinXiangJiRule,
            new WangLuLinShenRule,
        ];
    }
}
