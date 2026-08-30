<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义八专课的命中标识、名称与解盘说明。 */
final readonly class BazhuanRule extends ChuchuanMethodRule
{
    protected const METHOD = 'bazhuan';

    protected const NAME = '八专课';

    protected const DESCRIPTION = '干支同位、四课实际只有两课且无克贼，按日干阴阳进退取初传。';
}
