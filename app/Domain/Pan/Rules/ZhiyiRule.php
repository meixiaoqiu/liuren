<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义知一课的命中标识、名称与解盘说明。 */
final readonly class ZhiyiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'zhiyi';

    protected const NAME = '知一课';

    protected const DESCRIPTION = '无下贼上而有多处上克下，取与日干阴阳相比者为初传。';
}
