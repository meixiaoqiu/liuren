<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断夫妻行年俱立旺相三合之乡所成的旺孕格。 */
final class WangYunRule extends FanchangGridRule
{
    protected const SLUG = 'wang_yun';

    protected const NAME = '旺孕格';

    protected const DESCRIPTION = '夫妻行年地支三合，且俱得季节旺相。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::wangYunDetail($facts);
    }
}
