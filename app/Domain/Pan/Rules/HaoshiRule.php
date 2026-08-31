<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义遥克课中上神遥克日干的蒿矢格。 */
final readonly class HaoshiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'haoshi';

    protected const NAME = '蒿矢格';

    protected const DESCRIPTION = '四课无直接克贼，而有上神遥克日干，取遥克日干之神为初传。';

    protected const MARKER = '格';

    protected const XIANG = '神随遥克，力弱难伤，不能为害，如折蒿为矢。';

    protected function qualifies(PanFacts $facts): bool
    {
        return YaokeRule::qualifies($facts);
    }
}
