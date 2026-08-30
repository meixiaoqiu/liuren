<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：定义六十四课涉害课，涵盖涉害本课及见机、察微、缀瑕子格。 */
final class ShehaiRule implements PanRule
{
    /** @var list<string> */
    protected const METHODS = ['shehai', 'shehai_jianji', 'shehai_chawei', 'shehai_zhuixia'];

    protected const RULE_CODE = 'selection.shehai';

    protected const NAME = '涉害课';

    protected const GROUP = '初传取法';

    protected const DESCRIPTION = '四课中克贼不止一处，依涉害法比较候选课，逐层取定初传。';

    protected const GUA = '坎';

    protected const GUA_SYMBOL = '䷜';

    protected const XIANG = '风波险恶，度涉艰难。谋为利名，多费机关。婚姻有阻，疾病难安。胎孕迟滞，行人未还。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $method = $facts->chuchuanMethod();

        if (! self::qualifies($facts)) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                ...$facts->chuchuanEvidence(),
                'method' => $method,
            ],
            coverageAreas: ['initial_transmission'],
        );
    }

    public static function qualifies(PanFacts $facts): bool
    {
        return ! $facts->hasPlatePattern('fanyin')
            && in_array($facts->chuchuanMethod(), self::METHODS, true);
    }
}
