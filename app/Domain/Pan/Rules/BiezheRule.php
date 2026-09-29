<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义别责课的命中标识、名称与解盘说明。 */
final readonly class BiezheRule extends ChuchuanMethodRule
{
    protected const METHOD = 'biezhe';

    protected const NAME = '别责课';

    protected const DESCRIPTION = '四课不全而又不成八专，依日干阴阳从干合上神或支前三合神取初传。';

    protected const XIANG = '谋为处正，财物不全。临兵选将，欲渡寻船。求婚别娶，胎孕多延。损而能益，事遇神仙。';
}
