<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按“单项宽判、整体严判”口径判断迍福课。
 *
 * 《六壬大全》正文把迍福拆成八迍五福。本规则保留十三项全 AND；遇到正文未限定
 * 范围的“刑害、丘墓、吉神、年命”等，只并入有古籍依据且可明确追踪的候选路径，
 * 不用单一课例把歧义项人为缩窄。研究枚举显示，宽化后最终盘面结构仍只落到正文
 * 癸酉日、春占、三传未子巳这一种结构。
 */
final class ZhunfuRule implements ContextAwareRule
{
    /** @var list<int> 六凶将：蛇、雀、勾、空、虎、武。 */
    private const OMINOUS_GENERALS = [1, 2, 4, 6, 7, 9];

    /** @var list<int> 六吉将：贵、合、龙、常、阴、后。 */
    private const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    /** 十干刑：甲申、乙酉、丙亥、丁子、戊寅、己卯、庚巳、辛午、壬戌、癸未。 */
    private const STEM_PUNISHMENTS = [8, 9, 11, 0, 2, 3, 5, 6, 10, 7];

    /** 地支刑，与排盘核心当前刑表一致；本课另叠加十干刑与六害路径。 */
    private const BRANCH_PUNISHMENTS = [3, 10, 5, 0, 4, 8, 6, 1, 2, 9, 7, 11];

    /** 五行墓位：木未、火戌、土辰、金丑、水辰。 */
    private const TOMB_BY_ELEMENT = [0 => 7, 1 => 10, 2 => 4, 3 => 1, 4 => 4];

    /** 四德表，与德庆课既有口径保持一致。 */
    private const STEM_VIRTUES = [2, 8, 5, 11, 5, 2, 8, 5, 11, 5];
    private const BRANCH_VIRTUES = [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4];
    private const HEAVENLY_VIRTUES = [5, 8, 7, 8, 11, 10, 11, 2, 1, 2, 5, 4];
    private const MONTHLY_VIRTUES = [11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5, 2];

    public function code(): string
    {
        return 'lesson.zhunfu';
    }

    public function requiredContext(): array
    {
        return ['people.querent.nianming', 'people.querent.xingnian'];
    }

