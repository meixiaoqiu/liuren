<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按冻结结论“九丑日 + 天盘丑临日支”判断九丑课。
 *
 * 四仲时与丑发用仅保留为《六壬大全》正文严格形态判断，不反向收紧 matcher；
 * 丑临日干、丑临其他四仲而发用均不纳入正式 matcher。本口径综合正文课例与多条
 * 独立古籍证据，并非以某一注本覆盖正文。
 */
final class JiuchouRule implements PanRule
{
    public const RULE_CODE = 'lesson.jiuchou';

    public const NAME = '九丑课';

    public const GROUP = '六十四课';

    public const GUA = '小过';

    public const GUA_SYMBOL = '䷽';

    public const DESCRIPTION = '九丑十日占课，天盘丑加临日支，为九丑课。';

    private const CHOU = 1;

    private const FOUR_ZHONG = [0, 3, 6, 9];

    /** @var array<int, list<int>> */
    private const JIUCHOU_DAYS = [4 => [0, 6], 8 => [0, 6], 1 => [3, 9], 5 => [3, 9], 7 => [3, 9]];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $hour = $facts->get('shizhi');
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');

        if (! is_int($stem) || ! is_int($branch) || ! is_array($tianpan) || count($tianpan) !== 12
            || ! in_array($stem, range(0, 9), true) || ! in_array($branch, range(0, 11), true)) {
            return null;
        }

        $isJiuchouDay = in_array($branch, self::JIUCHOU_DAYS[$stem] ?? [], true);
        $chouGround = $facts->heavenBranchGroundPosition(self::CHOU);
        $chouAtDayBranch = $chouGround !== null && $chouGround === $branch;
        if (! $isJiuchouDay || ! $chouAtDayBranch) {
            return null;
        }

        $validHour = is_int($hour) && in_array($hour, range(0, 11), true);
        $validInitial = is_int($initial) && in_array($initial, range(0, 11), true);
        $fourZhongTime = $validHour && in_array($hour, self::FOUR_ZHONG, true);
        $chouFayong = $validInitial && $initial === self::CHOU;
        $strict = $fourZhongTime && $chouFayong;
        $dayGanzhi = (PanCalculator::$tiangan[$stem] ?? '?').(PanCalculator::$dizhi[$branch] ?? '?');
        $judgments = [
            ['code' => 'four_zhong_time', 'effect' => 'neutral', 'label' => '四仲时占', 'evidence' => $validHour ? '占时'.(PanCalculator::$dizhi[$hour] ?? '?').($fourZhongTime ? '属于' : '不属于').'子、卯、午、酉四仲。' : '占时资料缺失或异常，无法判断是否为四仲时。', 'matched' => $fourZhongTime],
            ['code' => 'chou_fayong', 'effect' => 'neutral', 'label' => '丑发用', 'evidence' => $validInitial ? '初传为'.(PanCalculator::$dizhi[$initial] ?? '?').($chouFayong ? '，丑发用。' : '，丑未发用。') : '初传资料缺失或异常，无法判断丑是否发用。', 'matched' => $chouFayong],
        ];
        if ($strict) {
            $judgments[] = ['code' => 'strict_daquan_form', 'effect' => 'increase', 'label' => '完全符合《六壬大全》正文严格形态', 'evidence' => '主体已成课，且四仲时占、丑发用同时成立。', 'matched' => true];
        }

        return new RuleMatch(
            code: self::RULE_CODE, name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL,
            xiang: '刚日男凶，柔日女祸。重阳害父，重阴害母。婚姻有灾，造葬无补。诸事谋为，徒劳身苦。',
            evidence: [
                'day_stem' => $stem, 'day_branch' => $branch, 'day_ganzhi' => $dayGanzhi,
                'chou_ground' => $chouGround, 'is_jiuchou_day' => true, 'chou_at_day_branch' => true,
                'hour_branch' => $hour, 'four_zhong_time' => $fourZhongTime,
                'initial' => $initial, 'chou_fayong' => $chouFayong, 'strict_daquan_form' => $strict,
                'foundations' => [
                    ['title' => '九丑日', 'detail' => "当前日干支{$dayGanzhi}属于九丑十日。"],
                    ['title' => '丑所临地盘', 'detail' => '天盘丑当前实际加临地盘'.(PanCalculator::$dizhi[$chouGround] ?? '?').'。'],
                    ['title' => '日支', 'detail' => '当前日支为'.(PanCalculator::$dizhi[$branch] ?? '?').'。'],
                    ['title' => '主体成课', 'detail' => '丑所临地盘与日支同为'.(PanCalculator::$dizhi[$branch] ?? '?').'，因此“丑临日支”，九丑课成立。'],
                ],
                'judgments' => $judgments,
                'uncovered' => [
                    '刚日男凶、柔日女祸的完整人物作用边界未程序化',
                    '重阳害父、重阴害母的完整程序边界未冻结',
                    '“上乘白虎”的具体所指位置尚有歧义',
                    '大时、小时及其“相并”的对象与位置尚未冻结',
                    '吉将祸浅、凶将祸深的天将集合及作用对象尚未冻结',
                    '大小时并见时应期缩短等判断尚未程序化',
                ],
            ],
        );
    }
}
