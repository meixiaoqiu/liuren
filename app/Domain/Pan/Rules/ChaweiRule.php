<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义涉害法察微格的命中标识、名称与解盘说明。 */
final readonly class ChaweiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_chawei';

    protected const NAME = '察微格';

    protected const DESCRIPTION = '候选课涉害相等且不临四孟，取临四仲者为初传。';

    protected const MARKER = '格';

    protected const XIANG = '笑中有刀，蜜中有砒。大人利见，旧德微施，人情浅泊，世事难披，防范机密，物欲必齐。';

    protected function qualifies(PanFacts $facts): bool
    {
        return ShehaiRule::qualifies($facts);
    }
}
