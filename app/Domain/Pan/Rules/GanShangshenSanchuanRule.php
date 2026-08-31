<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：识别中传、末传均归于干上神的三传规则，并返回相应说明。 */
final readonly class GanShangshenSanchuanRule extends SanchuanMethodRule
{
    protected const METHOD = 'gan_shangshen';

    protected const RULE_CODE = 'gan_shangshen';

    protected const NAME = '中末传归干上神';

    protected const DESCRIPTION = '中传和末传均取干上神。';
}
