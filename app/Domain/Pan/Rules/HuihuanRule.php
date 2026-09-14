<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：独立判断盘珠课篇所附回还格；三传俱在四课即可，不以前置命中盘珠课为条件。 */
final class HuihuanRule implements PanRule
{
    public function code(): string
    {
        return 'structure.huihuan';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $analysis = PanzhuSupport::analyze($facts);
        if ($analysis === null
            || ! PanzhuSupport::allIn($analysis['transmissions'], $analysis['lesson_branches'])) {
            return null;
        }

        $transmissions = PanzhuSupport::branchNames($analysis['transmissions']);
        $lessons = PanzhuSupport::branchNames($analysis['lesson_branches']);

        return new RuleMatch(
            code: $this->code(),
            name: '回还格',
            group: '盘珠课体',
            description: '三传俱在四课整体结构的地支集合中。',
            marker: '格',
            evidence: [
                'transmissions' => $analysis['transmissions'],
                'lesson_branches' => $analysis['lesson_branches'],
                'detail' => "三传{$transmissions}全部见于四课地支集合（{$lessons}），成立回还格。",
            ],
        );
    }
}
