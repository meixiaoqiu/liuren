<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义冬蛇掩目课及其初传取法的命中标识与说明。 */
final readonly class DongsheYanmuRule extends ChuchuanMethodRule
{
    protected const METHOD = 'dongshe_yanmu';

    protected const NAME = '冬蛇掩目课';

    protected const DESCRIPTION = '无克贼、无遥克且四课不重复，阴日取天盘酉下神为初传。';
}
