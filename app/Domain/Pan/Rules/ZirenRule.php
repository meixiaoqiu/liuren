<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟课中无克阳日所成的自任格。 */
final readonly class ZirenRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_ziren';

    protected const RULE_CODE = 'lesson.fuyin_ziren';

    protected const NAME = '自任格';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼而为阳日，取干上神为初传，再按伏吟刑冲法取中末传。';

    protected const MARKER = '格';

    protected const XIANG = '任己刚暴，必成过愆。行人近至，逃亡眼前。胎孕哑聋，祸患留连。干谒不出，株守吉言。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
