<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义昴星课中阳日从地盘酉位取用的虎视格。 */
final readonly class HushiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'hushi';

    protected const NAME = '虎视格';

    protected const DESCRIPTION = '无克贼、无遥克且四课不重复，阳日取地盘酉上神为初传。';

    protected const MARKER = '格';

    protected const XIANG = null;

    protected function qualifies(PanFacts $facts): bool
    {
        return MaoxingRule::qualifies($facts);
    }
}
