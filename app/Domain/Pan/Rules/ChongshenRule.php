<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义重审课的命中标识、名称与解盘说明。 */
final readonly class ChongshenRule extends ChuchuanMethodRule
{
    protected const METHOD = 'chongshen';

    protected const NAME = '重审课';

    protected const DESCRIPTION = '四课中只有一处下贼上，取受贼之上神为初传。';

    protected const GUA = '坤';

    protected const GUA_SYMBOL = '䷁';

    protected const XIANG = '顺天厚载，柔顺利贞。一下逆上，岂无忧惊。贵顺福至，贵逆乱兴。事宜后起，祸从内生。用兵主胜，受孕女形。诸般谋望，先难后成。';

    protected const EXCLUDED_PLATE_PATTERNS = ['fanyin'];
}
