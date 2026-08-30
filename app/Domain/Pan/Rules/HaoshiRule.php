<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义蒿矢课的命中标识、名称与解盘说明。 */
final readonly class HaoshiRule extends ChuchuanMethodRule
{
    protected const METHOD = 'haoshi';

    protected const NAME = '蒿矢课';

    protected const DESCRIPTION = '四课无直接克贼，而有上神遥克日干，取遥克日干之神为初传。';
}
