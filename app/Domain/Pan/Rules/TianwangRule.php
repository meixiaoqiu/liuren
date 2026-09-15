<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按“占时支克日干 AND 初传克日干”冻结口径判断天网课；同支、神煞、解网与罗网格均不参与基础成立。 */
final class TianwangRule implements PanRule
{
    use LessonDefinitionDefaults;

    /** @var list<string> */
    private const ELEMENTS = ['木', '火', '土', '金', '水'];

    public function code(): string
    {
        return 'lesson.tianwang';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $timeBranch = $facts->get('shizhi');
        $initial = $facts->get('sanchuan0');

        if (! is_int($dayStem) || ! is_int($timeBranch) || ! is_int($initial)) {
            return null;
        }

        $dayStemElement = $facts->stemElement($dayStem);
        $timeBranchElement = $facts->branchElement($timeBranch);
        $initialElement = $facts->branchElement($initial);
        if ($dayStemElement === null || $timeBranchElement === null || $initialElement === null) {
            return null;
        }

        $timeRestrainsDay = $dayStemElement === ($timeBranchElement + 2) % 5;
        $initialRestrainsDay = $dayStemElement === ($initialElement + 2) % 5;
        if (! $timeRestrainsDay || ! $initialRestrainsDay) {
            return null;
        }

        $stemName = PanCalculator::$tiangan[$dayStem] ?? '?';
        $timeName = PanCalculator::$dizhi[$timeBranch] ?? '?';
        $initialName = PanCalculator::$dizhi[$initial] ?? '?';
        $dayElementName = self::ELEMENTS[$dayStemElement] ?? '?';
        $timeElementName = self::ELEMENTS[$timeBranchElement] ?? '?';
        $initialElementName = self::ELEMENTS[$initialElement] ?? '?';

        return new RuleMatch(
            code: $this->code(), name: '天网课', group: '六十四课',
            description: '占时与发用同为日鬼，即占时支和初传分别克日干。',
            gua: '蒙', guaSymbol: '䷃',
            xiang: '天网四张，万物尽伤。产孕损子，逃亡遭殃。战有埋伏，病入膏肓。先凶有救，后获吉祥。',
            evidence: [
                'day_stem' => $dayStem,
                'day_stem_element' => $dayStemElement,
                'time_branch' => $timeBranch,
                'time_branch_element' => $timeBranchElement,
                'time_restrains_day' => $timeRestrainsDay,
                'initial' => $initial,
                'initial_element' => $initialElement,
                'initial_restrains_day' => $initialRestrainsDay,
                'time_equals_initial' => $timeBranch === $initial,
                'foundations' => [
                    ['title' => '占时克日', 'detail' => "占时{$timeName}属{$timeElementName}，日干{$stemName}属{$dayElementName}，{$timeElementName}克{$dayElementName}，占时克日成立。"],
                    ['title' => '用神克日', 'detail' => "初传{$initialName}属{$initialElementName}，日干{$stemName}属{$dayElementName}，{$initialElementName}克{$dayElementName}，用神克日成立。"],
                    ['title' => '同克不等于同支', 'detail' => '占时与初传是否同支不影响成立，只判断二者是否分别克日干。'],
                ],
                'judgments' => [],
                'uncovered' => [
                    '天网煞、天刑煞及辰戌天网地网只作课义增强，未加入基础判断。',
                    '解网属于天网成立后的救解判断，旁证对救神范围仍有差异，本轮尚未实现。',
                    '罗网格属于独立格，天罗、地网定义仍待独立校勘，本轮尚未实现。',
                    '三煞、灾煞、劫煞与旺相克囚死等凶应增强均未加入基础判断。',
                ],
            ],
        );
    }
}
