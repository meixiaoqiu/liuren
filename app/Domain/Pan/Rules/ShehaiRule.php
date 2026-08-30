<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义涉害课的命中标识、名称与解盘说明。 */
final readonly class ShehaiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai';

    protected const NAME = '涉害课';

    protected const DESCRIPTION = '候选课涉害深浅不同，取涉害较深者为初传。';
}
