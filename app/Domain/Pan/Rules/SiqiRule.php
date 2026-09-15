<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》正文"斗罡系日辰阴阳发用"严格口径判断死奇课。
 *
 * 规则边界：
 *  - 斗罡 / 天罡 = 辰(4)，不接纳月将同名为"罡"的任何旁证。
 *  - "日辰阴阳"即四课，第一课至第四课全部允许。
 *  - 不得退化为"天罡加日干或日支发用"（《图解六壬大全》过窄版本）。
 *  - 不得简化为"辰发用"（必须同时是四课上神）。
 *  - 日鬼、日墓、灾煞、劫煞、吉将集合、救解程序、日奇、月奇、刑奇
 *    以及"三死课"的组合公式本轮均冻结为未实现项。
 */
final class SiqiRule implements PanRule
{
    use LessonDefinitionDefaults;

    protected const RULE_CODE = 'lesson.siqi';

    protected const NAME = '死奇课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '天罡辰系日辰阴阳发用：初传为辰，且辰为四课上神之一。';

    protected const GUA = '未济';

    protected const GUA_SYMBOL = '䷿';

    protected const XIANG = '辰为天罡，刑狱之曜。疾病死期，征战凶兆。论讼被囚，干贵失靠。婚嫁出行，祸患自招。';

    /** 天罡在十二地支中的索引：辰。 */
    private const GANG = 4;

    /** 孟 = 寅巳申亥（2,5,8,11）；仲 = 子卯午酉（0,3,6,9）；季 = 丑辰未戌（1,4,7,10）。 */
    private const MENG_BRANCHES = [2, 5, 8, 11];

    private const ZHONG_BRANCHES = [0, 3, 6, 9];

    private const JI_BRANCHES = [1, 4, 7, 10];

    /** 天将固定序号：白虎 = 7。 */
    private const GENERAL_BAIHU = 7;

