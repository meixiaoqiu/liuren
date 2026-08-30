<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义比用课的命中标识、名称与解盘说明。 */
final readonly class BiyongRule extends ChuchuanMethodRule
{
    protected const METHOD = 'biyong';

    protected const NAME = '比用课';

    protected const DESCRIPTION = '下贼上不止一处，取与日干阴阳相比者为初传。';
}
