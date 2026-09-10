<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》正文严格口径判断赘婿课。
 *
 * 规则边界：日干须克日支，并且日支临干而由日支发用，或日干寄宫之神临支而发用；
 * 只相临、只入中末传及其他文献所见宽口径均不在主体规则内。
 */
final class ZhuixuRule implements PanRule
{
    protected const RULE_CODE = 'lesson.zhuixu';

    protected const NAME = '赘婿课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干克日支，且日干、日支之一亲临对方并由该神发用。';

    protected const GUA = '旅';

    protected const GUA_SYMBOL = '䷷';

    protected const XIANG = '屈意从人，事多牵制。胎孕迟延，行人淹滞。财名可成，病讼未济。兵利为客，先动胜计。';

    /** @var list<string> */
    private const UNCOVERED = [
        '干临支与支临干两路所主“男就乎女／女就于男”、尊卑动静及兵家主客差异尚未程序化',
        '白虎、勾陈、朱雀、螣蛇等天将，以及日用旺相休囚对吉凶增减的判断尚未程序化',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $sike = $facts->get('sike');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($initial)
            || ! is_array($sike) || ! is_int($sike[1] ?? null) || ! is_int($sike[5] ?? null)) {
            return null;
        }

        $stemLodgingBranch = $facts->stemLodgingBranch($dayStem);
        if ($stemLodgingBranch === null) {
            return null;
        }

        $dayStemControlsDayBranch = $facts->isDayWealthBranch($dayBranch);
        $dayBranchOnStem = $sike[1] === $dayBranch;
        $stemOnDayBranch = $sike[5] === $stemLodgingBranch;
        $dayBranchAsInitial = $initial === $dayBranch;
        $stemLodgingAsInitial = $initial === $stemLodgingBranch;
        $branchPath = $dayBranchOnStem && $dayBranchAsInitial;
        $stemPath = $stemOnDayBranch && $stemLodgingAsInitial;

        if (! $dayStemControlsDayBranch || ! ($branchPath || $stemPath)) {
            return null;
        }

        $stem = static fn (int $i): string => PanCalculator::$tiangan[$i] ?? '?';
        $branch = static fn (int $i): string => PanCalculator::$dizhi[$i] ?? '?';
        $paths = [];
        $foundations = [[
            'title' => '日干克辰',
            'detail' => "日干{$stem($dayStem)}克日支{$branch($dayBranch)}，日支为日干之财。",
        ]];

        if ($branchPath) {
            $paths[] = '支临干发用';
            $foundations[] = [
                'title' => '支临干发用',
                'detail' => "日干{$stem($dayStem)}上神为{$branch($sike[1])}，正是日支{$branch($dayBranch)}；初传亦为{$branch($initial)}，构成支临干而发用。",
            ];
        }
        if ($stemPath) {
            $paths[] = '干临支发用';
            $foundations[] = [
                'title' => '干临支发用',
                'detail' => "日干{$stem($dayStem)}寄宫{$branch($stemLodgingBranch)}；日支{$branch($dayBranch)}上神为{$branch($sike[5])}，初传亦为{$branch($initial)}，构成干临支而发用。",
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
                'day_stem' => $dayStem,
                'day_branch' => $dayBranch,
                'initial' => $initial,
                'stem_lodging_branch' => $stemLodgingBranch,
                'day_stem_controls_day_branch' => $dayStemControlsDayBranch,
                'day_branch_on_stem' => $dayBranchOnStem,
                'day_branch_as_initial' => $dayBranchAsInitial,
                'branch_path' => $branchPath,
                'stem_on_day_branch' => $stemOnDayBranch,
                'stem_lodging_as_initial' => $stemLodgingAsInitial,
                'stem_path' => $stemPath,
                'paths' => $paths,
                'foundations' => $foundations,
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