    public function notEvaluatedInfo(): array
    {
        return [
            'name' => '迍福课',
            'notice' => '第四福需要占人本命与行年信息（出生时间与性别），当前未进行判断。',
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $monthBranch = $facts->get('yuezhi');
        $yearBranch = $facts->get('nianzhi');
        $sike = $facts->get('sike');
        $tianpan = $facts->get('tianpan');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($monthBranch)
            || ! is_array($sike) || count($sike) < 8 || ! is_array($tianpan)
            || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            return null;
        }

        $dayUpper = $sike[1] ?? null;
        $branchUpper = $sike[5] ?? null;
        if (! is_int($dayUpper) || ! is_int($branchUpper)) {
            return null;
        }

        $initialGround = array_search($initial, $tianpan, true);
        if ($initialGround === false) {
            return null;
        }

        $initialElement = $facts->branchElement($initial);
        $groundElement = $facts->branchElement($initialGround);
        $dayStemElement = $facts->stemElement($dayStem);
        if ($initialElement === null || $groundElement === null || $dayStemElement === null) {
            return null;
        }

        $wangXiang = $facts->wangXiangElements();
        $wangElement = $wangXiang['wang'] ?? null;
        if (! is_int($wangElement)) {
            return null;
        }

        $initialGeneral = $facts->generalRidingBranch($initial);
        $finalGeneral = $facts->generalRidingBranch($final);
        $dayUpperGeneral = $facts->generalRidingBranch($dayUpper);
        $branchUpperGeneral = $facts->generalRidingBranch($branchUpper);

        $tomb = self::TOMB_BY_ELEMENT[$wangElement] ?? null;
        $hill = is_int($tomb) ? BranchRelations::clashOf($tomb) : null;
        $initialState = $facts->branchSeasonalState($initial);
        $groundState = $facts->branchSeasonalState($initialGround);
        $finalState = $facts->branchSeasonalState($final);

        // 八迍。
        $zhun1 = $initialState === '死';
        $zhun2 = $groundState === '旺' && $this->branchRestrainsBranch($facts, $initialGround, $initial);
        $zhun3 = is_int($tomb) && ($initial === $tomb || $initial === $hill);
        $zhun4 = $this->branchRestrainsBranch($facts, $initialGround, $initial);
        $zhun5 = is_int($initialGeneral) && in_array($initialGeneral, self::OMINOUS_GENERALS, true);

        $xingHaiRoutes = $this->initialXingHaiRoutes(
            $facts,
            $dayStem,
            $dayBranch,
            $monthBranch,
            is_int($yearBranch) ? $yearBranch : null,
            $initial,
            $initialGround,
            $middle,
            $final,
            $dayUpper,
            $branchUpper,
        );
        $transmissionHitsGrave = in_array(0, [$initial, $middle, $final], true); // 子为坟墓星。
        $zhun6 = $xingHaiRoutes !== [] && $transmissionHitsGrave;

        $rawRelations = [];
        for ($i = 0; $i < 4; $i++) {
            $value = $facts->get('wuxingShengke'.$i);
            if (! is_array($value) || ! isset($value[0]) || ! is_int($value[0])) {
                return null;
            }
            $rawRelations[] = $value[0];
        }
        $zhun7 = in_array(-1, $rawRelations, true);

        $dayOminousConflict = is_int($dayUpperGeneral)
            && in_array($dayUpperGeneral, self::OMINOUS_GENERALS, true)
            && $this->stemBranchConflict($facts, $dayStem, $dayUpper);
        $branchOminousConflict = is_int($branchUpperGeneral)
            && in_array($branchUpperGeneral, self::OMINOUS_GENERALS, true)
            && $this->branchConflict($facts, $dayBranch, $branchUpper);
        $zhun8 = $dayOminousConflict || $branchOminousConflict;

        // 五福。
        $fu1 = $initialState === '死' && in_array($finalState, ['旺', '相'], true);

        $motherCandidates = [];
        foreach (['middle' => $middle, 'final' => $final] as $position => $branch) {
            $virtueTypes = $this->virtueTypes($dayStem, $dayBranch, $monthBranch, $branch);
            if ($this->branchGeneratesBranch($facts, $branch, $initial) && $virtueTypes !== []) {
                $motherCandidates[] = [
                    'position' => $position,
                    'branch' => $branch,
                    'virtue_types' => $virtueTypes,
                    'general' => $facts->generalRidingBranch($branch),
                ];
            }
        }
        $fu2 = $motherCandidates !== [];

        $finalVirtueTypes = $this->virtueTypes($dayStem, $dayBranch, $monthBranch, $final);
        $finalIsAuspicious = (is_int($finalGeneral) && in_array($finalGeneral, self::AUSPICIOUS_GENERALS, true))
            || $finalVirtueTypes !== [];
        $fu3 = $zhun5 && $finalIsAuspicious;

        $initialIsDayGhost = $this->elementRestrains($initialElement, $dayStemElement);
        $yearMingRescues = $this->yearMingRescues($facts, $tianpan, $initial);
        $fu4 = $initialIsDayGhost && $yearMingRescues['matched_via'] !== [];

        $fu5 = in_array($facts->branchSeasonalState($dayUpper), ['旺', '相'], true)
            || in_array($facts->branchSeasonalState($branchUpper), ['旺', '相'], true);

        $conditions = [
            'zhun1_dead_initial' => $zhun1,
            'zhun2_wang_ground_restrains_initial' => $zhun2,
            'zhun3_hill_or_tomb' => $zhun3,
            'zhun4_ground_restrains_initial' => $zhun4,
            'zhun5_initial_ominous_general' => $zhun5,
            'zhun6_xing_hai_and_grave' => $zhun6,
            'zhun7_lower_restrains_upper' => $zhun7,
            'zhun8_ominous_on_day_or_branch_conflict' => $zhun8,
            'fu1_dead_to_wang_xiang' => $fu1,
            'fu2_mother_generates_with_virtue' => $fu2,
            'fu3_ominous_to_auspicious' => $fu3,
            'fu4_day_ghost_restrained_by_year_ming' => $fu4,
            'fu5_wang_xiang_on_day_or_branch' => $fu5,
        ];

        if (in_array(false, $conditions, true)) {
            return null;
        }

        $branchName = fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $generalName = fn (?int $general): string => is_int($general) ? (PanCalculator::$tianjiang[$general] ?? '?') : '?';

        $foundations = [
            ['title' => '一迍·死气发用', 'detail' => "初传{$branchName($initial)}为{$initialState}气。"],
            ['title' => '二迍·旺气下胜', 'detail' => "初传{$branchName($initial)}临{$branchName($initialGround)}，下神为{$groundState}气且克初传。"],
            ['title' => '三迍·上见丘墓', 'detail' => "当令墓为{$branchName($tomb)}、丘为{$branchName($hill)}，初传{$branchName($initial)}命中其一。"],
            ['title' => '四迍·下见仇克', 'detail' => "下神{$branchName($initialGround)}克初传{$branchName($initial)}。"],
            ['title' => '五迍·乘凶将', 'detail' => "初传{$branchName($initial)}乘{$generalName($initialGeneral)}。"],
            ['title' => '六迍·带刑害、传逢坟墓', 'detail' => '初传命中刑害路径：'.implode('、', array_column($xingHaiRoutes, 'label')).'；三传见子。'],
            ['title' => '七迍·下贼上', 'detail' => '四个原始课位至少一处下贼上。'],
            ['title' => '八迍·凶神临日辰相克', 'detail' => $dayOminousConflict && $branchOminousConflict ? '日位、辰位均命中。' : ($dayOminousConflict ? '日位命中。' : '辰位命中。')],
            ['title' => '一福·初死终旺', 'detail' => "初传{$branchName($initial)}为{$initialState}，末传{$branchName($final)}为{$finalState}。"],
            ['title' => '二福·子母相生', 'detail' => '生初传且带德的母神：'.implode('、', array_map(fn (array $m): string => ($m['position'] === 'middle' ? '中传' : '末传').$branchName($m['branch']), $motherCandidates)).'。'],
            ['title' => '三福·始凶终吉', 'detail' => "初传乘{$generalName($initialGeneral)}，末传{$branchName($final)}具吉神/吉将条件。"],
            ['title' => '四福·年命制初', 'detail' => "初传{$branchName($initial)}为日鬼；".implode('、', $yearMingRescues['matched_labels']).'克制初传。'],
            ['title' => '五福·旺相临日辰', 'detail' => '日上神或辰上神得旺相。'],
        ];

        return new RuleMatch(
            code: $this->code(),
            name: '迍福课',
            group: '六十四课',
            description: '八迍五福十三项俱备；歧义项在有古籍依据范围内取宽解释，整体仍全项合取。',
            gua: '屯',
            guaSymbol: '䷂',
            xiang: '八迍并用，忧患将至；五福相逢，变忧为喜。',
            evidence: [
                'conditions' => $conditions,
                'zhun_count' => 8,
                'fu_count' => 5,
                'initial' => ['branch' => $initial, 'ground' => $initialGround, 'state' => $initialState, 'general' => $initialGeneral],
                'middle' => ['branch' => $middle],
                'final' => ['branch' => $final, 'state' => $finalState, 'general' => $finalGeneral, 'virtue_types' => $finalVirtueTypes],
                'hill_tomb' => ['wang_element' => $wangElement, 'tomb' => $tomb, 'hill' => $hill],
                'xing_hai_routes' => $xingHaiRoutes,
                'transmission_hits_grave' => $transmissionHitsGrave,
                'raw_lesson_relations' => $rawRelations,
                'day_ominous_conflict' => $dayOminousConflict,
                'branch_ominous_conflict' => $branchOminousConflict,
                'mother_candidates' => $motherCandidates,
                'year_ming_rescues' => $yearMingRescues,
                'foundations' => $foundations,
                'judgments' => [[
                    'label' => '迍福课成立',
                    'evidence' => '八迍8/8、五福5/5全部成立，按屯卦先难后解。',
                ]],
                'interpretation_policy' => '单项宽判、整体严判：原文未限定且古籍存在多个可辩护解释时取并集，十三项仍全部成立方成课。',
                'uncovered' => [
                    '四库本注明“此课不明姑存之”；本实现不宣称消除了古籍歧义。',
                    '刑害、吉神等宽口径只纳入已有明确文献或项目规则依据的路径，后续可按新证据增删单一路径。',
                ],
            ],
        );
    }

