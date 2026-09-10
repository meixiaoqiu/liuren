<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：判断《六壬大全》闭口课篇所附一旬周遍格；本格独立成立，不要求闭口课先成立。 */
final class YixunZhoubianRule implements PanRule
{
    public function code(): string
    {
        return 'structure.yixun_zhoubian';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $xunHead = $facts->dayXunHeadBranch();
        $tianpan = $facts->get('tianpan');
        $stemLodging = is_int($dayStem) ? $facts->stemLodgingBranch($dayStem) : null;

        if (! is_int($dayBranch) || ! is_int($xunHead) || ! is_int($stemLodging) || ! is_array($tianpan)
            || ! isset($tianpan[$stemLodging], $tianpan[$dayBranch])
            || ! is_int($tianpan[$stemLodging]) || ! is_int($tianpan[$dayBranch])) {
            return null;
        }

        $xunTail = ($xunHead + 9) % 12;
        $stemUpper = $tianpan[$stemLodging];
        $branchUpper = $tianpan[$dayBranch];

        if ($stemUpper !== $xunTail || $branchUpper !== $xunHead) {
            return null;
        }

        $branch = static fn (int $index): string => PanCalculator::$dizhi[$index] ?? '未知地支';

        return new RuleMatch(
            code: $this->code(),
            name: '一旬周遍格',
            group: '闭口课篇附格',
            description: '旬尾加临日干寄宫，旬首加临日支；独立成格，不以闭口课成立为前提。',
            marker: '格',
            evidence: [
                'day_stem' => $dayStem,
                'day_branch' => $dayBranch,
                'stem_lodging' => $stemLodging,
                'xun_head' => $xunHead,
                'xun_tail' => $xunTail,
                'stem_upper' => $stemUpper,
                'branch_upper' => $branchUpper,
                'detail' => "旬首{$branch($xunHead)}加临日支{$branch($dayBranch)}，旬尾{$branch($xunTail)}加临日干寄宫{$branch($stemLodging)}；首尾分别加临日支与日干，独立成立一旬周遍格。",
            ],
        );
    }
}
