<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断八专课是否同时构成三传皆同的独足格。 */
final class DuzuRule implements PanRule
{
    protected const CHUCHUAN_METHOD = 'bazhuan';

    protected const RULE_CODE = 'structure.duzu';

    protected const NAME = '独足格';

    protected const GROUP = '三传格局';

    protected const DESCRIPTION = '八专课中初传、中传和末传归于同一神，同时成独足格。';

    protected const MARKER = '格';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== self::CHUCHUAN_METHOD) {
            return null;
        }

        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        if (! array_is_list($transmissions)
            || array_filter($transmissions, 'is_int') !== $transmissions
            || count(array_unique($transmissions, SORT_REGULAR)) !== 1) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            marker: self::MARKER,
            evidence: ['transmission' => $transmissions[0]],
        );
    }
}
