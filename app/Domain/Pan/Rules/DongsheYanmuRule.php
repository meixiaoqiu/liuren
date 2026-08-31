<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义昴星课中阴日从天盘酉位取用的冬蛇掩目格。 */
final readonly class DongsheYanmuRule extends ChuchuanMethodRule
{
    protected const METHOD = 'dongshe_yanmu';

    protected const NAME = '冬蛇掩目格';

    protected const DESCRIPTION = '无克贼、无遥克且四课不重复，阴日取天盘酉下神为初传。';

    protected const MARKER = '格';

    protected const XIANG = '人情失意，进退无凭。女多淫泆，内有忧惊。访人不见，作事难成。行者淹滞，逃亡隐形。';

    protected function qualifies(PanFacts $facts): bool
    {
        return MaoxingRule::qualifies($facts);
    }
}
