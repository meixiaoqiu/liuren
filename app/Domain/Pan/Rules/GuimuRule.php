<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按冻结结论 A' 判断鬼墓课。
 *
 * 规则边界：
 * 1. 正式 matcher 走 A'：初传必须是日鬼，且同时为日干墓或日支墓；
 * 2. 日鬼表严格采用《六壬大全》本课列出的同性相克表，壬癸各取两位（辰戌、丑未）；
 * 3. 日干、日支五行墓采用「木未、火戌、土辰、金丑、水辰」五行墓，不采用十干十二长生墓；
 * 4. 中传、末传出现鬼或墓不扩大为成课条件；
 * 5. 干上神或支上神必须发用不属于本课要求；本规则只检查初传。
 * 「《订讹》支鬼支墓」中的支鬼因支鬼尚无稳定古籍程序定义，暂不并入正式 matcher。
 */
final class GuimuRule implements PanRule
{
    public const RULE_CODE = 'lesson.guimu';

    public const NAME = '鬼墓课';

    public const GROUP = '六十四课';

    public const GUA = '困';

    public const GUA_SYMBOL = '䷮';

    public const DESCRIPTION = '日鬼发用，且初传同时为日干墓或日支墓，为鬼墓课。';

    public const XIANG = '五行克贼，死墓之乡。人丁多耗，家宅不昌。行人可至，病者颠狂。谋为迟滞，捕盗深藏。';

    /**
     * 日鬼表：基于《六壬大全》本课正文列出的「同性相克」日鬼；
     * 壬、癸各两位；不得用五行相克直接推导，也不得把「土克水」的辰戌丑未视作水干日鬼。
     *
     * @var array<int, list<int>>
     */
    private const DAY_GHOSTS = [
        0 => [8],      // 甲 -> 申
        1 => [9],      // 乙 -> 酉
        2 => [0],      // 丙 -> 子
        3 => [11],     // 丁 -> 亥
        4 => [2],      // 戊 -> 寅
        5 => [3],      // 己 -> 卯
        6 => [6],      // 庚 -> 午
        7 => [5],      // 辛 -> 巳
        8 => [4, 10],  // 壬 -> 辰、戌
        9 => [1, 7],   // 癸 -> 丑、未
    ];