    /** @var list<string> */
    private const UNCOVERED = [
        '《大全》"日鬼"是否构成死奇课增强条件，程序范围尚未冻结，未实现',
        '《大全》"日墓"是否构成死奇课增强条件，程序范围尚未冻结，未实现',
        '《大全》"灾煞"、"劫煞"在死奇课中的具体检索位置与数量要求尚未冻结，未实现',
        '《大全》"恶煞相并克贼"的精确克贼结构尚未冻结，未实现',
        '《大全》"德"、"合"、"相生"等救解或增强条件的吉凶边界尚未冻结，未实现',
        '《大全》"吉将"的具体集合尚未仅凭正文冻结，未实现',
        '《大全》"六处有冲克救神"中"六处"与"冲克救神"的精确程序范围尚未冻结，未实现',
        '《大全》"日奇"的具体程序落点尚未冻结，未实现',
        '《大全》"月奇"、"刑奇"的完整判断尚未实现',
        '《订讹》"月行度到角亢"涉及月宿精确度数，本轮未实现',
        '《订讹》"月宿临太岁日辰"涉及月宿与太岁、日、辰同位的具体组合，本轮未实现',
        '《订讹》"六处有月将照之"中"六处"与"照之"的具体程序范围尚未冻结，未实现',
        '《订讹》"天罡太阴同见"扩展"死奇回光"口径尚未冻结，未实现',
        '三死课（需日、辰、岁中哪些同时成立）的组合公式尚未冻结，本轮不输出"三死课"标签',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $sike = $facts->get('sike');
        $tianpan = $facts->get('tianpan');

        if (! is_int($initial) || ! is_array($sike) || ! is_array($tianpan)) {
            return null;
        }

        // matcher 第 1 条：初传必须为天罡。
        if ($initial !== self::GANG) {
            return null;
        }

        // matcher 第 2 条：四课数组必须齐全且至少含四对（八个元素）。
        if (count($sike) < 8) {
            return null;
        }

        // 严格按《大全》"日辰阴阳"四课定义：sike[1,3,5,7] 为四课上神。
        $upperBranches = [
            1 => $sike[1] ?? null,
            3 => $sike[3] ?? null,
            5 => $sike[5] ?? null,
            7 => $sike[7] ?? null,
        ];

        $routeIndexes = [];
        $polarityLabels = [
            1 => '日阳',
            3 => '日阴',
            5 => '辰阳',
            7 => '辰阴',
        ];
        foreach ($upperBranches as $idx => $value) {
            if ($value === self::GANG) {
                $routeIndexes[] = $idx;
            }
        }

        // matcher 第 3 条：天罡必须实际是某一课上神，不能仅靠"初传为辰"成立。
        if ($routeIndexes === []) {
            return null;
        }

        $routes = array_map(static fn (int $idx): array => [
            'lesson' => intdiv($idx, 2) + 1,
            'polarity' => $polarityLabels[$idx] ?? '未知',
        ], $routeIndexes);

        // matcher 第 4 条：天罡所临地盘必须可由天地盘求出。
        $gangGround = $facts->heavenBranchGroundPosition(self::GANG);
        if ($gangGround === null) {
            return null;
        }

        $judgments = $this->judgments($facts, $gangGround);

        return new RuleMatch(
            code: $this->code(), name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG,
            evidence: [
                'initial' => $initial,
                'sike_upper_branches' => array_values($upperBranches),
                'routes' => $routes,
                'route_indexes' => $routeIndexes,
                'gang_ground' => $gangGround,
                'foundations' => [
                    ['title' => '天罡发用', 'detail' => '初传为辰即天罡。'],
                    ['title' => '罡加四课', 'detail' => '辰同时为'.count($routes).'课上神；'.self::summarizeRoutes($routes).'。'],
                    ['title' => '天罡所临地盘', 'detail' => '天罡辰所临地盘的求法为 array_search(4, tianpan, true)；本盘结果为'
                        .self::branchName($gangGround)."（地盘第 {$gangGround} 位）。"],
                ],
                'judgments' => $judgments,
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /**
     * @return list<array{code: string, effect: string, label: string, evidence: string}>
     */
    private function judgments(PanFacts $facts, int $gangGround): array
    {
        $judgments = [];

        // 1. 初传天罡乘白虎：《大全》"及乘白虎，为必死之兆，大凶"。
        $general = $facts->generalRidingBranch(self::GANG);
        if ($general === self::GENERAL_BAIHU) {
            $judgments[] = [
                'code' => 'gang_rides_white_tiger',
                'effect' => 'ominous',
                'label' => '天罡乘白虎',
                'evidence' => '辰为天罡同时乘白虎；《大全》称"及乘白虎，为必死之兆，大凶"。',
            ];
        }

        // 2. 天罡临日、临辰、临岁：三处分别判断，按《大全》"天罡临日，旬内忧；临辰，月内忧；临岁，岁内忧"。
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $nianzhi = $facts->get('nianzhi');

        if (is_int($rigan)) {
            $lodging = $facts->stemLodgingBranch($rigan);
            if ($lodging !== null && $gangGround === $lodging) {
                $judgments[] = [
                    'code' => 'gang_at_day',
                    'effect' => 'ominous',
                    'label' => '天罡临日',
                    'evidence' => '天罡辰临日干'.self::stemName($rigan).'寄宫'.self::branchName($lodging).'；原文称"旬内忧"。',
                ];
            }
        }
        if (is_int($rizhi) && $gangGround === $rizhi) {
            $judgments[] = [
                'code' => 'gang_at_branch',
                'effect' => 'ominous',
                'label' => '天罡临辰',
                'evidence' => '天罡辰临日支'.self::branchName($rizhi).'；原文称"月内忧"。',
            ];
        }
        if (is_int($nianzhi) && $gangGround === $nianzhi) {
            $judgments[] = [
                'code' => 'gang_at_year',
                'effect' => 'ominous',
                'label' => '天罡临岁',
                'evidence' => '天罡辰临年支'.self::branchName($nianzhi).'；原文称"岁内忧"。',
            ];
        }

        // 3. 孟仲季判断：按《大全》"孟忧二亲、仲忧己身、季忧妻奴"。
        $phase = null;
        if (in_array($gangGround, self::MENG_BRANCHES, true)) {
            $phase = '孟';
        } elseif (in_array($gangGround, self::ZHONG_BRANCHES, true)) {
            $phase = '仲';
        } elseif (in_array($gangGround, self::JI_BRANCHES, true)) {
            $phase = '季';
        }
        if ($phase !== null) {
            $detail = match ($phase) {
                '孟' => '忧二亲',
                '仲' => '忧己身',
                '季' => '忧妻奴',
            };
            $judgments[] = [
                'code' => 'gang_phase_'.$phase,
                'effect' => 'ominous',
                'label' => '天罡临'.$phase,
                'evidence' => '天罡临地盘'.self::branchName($gangGround).'属'.$phase.'；正文称"'.$detail.'"。',
            ];
        }

        // 4. 死奇回光：月将=辰。
        $yuejiang = $facts->get('yuejiang');
        if (is_int($yuejiang) && $yuejiang === self::GANG) {
            $judgments[] = [
                'code' => 'siqi_huiguang',
                'effect' => 'auspicious',
                'label' => '死奇回光',
                'evidence' => '辰即天罡，同时为本课月将；《大全》称"辰为月将尤美，为死奇回光，除祸为福"。',
            ];
        }

        return $judgments;
    }

    /**
     * @param  list<array{lesson: int, polarity: string}>  $routes
     */
    private static function summarizeRoutes(array $routes): string
    {
        $parts = [];
        foreach ($routes as $route) {
            $parts[] = '第'.$route['lesson'].'课（'.$route['polarity'].'）';
        }

        return implode('、', $parts);
    }

    private static function branchName(int $branch): string
    {
        return PanCalculator::$dizhi[$branch] ?? '?';
    }

    private static function stemName(int $stem): string
    {
        return PanCalculator::$tiangan[$stem] ?? '?';
    }
}
