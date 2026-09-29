<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义八专课的命中标识、名称与解盘说明。 */
final readonly class BazhuanRule extends ChuchuanMethodRule
{
    protected const METHOD = 'bazhuan';

    protected const NAME = '八专课';

    protected const DESCRIPTION = '干支同位、四课实际只有两课且无克贼，按日干阴阳进退取初传。';

    protected const GUA = '同人';

    protected const GUA_SYMBOL = '䷌';

    protected const XIANG = '二人同心，其利断金。阳进男喜，阴进女淫。兵资众揵，物失内寻。成功异路，显擢士林。';
}
