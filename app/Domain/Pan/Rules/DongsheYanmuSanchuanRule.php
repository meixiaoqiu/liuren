<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义冬蛇掩目格中传、末传的专用取法与说明。 */
final readonly class DongsheYanmuSanchuanRule extends SanchuanMethodRule
{
    protected const METHOD = 'dongshe_yanmu_sanchuan';

    protected const RULE_CODE = 'dongshe_yanmu';

    protected const NAME = '冬蛇掩目中末传';

    protected const DESCRIPTION = '冬蛇掩目格中传取干上神，末传取支上神。';
}
