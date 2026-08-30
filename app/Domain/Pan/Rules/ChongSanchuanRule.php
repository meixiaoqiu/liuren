<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：识别中传、末传逐次取冲神的三传规则，并返回命中证据。 */
final class ChongSanchuanRule implements PanRule
{
    public function code(): string
    {
        return 'sanchuan.chong';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if ($facts->sanchuanMethod('middle') !== 'chong' || $facts->sanchuanMethod('final') !== 'chong') {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: '冲神递取',
            group: '中末传取法',
            description: '中传取初传之冲神，末传再取中传之冲神。',
            evidence: [
                'middle_method' => 'chong',
                'final_method' => 'chong',
            ],
            coverageAreas: ['middle_transmission', 'final_transmission'],
        );
    }
}
