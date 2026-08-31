<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断八专课是否有天后、六合或玄武入传而构成帷簿不修格。 */
final class WeibuBuxiuRule implements PanRule
{
    protected const CHUCHUAN_METHOD = 'bazhuan';

    protected const RULE_CODE = 'structure.weibu_buxiu';

    protected const NAME = '帷簿不修格';

    protected const GROUP = '三传格局';

    protected const DESCRIPTION = '八专课中天后、六合或玄武入传，阴阳共处而又逢阴私之将，构成帷簿不修格。';

    protected const MARKER = '格';

    protected const XIANG = null;

    /** @var array<int, string> */
    private const QUALIFYING_GENERALS = [
        3 => '六合',
        9 => '玄武',
        11 => '天后',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== self::CHUCHUAN_METHOD) {
            return null;
        }

        $matchedGenerals = [];

        foreach (range(0, 2) as $index) {
            $general = $facts->get("sanchuan{$index}tianjiang");

            if (is_int($general) && isset(self::QUALIFYING_GENERALS[$general])) {
                $matchedGenerals[] = [
                    'transmission' => $index,
                    'general' => $general,
                    'name' => self::QUALIFYING_GENERALS[$general],
                ];
            }
        }

        if ($matchedGenerals === []) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            marker: self::MARKER,
            xiang: self::XIANG,
            evidence: ['matched_generals' => $matchedGenerals],
        );
    }
}
