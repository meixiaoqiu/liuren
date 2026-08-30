<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义涉害法见机格的命中标识、名称与解盘说明。 */
final readonly class JianjiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_jianji';

    protected const NAME = '见机格';

    protected const DESCRIPTION = '候选课涉害相等，取临四孟者为初传。';
}