    /** @return list<array{type:string, source:string, branch:int, label:string}> */
    private function initialXingHaiRoutes(
        PanFacts $facts,
        int $dayStem,
        int $dayBranch,
        int $monthBranch,
        ?int $yearBranch,
        int $initial,
        int $initialGround,
        int $middle,
        int $final,
        int $dayUpper,
        int $branchUpper,
    ): array {
        $routes = [];

        if ((self::STEM_PUNISHMENTS[$dayStem] ?? null) === $initial) {
            $routes[] = ['type' => 'xing', 'source' => 'day_stem', 'branch' => $initial, 'label' => '日干刑初传'];
        }

        $related = [
            'initial_ground' => $initialGround,
            'day_branch' => $dayBranch,
            'month_branch' => $monthBranch,
            'middle_transmission' => $middle,
            'final_transmission' => $final,
            'day_upper' => $dayUpper,
            'branch_upper' => $branchUpper,
        ];
        if ($yearBranch !== null) {
            $related['year_branch'] = $yearBranch;
        }

        $sourceLabels = [
            'initial_ground' => '初传所临下神',
            'day_branch' => '日支',
            'month_branch' => '月支',
            'year_branch' => '太岁支',
            'middle_transmission' => '中传',
            'final_transmission' => '末传',
            'day_upper' => '日上神',
            'branch_upper' => '辰上神',
        ];

        foreach ($related as $source => $branch) {
            if ($branch === $initial) {
                continue;
            }
            $sourceLabel = $sourceLabels[$source] ?? $source;

            if ((self::BRANCH_PUNISHMENTS[$initial] ?? null) === $branch
                || (self::BRANCH_PUNISHMENTS[$branch] ?? null) === $initial) {
                $routes[] = ['type' => 'xing', 'source' => $source, 'branch' => $branch, 'label' => $sourceLabel.'与初传相刑'];
            }

            if (BranchRelations::isHai($initial, $branch)) {
                $routes[] = ['type' => 'hai', 'source' => $source, 'branch' => $branch, 'label' => $sourceLabel.'与初传六害'];
            }
        }

        return $routes;
    }

