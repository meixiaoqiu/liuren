<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义返吟无依课的命中标识、名称与解盘说明。 */
final readonly class WuyiRule extends LessonPatternRule
{
    protected const PATTERN = 'fanyin_wuyi';

    protected const RULE_CODE = 'lesson.fanyin_wuyi';

    protected const NAME = '无依课';

    protected const GROUP = '返吟课体';

    protected const DESCRIPTION = '返吟而不属于四个无亲日，初传仍依实际克贼、比用或涉害等法选取，中末传以冲神递取。';
}
