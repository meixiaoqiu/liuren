<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟不虞课及其三传覆盖范围。 */
final readonly class BuyuRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_buyu';

    protected const RULE_CODE = 'lesson.fuyin_buyu';

    protected const NAME = '不虞课';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟有克贼，先依克贼取初传，再按伏吟刑冲法取中末传。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
