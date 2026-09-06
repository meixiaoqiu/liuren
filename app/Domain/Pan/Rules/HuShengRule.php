<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断干上生支、支上生干所成的互生格。 */
final class HuShengRule extends HengtongGridRule
{
    protected const SLUG = 'hu_sheng';

    protected const NAME = '互生格';

    protected const DESCRIPTION = '干上神生日支、支上神生日干。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::huShengDetail($facts);
    }
}
