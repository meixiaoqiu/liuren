<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：判断全局课寅午戌三合火局所成炎上格。 */
final class YanshangRule extends QuanjuGridRule
{
    protected const SLUG = 'yanshang';

    protected const NAME = '炎上格';

    protected const DESCRIPTION = '三传完整构成寅午戌火局。';
}
