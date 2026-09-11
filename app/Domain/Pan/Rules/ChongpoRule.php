<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》正文最小严格口径判断冲破课。
 *
 * 规则边界：只认日干寄宫或日支之冲神发用，且该初传实际加临其自身破位；
 * “用传与岁月日时冲破亦是”暂不作为独立成课入口。
 */
final class ChongpoRule implements PanRule
{
    protected const RULE_CODE = 'lesson.chongpo';

    protected const NAME = '冲破课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干寄宫或日支之冲神发用，且该冲神加临其自身破位。';

    protected const GUA = '夬';

    protected const GUA_SYMBOL = '䷪';

    protected const XIANG = '人情反覆，门户不宁。婚姻不遂，胎孕难成。疾病凶散，财利事平。';

    /** @var list<int> */
    private const CHONG = [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5];

    /** @var list<int> */
    private const PO = [9, 4, 11, 6, 1, 8, 3, 10, 5, 0, 7, 2];

    /** @var list<string> */
    private const UNCOVERED = [
        '“用传与岁月日时冲破亦是”的精确课体地位仍有歧义，当前未作为独立成课入口程序化',
        '冲破与白虎、螣蛇、朱雀、死神、丧车、破碎等吉凶增减尚未动态程序化',
        '空亡受冲、类神受冲以及“宜散凶事、不宜吉事”等占断尚未程序化',
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
        $tianpan = $facts->get('tianpan');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($initial) || ! is_array($tianpan)) {
            return null;
        }

        $stemLodgingBranch = $facts->stemLodgingBranch($dayStem);
        $initialGround = array_search($initial, $tianpan, true);
        if ($stemLodgingBranch === null || $initialGround === false) {
            return null;
        }

        $stemClash = self::CHONG[$stemLodgingBranch] ?? null;
        $branchClash = self::CHONG[$dayBranch] ?? null;
        $initialBreakGround = self::PO[$initial] ?? null;
        if ($stemClash === null || $branchClash === null || $initialBreakGround === null) {
            return null;
        }

        $stemPath = $initial === $stemClash && $initialGround === $initialBreakGround;
        $branchPath = $initial === $branchClash && $initialGround === $initialBreakGround;
        if (! ($stemPath || $branchPath)) {
            return null;
        }

        $stem = static fn (int $index): string => PanCalculator::$tiangan[$index] ?? '?';
        $branch = static fn (int $index): string => PanCalculator::$dizhi[$index] ?? '?';
        $paths = [];
        $foundations = [];

        if ($stemPath) {
            $paths[] = '日干冲神加破发用';
            $foundations[] = [
                'title' => '日干寄宫一路',
                'detail' => "日干{$stem($dayStem)}寄宫{$branch($stemLodgingBranch)}之冲神为{$branch($stemClash)}；初传{$branch($initial)}；{$branch($initial)}实际临地盘{$branch($initialGround)}；{$branch($initialGround)}为{$branch($initial)}之破；故日干一路成立。",
            ];
        }
        if ($branchPath) {
            $paths[] = '日支冲神加破发用';
            $foundations[] = [
                'title' => '日支一路',
                'detail' => "日支{$branch($dayBranch)}之冲神为{$branch($branchClash)}；初传{$branch($initial)}；{$branch($initial)}实际临地盘{$branch($initialGround)}；{$branch($initialGround)}为{$branch($initial)}之破；故日支一路成立。",
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
                'stem_lodging_branch' => $stemLodgingBranch,
                'initial' => $initial,
                'initial_ground' => $initialGround,
                'initial_break_ground' => $initialBreakGround,
                'stem_clash' => $stemClash,
                'branch_clash' => $branchClash,
                'stem_path' => $stemPath,
                'branch_path' => $branchPath,
                'paths' => $paths,
                'foundations' => $foundations,
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
