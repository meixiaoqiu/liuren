<?php

namespace App\Domain\Pan\BiFa;

use App\Extensions\BiFaExtensionRegistry;

/**
 * 文件作用：登记毕法规则引擎每次起盘需要执行的具体规则。
 *
 * 所有具体毕法由 BiFaExtensionRegistry 注入；本类负责读取扩展部分，
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
        $merged = [];
        $seen = [];
        foreach ($this->extensions->rules() as $rule) {
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
