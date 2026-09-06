<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断干上为干旺神、支上为支旺神所成的俱旺格。 */
final class JuWangRule extends HengtongGridRule
{
    protected const SLUG = 'ju_wang';

    protected const NAME = '俱旺格';

    protected const DESCRIPTION = '干上神为日干之旺神、支上神为日支之旺神。';

    protected function evaluate(PanFacts $facts): ?string
    {
        return self::juWangDetail($facts);
    }
}
