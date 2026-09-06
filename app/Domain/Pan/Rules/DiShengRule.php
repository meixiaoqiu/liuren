<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断三传递生日干所成的递生格（顺逆两向）。 */
final class DiShengRule extends HengtongGridRule
{
    protected const SLUG = 'di_sheng';

    protected const NAME = '递生格';

    protected const DESCRIPTION = '三传递生日干：初生中、中生末、末生日干，或末生中、中生初、初生日干。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::diShengDetail($facts);
    }
}
