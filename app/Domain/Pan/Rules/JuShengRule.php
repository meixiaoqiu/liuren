<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断干上生干、支上生支所成的俱生格。 */
final class JuShengRule extends HengtongGridRule
{
    protected const SLUG = 'ju_sheng';

    protected const NAME = '俱生格';

    protected const DESCRIPTION = '干上神生日干、支上神生日支。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::juShengDetail($facts);
    }
}
