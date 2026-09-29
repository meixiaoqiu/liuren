<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义涉害法见机格的命中标识、名称与解盘说明。 */
final readonly class JianjiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_jianji';

    protected const NAME = '见机格';

    protected const DESCRIPTION = '候选课涉害相等，取临四孟者为初传。';

    protected const MARKER = '格';

    protected const XIANG = '利涉大川，有孚贞吉。动作知机，不俟终日。名利难遂，胎孕未实。疑事急改，犹豫有失。';

    protected function qualifies(PanFacts $facts): bool
    {
        return ShehaiRule::qualifies($facts);
    }
}
