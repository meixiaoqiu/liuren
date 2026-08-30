<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义涉害法察微格的命中标识、名称与解盘说明。 */
final readonly class ChaweiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_chawei';

    protected const NAME = '察微格';

    protected const DESCRIPTION = '候选课涉害相等且不临四孟，取临四仲者为初传。';
}
