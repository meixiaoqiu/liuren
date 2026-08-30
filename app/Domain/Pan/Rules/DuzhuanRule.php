<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟杜传课及其三传覆盖范围。 */
final readonly class DuzhuanRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_duzhuan';

    protected const RULE_CODE = 'lesson.fuyin_duzhuan';

    protected const NAME = '杜传课';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼，初传又遇自刑，改取干上神与支上神递用，再依刑冲定末传。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
