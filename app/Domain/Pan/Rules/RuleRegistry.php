<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：登记规则引擎每次起盘需要执行的全部具体规则。 */
final class RuleRegistry
{
    /** @return list<PanRule> */
    public function rules(): array
    {
        return [
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
            new SanguangRule,
            new SanyangRule,
            new SanqiRule,
            new LiuyiRule,
            new ShitaiRule,
            new LongdeRule,
        ];
    }
}
