<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：识别返吟无亲课及其规定的初传、中传、末传取法。 */
final class FanyinWuqinRule implements PanRule
{
    public function code(): string
    {
        return 'selection.fanyin_wuqin';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->chuchuanMethod() !== 'fanyin_wuqin'
            || $facts->sanchuanMethod('middle') !== 'fanyin_wuqin'
            || $facts->sanchuanMethod('final') !== 'fanyin_wuqin') {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: '无亲课',
            group: '特殊取传',
            description: '返吟中的特殊日课，不依一般冲神递取，按规定神位取初、中、末传。',
            coverageAreas: ['initial_transmission', 'middle_transmission', 'final_transmission'],
        );
    }
}
