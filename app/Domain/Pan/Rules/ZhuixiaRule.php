<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义涉害法缀瑕格的命中标识、名称与解盘说明。 */
final readonly class ZhuixiaRule extends ChuchuanMethodRule
{
    protected const METHOD = 'shehai_zhuixia';

    protected const NAME = '缀瑕格';

    protected const DESCRIPTION = '候选课涉害相等，且同属孟、仲或季仍不能决胜，依日干阴阳取干上神或支上神为初传。';

    protected const MARKER = '格';

    protected const XIANG = '两雄交争，经延岁月，人众牵连，灾耗不绝。君子宜亲，小人可缀，胎孕逾期，行人失缺。';

    protected function qualifies(PanFacts $facts): bool
    {
        return ShehaiRule::qualifies($facts);
    }
}
