<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：判断全局课三传全部属于辰戌丑未四季土所成稼穑格。 */
final class JiaseRule extends QuanjuGridRule
{
    protected const SLUG = 'jiase';

    protected const NAME = '稼穑格';

    protected const DESCRIPTION = '三传全部属于辰、戌、丑、未四季土。';
}
