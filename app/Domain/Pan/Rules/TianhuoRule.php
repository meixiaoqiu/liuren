<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：严格按四立日两组同向“干支同时相临”判断天祸课；发用不参与基础成立条件。 */
final class TianhuoRule implements PanRule
{
    public function code(): string
    {
        return 'lesson.tianhuo';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $fourLi = $facts->fourLiDay();
        $todayIndex = $facts->sexagenaryDayIndex();
        $todayStem = $facts->get('rigan');
        $todayBranch = $facts->get('rizhi');
        $tianpan = $facts->get('tianpan');

        if ($fourLi === null || $todayIndex === null || ! is_int($todayStem) || ! is_int($todayBranch) || ! is_array($tianpan)) {
            return null;
        }

        $yesterdayIndex = ($todayIndex + 59) % 60;
        $yesterdayStem = $yesterdayIndex % 10;
        $yesterdayBranch = $yesterdayIndex % 12;
        $todayStemLodge = $facts->stemLodgingBranch($todayStem);
        $yesterdayStemLodge = $facts->stemLodgingBranch($yesterdayStem);
        if (! is_int($todayStemLodge) || ! is_int($yesterdayStemLodge)) {
            return null;
        }

        foreach ([$yesterdayStemLodge, $yesterdayBranch, $todayStemLodge, $todayBranch] as $ground) {
            if (! isset($tianpan[$ground]) || ! is_int($tianpan[$ground])) {
                return null;
            }
        }

        $todayStemUpper = $tianpan[$yesterdayStemLodge];
        $todayBranchUpper = $tianpan[$yesterdayBranch];
        $yesterdayStemUpper = $tianpan[$todayStemLodge];
        $yesterdayBranchUpper = $tianpan[$todayBranch];
        $todayStemOnYesterdayStem = $todayStemUpper === $todayStemLodge;
        $todayBranchOnYesterdayBranch = $todayBranchUpper === $todayBranch;
        $yesterdayStemOnTodayStem = $yesterdayStemUpper === $yesterdayStemLodge;
        $yesterdayBranchOnTodayBranch = $yesterdayBranchUpper === $yesterdayBranch;
        $todayOnYesterday = $todayStemOnYesterdayStem && $todayBranchOnYesterdayBranch;
        $yesterdayOnToday = $yesterdayStemOnTodayStem && $yesterdayBranchOnTodayBranch;

        if (! $todayOnYesterday && ! $yesterdayOnToday) {
            return null;
        }

        $stem = fn (int $value): string => PanCalculator::$tiangan[$value] ?? '?';
        $branch = fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';
        $today = $stem($todayStem).$branch($todayBranch);
        $yesterday = $stem($yesterdayStem).$branch($yesterdayBranch);
        $mark = static fn (bool $matched): string => $matched ? '成立' : '不成立';

        return new RuleMatch(
            code: $this->code(), name: '天祸课', group: '六十四课',
            description: '四立日，今日干支临昨日干支，或昨日干支临今日干支。',
            gua: '大过', guaSymbol: '䷛',
            xiang: '以新易旧，天有灾祸。咎事莫为，身宜谨守。战斗流血，造死丧偶。出行死亡，干谒空走。',
            evidence: [
                'four_li' => $fourLi,
                'calculation_date' => substr((string) $facts->get('calculationTime'), 0, 10),
                'today_index' => $todayIndex, 'yesterday_index' => $yesterdayIndex,
                'today_stem' => $todayStem, 'today_branch' => $todayBranch, 'today' => $today,
                'yesterday_stem' => $yesterdayStem, 'yesterday_branch' => $yesterdayBranch, 'yesterday' => $yesterday,
                'today_stem_lodge' => $todayStemLodge, 'yesterday_stem_lodge' => $yesterdayStemLodge,
                'today_stem_on_yesterday_stem' => $todayStemOnYesterdayStem,
                'today_branch_on_yesterday_branch' => $todayBranchOnYesterdayBranch,
                'yesterday_stem_on_today_stem' => $yesterdayStemOnTodayStem,
                'yesterday_branch_on_today_branch' => $yesterdayBranchOnTodayBranch,
                'today_on_yesterday' => $todayOnYesterday, 'yesterday_on_today' => $yesterdayOnToday,
                'today_stem_upper' => $todayStemUpper, 'today_branch_upper' => $todayBranchUpper,
                'yesterday_stem_upper' => $yesterdayStemUpper, 'yesterday_branch_upper' => $yesterdayBranchUpper,
                'matched_directions' => array_values(array_filter([
                    $todayOnYesterday ? 'today_on_yesterday' : null,
                    $yesterdayOnToday ? 'yesterday_on_today' : null,
                ])),
                'foundations' => [
                    ['title' => '四立日', 'detail' => "当前日期{$fourLi['date']}为{$fourLi['name']}日；精确交节为{$fourLi['term_time']}，按交节所在整个公历日期判断。"],
                    ['title' => '今日与昨日干支', 'detail' => "今日{$today}，昨日{$yesterday}；{$stem($todayStem)}寄{$branch($todayStemLodge)}，{$stem($yesterdayStem)}寄{$branch($yesterdayStemLodge)}。"],
                    ['title' => '方向一：今日干支临昨日干支', 'detail' => "地盘{$branch($yesterdayStemLodge)}位上神为{$branch($todayStemUpper)}，今日干相临{$mark($todayStemOnYesterdayStem)}；地盘{$branch($yesterdayBranch)}位上神为{$branch($todayBranchUpper)}，今日支相临{$mark($todayBranchOnYesterdayBranch)}；本方向{$mark($todayOnYesterday)}。"],
                    ['title' => '方向二：昨日干支临今日干支', 'detail' => "地盘{$branch($todayStemLodge)}位上神为{$branch($yesterdayStemUpper)}，昨日干相临{$mark($yesterdayStemOnTodayStem)}；地盘{$branch($todayBranch)}位上神为{$branch($yesterdayBranchUpper)}，昨日支相临{$mark($yesterdayBranchOnTodayBranch)}；本方向{$mark($yesterdayOnToday)}。"],
                ],
                'judgments' => [],
                'uncovered' => ['《观月经》《心镜》等只看日干的异说未纳入正式规则。', '“又发用”等只作增强描述，不是基础成立条件。'],
            ],
        );
    }
}
