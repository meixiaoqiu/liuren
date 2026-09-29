<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义元首课的命中标识、名称与解盘说明。 */
final readonly class YuanshouRule extends ChuchuanMethodRule
{
    protected const METHOD = 'yuanshou';

    protected const NAME = '元首课';

    protected const DESCRIPTION = '四课中只有一处上克下，取克下之上神为初传。';

    protected const GUA = '乾';

    protected const GUA_SYMBOL = '䷀';

    protected const XIANG = '天地得位，品物咸新。事用君子，忧喜俱真。君臣和合，父子慈亲。婚谐鸾凤，孕育麒麟。用兵客胜，论讼先陈。市贾出色，各利超群。官职首擢，柱石元勋。门庭喜溢，利见大人。';

    protected const EXCLUDED_PLATE_PATTERNS = ['fanyin'];
}
