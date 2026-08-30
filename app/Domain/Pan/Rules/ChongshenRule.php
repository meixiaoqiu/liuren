<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义重审课的命中标识、名称与解盘说明。 */
final readonly class ChongshenRule extends ChuchuanMethodRule
{
    protected const METHOD = 'chongshen';

    protected const NAME = '重审课';

    protected const DESCRIPTION = '四课中只有一处下贼上，取受贼之上神为初传。';
}
