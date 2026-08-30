<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义虎视课及其初传取法的命中标识与说明。 */
final readonly class HushiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'hushi';

    protected const NAME = '虎视课';

    protected const DESCRIPTION = '无克贼、无遥克且四课不重复，阳日取地盘酉上神为初传。';
}
