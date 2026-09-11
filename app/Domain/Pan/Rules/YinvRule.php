<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：独立判断淫泆课篇所附泆女格，不以前置命中淫泆课为条件。 */
final class YinvRule implements PanRule
{
    public function code(): string
    {
        return 'structure.yinv';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $final = $facts->get('sanchuan2');
        if (! is_int($initial) || ! is_int($final)) {
            return null;
        }

        $initialGeneral = $facts->generalRidingBranch($initial);
        $finalGeneral = $facts->generalRidingBranch($final);
        if (! is_int($initialGeneral) || ! is_int($finalGeneral)) {
            return null;
        }

        if ($initialGeneral !== 11 || $finalGeneral !== 3) {
            return null;
        }

        $branch = static fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';

        return new RuleMatch(
            code: $this->code(),
            name: '泆女格',
            group: '淫泆课体',
            description: '初传乘天后，末传乘六合。',
            marker: '格',
            evidence: [
                'initial' => $initial,
                'initial_general' => $initialGeneral,
                'final' => $final,
                'final_general' => $finalGeneral,
                'foundations' => [
                    ['title' => '初传乘天后', 'detail' => '初传'.$branch($initial).'乘天后。'],
                    ['title' => '末传乘六合', 'detail' => '末传'.$branch($final).'乘六合。'],
                ],
                'judgments' => [],
                'uncovered' => [],
            ],
        );
    }
}
