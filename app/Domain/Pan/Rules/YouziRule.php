<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》游子课正文的项目暂定严格口径判断游子课。
 *
 * 规则边界：三传须全为辰戌丑未四季土神，且旬丁或月内天马必须发用；
 * 丁、马只在中末传不成立，不在此处抽象全局神煞系统。
 */
final class YouziRule implements PanRule
{
    /** @var array<int, int> 月建地支到月内天马的固定映射。 */
    private const MONTH_TIANMA_BY_MONTH_BRANCH = [
        0 => 2,
        1 => 4,
        2 => 6,
        3 => 8,
        4 => 10,
        5 => 0,
        6 => 2,
        7 => 4,
        8 => 6,
        9 => 8,
        10 => 10,
        11 => 0,
    ];

    /** @var list<int> */
    private const SEASONAL_BRANCHES = [1, 4, 7, 10];

    protected const RULE_CODE = 'lesson.youzi';

    protected const NAME = '游子课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '三传皆为辰戌丑未四季土神，且旬丁或月内天马发用。';

    protected const GUA = '观';

    protected const GUA_SYMBOL = '䷓';

    protected const XIANG = '丁马加季，奔走西东。出行吉利，坐守困穷。疾病难产，官讼多凶。天阴不雨，婚事胡从。';

    /** @var list<string> */
    private const UNCOVERED = [
        '“传出阳神欲远行，传入阴神欲私归”的出入方向细分尚未实现',
        '与斩关、淫泆、天寇、行年、五墓四杀及三奇六仪等合课、凶吉增减与救解条件尚未实现',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];
        $xunHead = $facts->dayXunHeadBranch();
        $monthBranch = $facts->get('yuezhi');

        if (array_any($transmissions, static fn (mixed $branch): bool => ! is_int($branch))
            || $xunHead === null || ! is_int($monthBranch)
            || ! isset(self::MONTH_TIANMA_BY_MONTH_BRANCH[$monthBranch])) {
            return null;
        }

        /** @var list<int> $transmissions */
        $initial = $transmissions[0];
        $allSeasonal = array_all(
            $transmissions,
            static fn (int $branch): bool => in_array($branch, self::SEASONAL_BRANCHES, true),
        );
        $xunDing = ($xunHead + 3) % 12;
        $monthTianma = self::MONTH_TIANMA_BY_MONTH_BRANCH[$monthBranch];
        $xunDingAsInitial = $initial === $xunDing;
        $monthTianmaAsInitial = $initial === $monthTianma;

        if (! $allSeasonal || ! ($xunDingAsInitial || $monthTianmaAsInitial)) {
            return null;
        }

        $paths = [];
        if ($xunDingAsInitial) {
            $paths[] = '旬丁发用';
        }
        if ($monthTianmaAsInitial) {
            $paths[] = '天马发用';
        }

        $branch = static fn (int $i): string => PanCalculator::$dizhi[$i] ?? '?';
        $transmissionNames = implode('、', array_map($branch, $transmissions));
        $foundations = [[
            'title' => '三传皆季',
            'detail' => "三传为{$transmissionNames}，皆属辰戌丑未四季土神。",
        ]];

        if ($xunDingAsInitial) {
            $foundations[] = [
                'title' => '旬丁发用',
                'detail' => "本日旬首为{$branch($xunHead)}，旬丁为{$branch($xunDing)}；初传为{$branch($initial)}，旬丁发用。",
            ];
        }
        if ($monthTianmaAsInitial) {
            $foundations[] = [
                'title' => '天马发用',
                'detail' => "月建为{$branch($monthBranch)}，月内天马为{$branch($monthTianma)}；初传为{$branch($initial)}，天马发用。",
            ];
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'transmissions' => $transmissions,
                'initial' => $initial,
                'xun_head' => $xunHead,
                'xun_ding' => $xunDing,
                'month_branch' => $monthBranch,
                'month_tianma' => $monthTianma,
                'all_seasonal' => $allSeasonal,
                'xun_ding_as_initial' => $xunDingAsInitial,
                'month_tianma_as_initial' => $monthTianmaAsInitial,
                'paths' => $paths,
                'foundations' => $foundations,
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
