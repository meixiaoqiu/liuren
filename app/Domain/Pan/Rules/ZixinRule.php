<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：定义伏吟课中无克阴日所成的自信格。 */
final readonly class ZixinRule extends LessonPatternRule
{
    protected const PATTERN = 'fuyin_zixin';

    protected const RULE_CODE = 'lesson.fuyin_zixin';

    protected const NAME = '自信格';

    protected const GROUP = '伏吟课体';

    protected const DESCRIPTION = '伏吟无克贼而为阴日，取支上神为初传，再按伏吟刑冲法取中末传。';

    protected const MARKER = '格';

    protected const XIANG = '潜藏伏匿，身不自由。逃亡近觅，盗贼内搜。病人喑哑，行者淹留。检身谨恪，无不优悠。';

    protected const COVERAGE_AREAS = ['initial_transmission', 'middle_transmission', 'final_transmission'];
}
