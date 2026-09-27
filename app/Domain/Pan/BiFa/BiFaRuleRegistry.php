<?php

namespace App\Domain\Pan\BiFa;

use App\Domain\Pan\BiFa\Rules\BiNanTaoShengRule;
use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\BiFa\Rules\LianMuGuiRenRule;
use App\Domain\Pan\BiFa\Rules\LiuYangShuZuRule;
use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\BiFa\Rules\QuanSheBuZhengRule;
use App\Domain\Pan\BiFa\Rules\ShouWeiXiangJianRule;
use App\Domain\Pan\BiFa\Rules\WangLuLinShenRule;
use App\Extensions\BiFaExtensionRegistry;

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
 *  - QuanSheBuZhengRule          第八法 · 权摄不正禄临支
 *  - BiNanTaoShengRule           第九法 · 避难逃生须弃旧
 *
 * 第十法等后续法由 BiFaExtensionRegistry 注入；本类负责合并内置与扩展部分，
 * 并保证 code 不重复。BiFaRuleEngine 仍负责 duplicate code 兜底校验。
 *
 * 严禁把课经 PanRule 加入本注册表——课经与毕法是两套独立体系，
 * 混入会污染"解盘信息"与"毕法"两个独立区块。
 */
final class BiFaRuleRegistry
{
    public function __construct(private readonly BiFaExtensionRegistry $extensions) {}

    /** @return list<BiFaRule> */
    public function rules(): array
    {
        $builtIn = [
            new QianHouYinCongRule,
            new ShouWeiXiangJianRule,
            new LianMuGuiRenRule,
            new CuiGuanShiZheRule,
            new LiuYangShuZuRule,
            new LiuYinXiangJiRule,
            new WangLuLinShenRule,
            new QuanSheBuZhengRule,
            new BiNanTaoShengRule,
        ];

        $merged = [];
        $seen = [];
        foreach ([...$builtIn, ...$this->extensions->rules()] as $rule) {
            $code = $rule->code();
            if (isset($seen[$code])) {
                throw new \LogicException('Duplicate BiFa rule code: '.$code);
            }
            $seen[$code] = true;
            $merged[] = $rule;
        }

        return $merged;
    }
}
