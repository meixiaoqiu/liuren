<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟自信课及其三传覆盖范围。 */
final readonly class ZixinRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_zixin';

    protected const RULE_CODE = 'lesson.fuyin_zixin';

    protected const NAME = '自信课';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼而为阴日，取支上神为初传，再按伏吟刑冲法取中末传。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
