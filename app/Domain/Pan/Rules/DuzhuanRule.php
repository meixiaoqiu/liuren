<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟课中发用自刑、传行杜塞所成的杜传格。 */
final readonly class DuzhuanRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_duzhuan';

    protected const RULE_CODE = 'lesson.fuyin_duzhuan';

    protected const NAME = '杜传格';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼，初传又遇自刑，改取干上神与支上神递用，再依刑冲定末传。';

    protected const MARKER = '格';

    protected const XIANG = '居者将移，合者将离。道由中止，事宜改为。传阳人至，传阴未归。占人求物，不出庭除。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
