<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Shensha\ZaieShensha;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全·灾厄课》主体定义判断灾厄课。
 *
 * 规则边界：
 *  - 灾厄课主体只要求初传等于正文列出的九种神煞之一。
 *  - 不要求克日干、克日支、克年命、乘白虎。
 *  - 月神使用 yuezhi（month branch），不使用 yuejiang。
 *  - 岁神使用 nianzhi（year branch），不使用本命或行年。
 *  - 三丘五墓按 yuezhi 所属季节查表；不进入 PanFacts::seasonalPeriod() 的"四季土旺十八日"。
 *  - 九个神煞 key 必须由 ZaieShensha 共享类给出，规则不另写一份算法。
 *  - 重叠命中（多个神煞落在同一初传）必须全部保留。
 */
final class ZaieRule implements PanRule
{
    use LessonDefinitionDefaults;

    protected const RULE_CODE = 'lesson.zaie';

    protected const NAME = '灾厄课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '丧车、游魂、伏殃、病符、丧门、吊客、三丘、五墓、岁虎任一发用，即成灾厄课。';

    protected const GUA = '归妹';

    protected const GUA_SYMBOL = '䷵';

    protected const XIANG = '家门厄会，妖孽为害。疾病死亡，财喜破坏。婚孕多凶，征战大败。行人不归，访人不在。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《大全》"病符临支克支"未进入基础成课条件',
        '《大全》"病符并天鬼"（天鬼即伏殃别名）涉及多个神煞之间的"并"字句法未冻结',
        '《大全》"病符并白虎"涉及神煞与天将同时成立的句法未冻结',
        '《大全》"丧吊全加支干"涉及多神煞同时加临日干日的程序范围尚未冻结',
        '《大全》"丧吊临年命、发用"中年命上下文未冻结',
        '《大全》"死气"、"绝神"未进入基础成课条件',
        '《大全》"白虎临身"未进入基础成课条件',
        '《大全》"吊客入宅"中"宅"的具体位置未冻结',
        '《大全》"旬虎"未进入基础成课条件',
        '《大全》"丘墓并虎雀丧门"的多神煞并见句法未冻结',
        '《大全》"季神逢丁"未进入基础成课条件',
        '《大全》"血支"、"血忌"未进入基础成课条件',
        '《大全》"天医、地医"等救解未进入基础成课条件',
        '现代《图解》"泛凶煞发用并克干支年命"的扩大口径不采用，未进入 matcher',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $monthBranch = $facts->get('yuezhi');
        $yearBranch = $facts->get('nianzhi');
        $initial = $facts->get('sanchuan0');

        if (! is_int($monthBranch) || ! is_int($yearBranch) || ! is_int($initial)
            || ! in_array($monthBranch, range(0, 11), true)
            || ! in_array($yearBranch, range(0, 11), true)
            || ! in_array($initial, range(0, 11), true)) {
            return null;
        }

        $shensha = ZaieShensha::forPan($monthBranch, $yearBranch);
        $hits = ZaieShensha::matchInitial($shensha, $initial);
        if ($hits === []) {
            return null;
        }

        $monthName = PanCalculator::$dizhi[$monthBranch];
        $yearName = PanCalculator::$dizhi[$yearBranch];
        $initialName = PanCalculator::$dizhi[$initial];

        $foundations = [
            ['title' => '月建', 'detail' => "月建为{$monthName}（yuezhi={$monthBranch}）。"],
            ['title' => '太岁', 'detail' => "太岁为{$yearName}（nianzhi={$yearBranch}）。"],
            ['title' => '初传', 'detail' => "初传为{$initialName}（sanchuan0={$initial}）。"],
        ];

        foreach ($hits as $key) {
            $foundations[] = [
                'title' => ZaieShensha::displayName($key).'发用',
                'detail' => ZaieShensha::displayName($key).'所在地支为'.PanCalculator::$dizhi[$shensha[$key]]."，与初传同为{$initialName}，发用成立。",
            ];
        }

        return new RuleMatch(
            code: $this->code(), name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG,
            evidence: [
                'month_branch' => $monthBranch,
                'year_branch' => $yearBranch,
                'initial' => $initial,
                'shensha' => $shensha,
                'matched_keys' => $hits,
                'foundations' => $foundations,
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
