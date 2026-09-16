<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：判断全局课亥卯未三合木局所成曲直格。 */
final class QuzhiRule extends QuanjuGridRule
{
    protected const SLUG = 'quzhi';
    protected const NAME = '曲直格';
    protected const DESCRIPTION = '三传完整构成亥卯未木局。';
}
