<?php

namespace App\Domain\Pan\Rules;

/**
 * 文件作用：识别中传、末传依天盘逐次顺取的三传规则，并返回相应说明。
 */
final readonly class TianpanShunchuanRule extends SanchuanMethodRule
{
    protected const METHOD = 'tianpan_shunchuan';

    protected const RULE_CODE = 'tianpan_shunchuan';

    protected const NAME = '天盘顺传';

    protected const DESCRIPTION = '中传取初传在天盘上所临之神，末传再取中传在天盘上所临之神。';
}
