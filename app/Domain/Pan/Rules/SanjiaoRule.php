<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按项目冻结口径判断三交课，并保留三层结构及逐位置阴合证据。
 *
 * 规则边界：只检查四课上神与三传中的仲神是否乘太阴或六合；
 * 不收窄到初传，也不扩展到整个天地盘。
 */
final class SanjiaoRule implements PanRule
{
    /** @var list<int> */
    private const ZHONG_BRANCHES = [0, 3, 6, 9];

    /** @var list<int> */
    private const YIN_HE_GENERALS = [10, 3];

    protected const RULE_CODE = 'lesson.sanjiao';

    protected const NAME = '三交课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '四仲日占，支辰阴阳及三传皆为四仲，且课传所见仲神至少一处乘太阴或六合。';

    protected const GUA = '姤';

    protected const GUA_SYMBOL = '䷫';

    protected const XIANG = '家隐奸私，或自逃匿。谋事不明，求财无益。讼犯刑名，兵逢战敌。更乘凶将，病患尤极。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《大全》未明确规定“仲神乘太阴六合将”的检索位置；项目冻结为四课上神与三传，不宣称消除了古籍全部歧义',
        '奸私、逃匿、刑名、战敌及更乘凶将等象义与吉凶增减尚未程序化',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayBranch = $facts->get('rizhi');
        $sike = $facts->get('sike');
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        if (! is_int($dayBranch) || ! is_array($sike)
            || array_any([1, 3, 5, 7], static fn (int $position): bool => ! is_int($sike[$position] ?? null))
            || array_any($transmissions, static fn (mixed $branch): bool => ! is_int($branch))) {
            return null;
        }

        /** @var list<int> $lessonUppers */
        $lessonUppers = [$sike[1], $sike[3], $sike[5], $sike[7]];
        /** @var list<int> $transmissions */
        $branchYang = $sike[5];
        $branchYin = $sike[7];
        $dayIsZhong = $this->isZhong($dayBranch);
        $branchYangIsZhong = $this->isZhong($branchYang);
        $branchYinIsZhong = $this->isZhong($branchYin);
        $allTransmissionsAreZhong = array_all($transmissions, fn (int $branch): bool => $this->isZhong($branch));

        $occurrences = [];
        foreach ($lessonUppers as $index => $branch) {
            if ($this->isZhong($branch)) {
                $occurrences[] = $this->occurrence($facts, 'lesson', $index * 2 + 1, $branch);
            }
        }
        foreach ($transmissions as $index => $branch) {
            if ($this->isZhong($branch)) {
                $occurrences[] = $this->occurrence($facts, 'transmission', $index, $branch);
            }
        }

        if (array_any($occurrences, static fn (array $item): bool => $item['general'] === null)) {
            return null;
        }

        $matched = array_values(array_filter(
            $occurrences,
            static fn (array $item): bool => in_array($item['general'], self::YIN_HE_GENERALS, true),
        ));

        if (! ($dayIsZhong && $branchYangIsZhong && $branchYinIsZhong
            && $allTransmissionsAreZhong && $matched !== [])) {
            return null;
        }

        $branchName = static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $matchedDetails = implode('；', array_map(static function (array $item) use ($branchName): string {
            $position = $item['scope'] === 'lesson' ? "四课上神 sike[{$item['position']}]" : ['初传', '中传', '末传'][$item['position']];

            return "{$position}{$branchName($item['branch'])}乘{$item['general_name']}";
        }, $matched));

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'day_branch' => $dayBranch,
                'lesson_uppers' => $lessonUppers,
                'branch_yang' => $branchYang,
                'branch_yin' => $branchYin,
                'transmissions' => $transmissions,
                'day_is_zhong' => $dayIsZhong,
                'branch_yang_is_zhong' => $branchYangIsZhong,
                'branch_yin_is_zhong' => $branchYinIsZhong,
                'all_transmissions_are_zhong' => $allTransmissionsAreZhong,
                'zhong_occurrences' => $occurrences,
                'matched_yin_he_occurrences' => $matched,
                'foundations' => [
                    ['title' => '一交·四仲加辰', 'detail' => "日支{$branchName($dayBranch)}、支阳{$branchName($branchYang)}、支阴{$branchName($branchYin)}皆为四仲。"],
                    ['title' => '二交·传皆四仲', 'detail' => '三传'.implode('、', array_map($branchName, $transmissions)).'皆为四仲。'],
                    ['title' => '三交·仲神乘阴合', 'detail' => $matchedDetails.'；命中位置限定为四课上神与三传。'],
                ],
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    private function isZhong(int $branch): bool
    {
        return in_array($branch, self::ZHONG_BRANCHES, true);
    }

    /** @return array{scope: string, position: int, branch: int, general: ?int, general_name: ?string} */
    private function occurrence(PanFacts $facts, string $scope, int $position, int $branch): array
    {
        $general = $facts->generalRidingBranch($branch);

        return [
            'scope' => $scope,
            'position' => $position,
            'branch' => $branch,
            'general' => $general,
            'general_name' => is_int($general) ? (PanCalculator::$tianjiang[$general] ?? '?') : null,
        ];
    }
}
