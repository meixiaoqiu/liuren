<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断天地盘是否构成伏吟，并生成伏吟盘局说明。 */
final class FuyinRule implements PanRule
{
    public function code(): string
    {
        return 'plate.fuyin';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if (! $facts->hasPlatePattern('fuyin')) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: '伏吟',
            group: '天地盘格局',
            description: '天盘与地盘各宫相同，盘局属于伏吟。',
            evidence: ['plate_pattern' => 'fuyin'],
            coverageAreas: ['plate_pattern'],
        );
    }
}
