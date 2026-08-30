<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断天地盘是否构成返吟，并生成返吟盘局说明。 */
final class FanyinRule implements PanRule
{
    public function code(): string
    {
        return 'plate.fanyin';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        if (! $facts->hasPlatePattern('fanyin')) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: '返吟',
            group: '天地盘格局',
            description: '天盘与地盘各宫相冲，盘局属于返吟。',
            evidence: ['plate_pattern' => 'fanyin'],
            coverageAreas: ['plate_pattern'],
        );
    }
}