    /**
     * 日干五行墓：木未、火戌、土辰、金丑、水辰。
     * 与 YangjiuRule 中已经使用的 STEM_ELEMENT_TOMBS 完全一致。
     *
     * @var array<int, int>
     */
    private const STEM_ELEMENT_TOMBS = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];

    /**
     * 十二支五行墓：子辰、丑辰、寅未、卯未、辰辰、巳戌、午戌、未辰、申丑、酉丑、戌辰、亥辰。
     *
     * @var array<int, int>
     */
    private const BRANCH_ELEMENT_TOMBS = [4, 4, 7, 7, 4, 10, 10, 4, 1, 1, 4, 4];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');

        if (! is_int($stem) || ! is_int($branch) || ! is_int($initial)
            || ! in_array($stem, range(0, 9), true) || ! in_array($branch, range(0, 11), true)
            || ! in_array($initial, range(0, 11), true)) {
            return null;
        }

        $dayGhosts = self::DAY_GHOSTS[$stem] ?? [];
        $isDayGhost = in_array($initial, $dayGhosts, true);
        $isStemTomb = $initial === self::STEM_ELEMENT_TOMBS[$stem];
        $isBranchTomb = $initial === self::BRANCH_ELEMENT_TOMBS[$branch];

        // 正式 matcher A'：日鬼 AND (日干墓 OR 日支墓)。
        if (! $isDayGhost) {
            return null;
        }
        if (! ($isStemTomb || $isBranchTomb)) {
            return null;
        }

        $branchName = static fn (int $i): string => PanCalculator::$dizhi[$i] ?? '?';
        $stemName = PanCalculator::$tiangan[$stem] ?? '?';
        $stemElementName = PanCalculator::$wuxing[$facts->stemElement($stem) ?? 0] ?? '?';
        $branchElementName = PanCalculator::$wuxing[$facts->branchElement($branch) ?? 0] ?? '?';
        $stemTombName = $branchName(self::STEM_ELEMENT_TOMBS[$stem]);
        $branchTombName = $branchName(self::BRANCH_ELEMENT_TOMBS[$branch]);
        $dayGanzhi = $stemName.$branchName($branch);

        $dayGhostNames = implode('、', array_map($branchName, $dayGhosts));
        $initialName = $branchName($initial);

        $matched = ['day_ghost'];
        if ($isStemTomb) {
            $matched[] = 'stem_tomb';
        }
        if ($isBranchTomb) {
            $matched[] = 'branch_tomb';
        }

        $foundations = [];
        $foundations[] = [
            'title' => '日干支与初传',
            'detail' => "日干支{$dayGanzhi}，初传{$initialName}。",
        ];
        $foundations[] = [
            'title' => '正文日鬼集合',
            'detail' => "日干{$stemName}对应正文日鬼为{$dayGhostNames}。",
        ];
        $foundations[] = [
            'title' => '日干五行及其墓',
            'detail' => "日干{$stemName}属{$stemElementName}，五行墓在{$stemTombName}。",
        ];
        $foundations[] = [
            'title' => '日支五行及其墓',
            'detail' => "日支{$branchName($branch)}属{$branchElementName}，五行墓在{$branchTombName}。",
        ];
        $foundations[] = [
            'title' => '命中入口',
            'detail' => implode('；', array_map(
                static fn (string $code): string => match ($code) {
                    'day_ghost' => "初传{$initialName}属于日鬼（{$dayGhostNames}）→ 日鬼发用",
                    'stem_tomb' => "初传{$initialName}等于日干{$stemName}的五行墓{$stemTombName} → 日干墓神具备",
                    'branch_tomb' => "初传{$initialName}等于日支{$branchName($branch)}的五行墓{$branchTombName} → 日支墓神具备",
                },
                $matched,
            )).'。',
        ];
        // ghost_tomb_combined：初传同时是日鬼与至少一种墓，是 A' 的主体成立标志。
        $combinedTargets = [];
        if ($isStemTomb) {
            $combinedTargets[] = "日干墓{$stemTombName}";
        }
        if ($isBranchTomb) {
            $combinedTargets[] = "日支墓{$branchTombName}";
        }
        if ($isStemTomb) {
            $combinedEvidence = '初传'.$initialName.'同时是日鬼与'.implode('、', $combinedTargets).'；《订讹》壬日辰例称「既作日鬼，又作日墓，故名鬼墓」。';
            if ($isBranchTomb) {
                $combinedEvidence .= '本盘兼具日支墓身份。';
            }
        } else {
            $combinedEvidence = '初传'.$initialName.'同时是日鬼与日支墓'.$branchTombName.'，符合《六壬大全》「凡日辰墓神及日鬼发用，为鬼墓课」；这是本项目对「日辰墓神」采用 A\' 的程序解释，不以《订讹》壬日辰例直接证明此子路线。';
        }
        $foundations[] = [
            'code' => 'ghost_tomb_combined',
            'title' => '主体成课（鬼墓兼见）',
            'detail' => $combinedEvidence.'鬼墓课成立。',
        ];

        $judgments = [];

        // 多路线同时命中时，给出汇总式描述，便于前台展示。
        if (count($matched) >= 3) {
            $judgments[] = [
                'code' => 'triple_ghost_and_tombs',
                'effect' => 'increase',
                'label' => '鬼墓三方兼见',
                'evidence' => '初传'.$initialName.'同时为日鬼、日干墓与日支墓，三种身份全部具备。',
                'matched' => true,
            ];
        }

        // 中传、末传情况仅作为非成课证据展示，便于排查误判。
        $notInInitial = [];
        if (is_int($middle) && (in_array($middle, $dayGhosts, true) || $middle === self::STEM_ELEMENT_TOMBS[$stem] || $middle === self::BRANCH_ELEMENT_TOMBS[$branch])) {
            $notInInitial[] = '中传'.$branchName($middle);
        }
        if (is_int($final) && (in_array($final, $dayGhosts, true) || $final === self::STEM_ELEMENT_TOMBS[$stem] || $final === self::BRANCH_ELEMENT_TOMBS[$branch])) {
            $notInInitial[] = '末传'.$branchName($final);
        }

        $evidence = [
            'day_ganzhi' => $dayGanzhi,
            'day_stem' => $stem,
            'day_branch' => $branch,
            'stem_element' => $facts->stemElement($stem),
            'branch_element' => $facts->branchElement($branch),
            'day_ghosts' => $dayGhosts,
            'stem_tomb' => self::STEM_ELEMENT_TOMBS[$stem],
            'branch_tomb' => self::BRANCH_ELEMENT_TOMBS[$branch],
            'initial' => $initial,
            'day_ghost_fayong' => $isDayGhost,
            'stem_tomb_fayong' => $isStemTomb,
            'branch_tomb_fayong' => $isBranchTomb,
            'matched_routes' => $matched,
            'ghost_tomb_combined' => true,
            'foundations' => $foundations,
            'judgments' => $judgments,
            'uncovered' => [
                '《订讹》「支鬼支墓」路线的支鬼尚无稳定古籍程序定义，暂不并入 matcher',
                '鬼在日上发用、墓门开格、蛇虎与墓神组合、卯酉位置组合等加强条件尚未程序化',
                '辰未日墓、丑戌夜墓、辰戌刚猛急速、丑未迟延柔缓等昼夜墓吉凶尚未程序化',
                '财、禄、官星、长生发用后中末见墓与日鬼盗气而中末逢墓等后续结构尚未程序化',
                '鬼墓有克制、冲破后的反凶为吉、传墓入墓、自墓传生、干支乘墓坐墓互换坐墓、干墓临支、墓神覆日等结构尚未程序化',
            ],
        ];

        if ($notInInitial !== []) {
            $evidence['uncovered'][] = '当前盘面'.implode('、', $notInInitial).'亦含鬼或墓，但本课仅以初传为准';
        }

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: $evidence,
        );
    }
}