    /** @return list<string> */
    private function virtueTypes(int $dayStem, int $dayBranch, int $monthBranch, int $branch): array
    {
        $map = [
            '干德' => self::STEM_VIRTUES[$dayStem] ?? null,
            '支德' => self::BRANCH_VIRTUES[$dayBranch] ?? null,
            '天德' => self::HEAVENLY_VIRTUES[$monthBranch] ?? null,
            '月德' => self::MONTHLY_VIRTUES[$monthBranch] ?? null,
        ];

        return array_keys(array_filter($map, fn (?int $value): bool => $value === $branch));
    }

    /** @param array<int, int> $tianpan */
    private function yearMingRescues(PanFacts $facts, array $tianpan, int $initial): array
    {
        $person = $facts->personByRole('querent');
        $matchedVia = [];
        $matchedLabels = [];
        $details = [];

        if (! is_array($person)) {
            return ['matched_via' => [], 'matched_labels' => [], 'details' => []];
        }

        foreach (['nianming' => '本命', 'xingnian' => '行年'] as $key => $label) {
            $ground = $person[$key] ?? null;
            if (! is_int($ground)) {
                continue;
            }

            if ($this->branchRestrainsBranch($facts, $ground, $initial)) {
                $matchedVia[] = $key.'_ground';
                $matchedLabels[] = $label;
                $details[] = ['route' => $key.'_ground', 'branch' => $ground];
            }

            $upper = $tianpan[$ground] ?? null;
            if (is_int($upper) && $this->branchRestrainsBranch($facts, $upper, $initial)) {
                $matchedVia[] = $key.'_upper';
                $matchedLabels[] = $label.'上神';
                $details[] = ['route' => $key.'_upper', 'branch' => $upper];
            }
        }

        return [
            'matched_via' => array_values(array_unique($matchedVia)),
            'matched_labels' => array_values(array_unique($matchedLabels)),
            'details' => $details,
        ];
    }

    private function stemBranchConflict(PanFacts $facts, int $stem, int $branch): bool
    {
        $stemElement = $facts->stemElement($stem);
        $branchElement = $facts->branchElement($branch);

        return $stemElement !== null && $branchElement !== null
            && ($this->elementRestrains($stemElement, $branchElement) || $this->elementRestrains($branchElement, $stemElement));
    }

    private function branchConflict(PanFacts $facts, int $a, int $b): bool
    {
        $aElement = $facts->branchElement($a);
        $bElement = $facts->branchElement($b);

        return $aElement !== null && $bElement !== null
            && ($this->elementRestrains($aElement, $bElement) || $this->elementRestrains($bElement, $aElement));
    }

    private function branchRestrainsBranch(PanFacts $facts, int $source, int $target): bool
    {
        $sourceElement = $facts->branchElement($source);
        $targetElement = $facts->branchElement($target);

        return $sourceElement !== null && $targetElement !== null && $this->elementRestrains($sourceElement, $targetElement);
    }

    private function branchGeneratesBranch(PanFacts $facts, int $source, int $target): bool
    {
        $sourceElement = $facts->branchElement($source);
        $targetElement = $facts->branchElement($target);

        return $sourceElement !== null && $targetElement !== null
            && $targetElement === ($sourceElement + 1) % 5;
    }

    private function elementRestrains(int $source, int $target): bool
    {
        return $target === ($source + 2) % 5;
    }
}
