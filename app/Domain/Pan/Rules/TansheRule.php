<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义弹射课的命中标识、名称与解盘说明。 */
final readonly class TansheRule extends ChuchuanMethodRule
{
    protected const METHOD = 'tanshe';

    protected const NAME = '弹射课';

    protected const DESCRIPTION = '四课无直接克贼，而日干遥克上神，取被日干遥克之神为初传。';
}
