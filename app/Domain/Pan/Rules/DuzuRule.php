<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断八专课是否同时构成三传皆同的独足格。 */
final class DuzuRule implements PanRule
{
    public function code(): string
    {
        return 'structure.duzu';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== 'bazhuan') {
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
            name: '独足格',
            group: '三传格局',
            description: '八专课中初传、中传和末传归于同一神，同时成独足格。',
            evidence: ['transmission' => $transmissions[0]],
        );
    }
}
