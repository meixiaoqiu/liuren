<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：判断全局课申子辰三合水局所成润下格。 */
final class RunxiaRule extends QuanjuGridRule
{
    protected const SLUG = 'runxia';
    protected const NAME = '润下格';
    protected const DESCRIPTION = '三传完整构成申子辰水局。';
}
