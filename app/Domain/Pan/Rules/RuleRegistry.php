<?php

namespace App\Domain\Pan\Rules;

use App\Extensions\KeJingExtensionRegistry;
use LogicException;

/** 文件作用：登记规则引擎每次起盘需要执行的全部具体规则。 */
final class RuleRegistry
{
    public function __construct(private readonly ?KeJingExtensionRegistry $extensions = null) {}

    /** @return list<PanRule> */
    public function rules(): array
    {
        $coreRules = [
            new FanyinRule,
            new FuyinRule,
            new ZirenRule,
            new ZixinRule,
            new DuzhuanRule,
            new YuanshouRule,
            new ChongshenRule,
            new ZhiyiRule,
            new ShehaiRule,
            new JianjiRule,
            new ChaweiRule,
            new ZhuixiaRule,
            new YaokeRule,
            new HaoshiRule,
            new TansheRule,
            new MaoxingRule,
            new HushiRule,
            new DongsheYanmuRule,
            new BiezheRule,
            new BazhuanRule,
            new ChongSanchuanRule,
            new TianpanShunchuanRule,
            new HushiSanchuanRule,
            new DongsheYanmuSanchuanRule,
            new GanShangshenSanchuanRule,
            new JinglanRule,
            new DuzuRule,
            new WeibuBuxiuRule,
        ];

        $merged = [...$coreRules, ...($this->extensions?->rules() ?? [])];
        $codes = [];
        foreach ($merged as $rule) {
            if (isset($codes[$rule->code()])) {
                throw new LogicException('Duplicate Pan rule code: '.$rule->code());
            }

            $codes[$rule->code()] = true;
        }

        return $merged;
    }
}
