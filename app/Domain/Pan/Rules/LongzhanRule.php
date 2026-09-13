<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》正文严格同位口径判断龙战课。
 * 规则边界：仅认“卯日卯用行年卯”或“酉日酉用行年酉”；
 * 不采用《订讹》《观月经》《心镜》的扩展入口。
 */
final class LongzhanRule implements ContextAwareRule
{
    protected const RULE_CODE = 'lesson.longzhan';

    protected const NAME = '龙战课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '卯日卯发用且占者行年立卯，或酉日酉发用且占者行年立酉。';

    protected const GUA = '离';

    protected const GUA_SYMBOL = '䷝';

    protected const XIANG = '合者将离，居者将徙。欲行莫行，欲止莫止。出路迍邅，求婚莫娶。胎孕不安，财物弗聚。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《大全》“游神春丑夏子秋亥冬戌相加”的“相加”对象尚未冻结，未程序化',
        '《大全》“兄弟年立其上，主争财异居”因当前人物模型无明确兄弟姐妹角色，未程序化',
        '《大全》“将得天后，事起妇女”的天后具体检索位置尚未仅凭正文冻结，未程序化',
        '《大全》“乘蛇虎玄，尤加惊恐”的作用位置及数量要求尚未冻结，未程序化',
        '《订讹》《观月经》《心镜》的宽口径均作为旁证保留，不参与主体 matcher',
        '《大全》明确“纵有吉神将，不免其咎”，本轮不存在“吉将救解龙战”的程序条件',
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
        return ['name' => self::NAME, 'notice' => '龙战课需要占者出生信息以计算行年，当前未进行判断。'];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayBranch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $querent = $facts->personByRole('querent');
        $xingnian = is_array($querent) ? ($querent['xingnian'] ?? null) : null;

        if (! is_int($dayBranch) || ! is_int($initial) || ! is_int($xingnian)
            || ! in_array($dayBranch, [3, 9], true)
            || $dayBranch !== $initial || $dayBranch !== $xingnian) {
            return null;
        }

        $branchName = PanCalculator::$dizhi[$dayBranch];
        $judgments = [];

        if ((new SanjiaoRule)->match($facts) !== null) {
            $judgments[] = [
                'title' => '三传入三交',
                'detail' => '同盘兼成三交课；《大全》龙战正文断“主贼来必战”。',
            ];
        }

        $spouse = $facts->personByRole('spouse');
        if (is_array($spouse) && ($spouse['xingnian'] ?? null) === $dayBranch) {
            $judgments[] = [
                'title' => "夫妻年同立{$branchName}",
                'detail' => '配偶行年亦与龙战支同位；《大全》正文断“主室家离散”。',
            ];
        }

        return new RuleMatch(
            code: $this->code(), name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG,
            evidence: [
                'day_branch' => $dayBranch,
                'initial' => $initial,
                'querent_xingnian' => $xingnian,
                'matched_branch' => $dayBranch,
                'matched_route' => "{$branchName}日→{$branchName}发用→行年{$branchName}",
                'same_position' => true,
                'foundations' => [
                    ['title' => "{$branchName}日", 'detail' => "当前日支为{$branchName}。"],
                    ['title' => "{$branchName}发用", 'detail' => "初传为{$branchName}。"],
                    ['title' => "行年立{$branchName}", 'detail' => "占者当前行年为{$branchName}。"],
                    ['title' => '三者同位', 'detail' => "日支、初传、占者行年均为{$branchName}，命中《大全》龙战正文严格口径。"],
                ],
                'judgments' => $judgments,
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
