<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义元首课的命中标识、名称与解盘说明。 */
final readonly class YuanshouRule extends ChuchuanMethodRule
{
    protected const METHOD = 'yuanshou';

    protected const NAME = '元首课';

    protected const DESCRIPTION = '四课中只有一处上克下，取克下之上神为初传。';
}
