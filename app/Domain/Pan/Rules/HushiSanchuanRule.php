<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义虎视格中传、末传的专用取法与说明。 */
final readonly class HushiSanchuanRule extends SanchuanMethodRule
{
    protected const METHOD = 'hushi_sanchuan';

    protected const RULE_CODE = 'hushi';

    protected const NAME = '虎视中末传';

    protected const DESCRIPTION = '虎视格中传取支上神，末传取干上神。';
}
