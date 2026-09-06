<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断干上为支旺神、支上为干旺神所成的互旺格。 */
final class HuWangRule extends HengtongGridRule
{
    protected const SLUG = 'hu_wang';

    protected const NAME = '互旺格';

    protected const DESCRIPTION = '干上神为日支之旺神、支上神为日干之旺神。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::huWangDetail($facts);
    }
}
