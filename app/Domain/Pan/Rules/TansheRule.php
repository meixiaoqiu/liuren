<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义遥克课中日干遥克上神的弹射格。 */
final readonly class TansheRule extends ChuchuanMethodRule
{
    protected const METHOD = 'tanshe';

    protected const NAME = '弹射格';

    protected const DESCRIPTION = '四课无直接克贼，而日干遥克上神，取被日干遥克之神为初传。';

    protected const MARKER = '格';

    protected const XIANG = '已谋他事，祸从内施，兵用客利，事宜后为。';

    protected function qualifies(PanFacts $facts): bool
    {
        return YaokeRule::qualifies($facts);
    }
}
