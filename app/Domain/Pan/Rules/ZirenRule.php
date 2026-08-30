<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟自任课及其三传覆盖范围。 */
final readonly class ZirenRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_ziren';

    protected const RULE_CODE = 'lesson.fuyin_ziren';

    protected const NAME = '自任课';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼而为阳日，取干上神为初传，再按伏吟刑冲法取中末传。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
