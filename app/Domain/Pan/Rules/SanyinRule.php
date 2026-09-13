<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按冻结口径判断三阴课；行年是必要上下文，缺失时由规则引擎标记为尚未判断。
 * 规则只检查初传的囚死与玄虎，不纳入《订讹》《心镜》《袖中金》的扩大或收紧异说。
 */
final class SanyinRule implements ContextAwareRule
{
    protected const RULE_CODE = 'lesson.sanyin';

    protected const NAME = '三阴课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '贵人逆行，日干寄宫与日支均乘贵后六将，初传囚死且乘玄武或白虎，占时支又克占人行年。';

    protected const GUA = '中孚';

    protected const GUA_SYMBOL = '䷼';

    protected const XIANG = '动作困苦，百事沉沦。见官屈伏，占病多迍。仕忧禄位，男忌婚姻。求财破散，孕主女娠。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《订讹》“发用传终各带囚死”的收紧口径未进入基础成课条件',
        '《心镜》关于日辰见玄武白虎、用中囚死复相克等异说未进入基础成课条件',
        '《袖中金》关于玄白立前、大旺克初、日辰囚死等异说未进入基础成课条件',
        '日辰三传始终囚死带墓、丧魂、游魂、天鬼、伏殃及六处救解等增强或救解尚未实现',
        '六阴课是附属结构，本轮未实现为独立规则',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function requiredContext(): array
    {
        return ['people.querent.xingnian'];
    }

    public function notEvaluatedInfo(): array
    {
        return ['name' => self::NAME, 'notice' => '需要占人行年信息（出生时间与性别），当前未进行三阴课判断。'];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $time = $facts->get('shizhi');
        $initial = $facts->get('sanchuan0');
        $person = $facts->personByRole('querent');
        $xingnian = is_array($person) ? ($person['xingnian'] ?? null) : null;

        if (! is_int($stem) || ! is_int($branch) || ! is_int($time) || ! is_int($initial) || ! is_int($xingnian)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($stem);
        $state = $facts->branchSeasonalState($initial);
        $initialGeneral = $facts->generalRidingBranch($initial);
        $timeElement = $facts->branchElement($time);
        $xingnianElement = $facts->branchElement($xingnian);
        $timeRestrainsXingnian = is_int($timeElement) && is_int($xingnianElement)
            && $xingnianElement === ($timeElement + 2) % 5;

        if (! is_int($lodging)
            || ! $facts->isNoblemanMovingBackward()
            || ! $facts->isGroundPositionRidingNoblemanRearGeneral($lodging)
            || ! $facts->isGroundPositionRidingNoblemanRearGeneral($branch)
            || ! in_array($state, ['囚', '死'], true)
            || ! in_array($initialGeneral, [7, 9], true)
            || ! $timeRestrainsXingnian) {
            return null;
        }

        $noblemanGround = $facts->noblemanGroundPosition();
        $tianpan = $facts->get('tianpan');
        $nobleman = is_int($noblemanGround) && is_array($tianpan) ? ($tianpan[$noblemanGround] ?? null) : null;

        return new RuleMatch(
            code: $this->code(), name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG,
            evidence: [
                'nobleman' => $nobleman,
                'nobleman_ground' => $noblemanGround,
                'nobleman_forward' => false,
                'nobleman_backward' => true,
                'day_stem' => ['stem' => $stem, 'lodging_branch' => $lodging, 'general' => $facts->generalAtGroundPosition($lodging), 'rear_general_rank' => $facts->noblemanRearGeneralRankAtGroundPosition($lodging)],
                'day_branch' => ['branch' => $branch, 'general' => $facts->generalAtGroundPosition($branch), 'rear_general_rank' => $facts->noblemanRearGeneralRankAtGroundPosition($branch)],
                'initial_transmission' => ['branch' => $initial, 'element' => $facts->branchElement($initial), 'seasonal_state' => $state, 'general' => $initialGeneral],
                'time' => ['branch' => $time, 'element' => $timeElement],
                'xingnian' => ['branch' => $xingnian, 'element' => $xingnianElement],
                'time_restrains_xingnian' => true,
                'foundations' => [
                    ['title' => '第一阴', 'detail' => '贵人逆行，日干寄宫与日支均乘贵后六将。'],
                    ['title' => '第二阴', 'detail' => '初传处于时令囚死。'],
                    ['title' => '第三阴', 'detail' => '同一初传乘玄武或白虎，且占时支克占人行年。'],
                ],
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
