<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义涉害法缀瑕格的命中标识、名称与解盘说明。 */
final readonly class ZhuixiaRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_zhuixia';

    protected const NAME = '缀瑕格';

    protected const DESCRIPTION = '候选课涉害相等且不临孟仲，依日干阴阳取干上神或支上神为初传。';
}
