<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》卷八《课经集（二）·德庆课》严格口径判断德庆课。
 *
 * 严格口径（双必要条件，必须同时满足）：
 *   1. 初传属于以下四类德神之一：日干德、日支德、天德、月德；
 *   2. 初传本身乘六吉将之一（贵人、六合、青龙、太常、太阴、天后）；
 *   3. 初传本身加临占人本命宫（tianpan[nianming]）或行年宫（tianpan[xingnian]），
 *      即本命宫上神 = initial 或 行年宫上神 = initial，任一即可。
 *
 * 三项同时落在同一个初传神上；本命与行年仅取其一即足。
 *
 * 关键定义（沿用《六壬大全》"年命乘墓、坐墓"旁证）：
 *   - 本命宫上神 = tianpan[nianming]（从地盘本命宫向上取神）；
 *   - 行年宫上神 = tianpan[xingnian]（从地盘行年宫向上取神）；
 *   - "乘吉将" 考察 generalRidingBranch(上神/initial)，不检查"本命支或行年支
 *     作为天盘神在其他位置所乘天将"。
 *
 * 缺占人 / 缺本命或行年字段时，按项目既有 ContextAwareRule / not_evaluated
 * 机制标记"需要占人本命与行年信息，当前未进行判断"，不构成"不成立"。
 *
 * 四类德神映射（与《选择纪要》天德表、ShitaiRule::DAY_VIRTUES 同源）：
 *   - 干德：[2,8,5,11,5,2,8,5,11,5]（甲己寅、乙庚申、丙辛戊癸巳、丁壬亥）
 *   - 支德：[5,6,7,8,9,10,11,0,1,2,3,4]（子日起巳，顺行十二辰）
 *   - 天德：[5,8,7,8,11,10,11,2,1,2,5,4]（按月支索引 0=子）
 *   - 月德：[11,8,5,2,11,8,5,2,11,8,5,2]（按月支索引 0=子；甲寅、丙巳、庚申、壬亥）
 *
 * 衍生结构（德神为鬼、德空、乘龙尤吉、神将外战、四煞没四维等）暂不作为主体
 * 必要条件，详见 docs/课经/26-德庆课.md。
 */
final class DeQingRule implements ContextAwareRule
{
    /** @var array<int, int> 日干德位：寅、申、巳、亥 循环，与 ShitaiRule::DAY_VIRTUES 同源。 */
    private const STEM_VIRTUES = [2, 8, 5, 11, 5, 2, 8, 5, 11, 5];

    /**
     * 支德表：子日起巳，顺行十二辰。
     *
     * @var array<int, int> 索引为日支（0=子、1=丑、…、11=亥），值为德支。
     */
    private const BRANCH_VIRTUES = [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4];

    /**
     * 天德支表（按月支索引，子月起；索引 0=子）。
     *
     * 逐月展开（子月起、顺序排列）：
     *   子月巳、丑月申、寅月未、卯月申、辰月亥、巳月戌、
     *   午月亥、未月寅、申月丑、酉月寅、戌月巳、亥月辰。
     *
     * @var array<int, int>
     */
    private const HEAVENLY_VIRTUES = [5, 8, 7, 8, 11, 10, 11, 2, 1, 2, 5, 4];

    /**
     * 月德支表（按月支索引，子月起；索引 0=子）。
     *
     * 月德只涉及甲、丙、庚、壬四个阳干；其六壬寄宫与禄位落支结果完全相同：
     *   甲寅、丙巳、庚申、壬亥。
     *
     * @var array<int, int>
     */
    private const MONTHLY_VIRTUES = [11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5, 2];

    /** @var list<int> 六吉将：贵、合、龙、常、阴、后。 */
    private const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    protected const RULE_CODE = 'lesson.de_qing';

    protected const NAME = '德庆课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '四类德神之一发用，且该德神乘六吉将，且该德神加临占人本命宫或行年宫；三项须落在同一初传神上。';

    protected const GUA = '需';

    protected const GUA_SYMBOL = '䷄';

    protected const XIANG = '德神在位，诸杀潜藏。囚禁的释，病危无妨。婚成佳配，孕产贤郎。凡占谋望，事事吉昌。';

    /** @var list<string> */
    private const UNCOVERED = [
        '德神为鬼仍吉的衍生判断（"德神为鬼，占功名利病无妨"）未实现',
        '德空、神将外战被刑克的减损结构未实现',
        '四煞没四维（斩关结构）相关联条件未实现',
        '乘龙尤吉（初传德神同时乘青龙）的加权条件未实现',
        '戊子日巳德归亥乘元武夹克的"减德"反例未作为显式判断保留',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function requiredContext(): array
    {
        return ['people.querent.nianming', 'people.querent.xingnian'];
    }

    public function notEvaluatedInfo(): array
    {
        return [
            'name' => self::NAME,
            'notice' => '需要占人本命与行年信息（出生时间与性别），当前未进行判断。',
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $monthBranch = $facts->get('yuezhi');
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($monthBranch) || ! is_int($initial)) {
            return null;
        }
        if (! is_array($tianpan)) {
            return null;
        }

        $stemVirtue = self::STEM_VIRTUES[$dayStem] ?? null;
        $branchVirtue = self::BRANCH_VIRTUES[$dayBranch] ?? null;
        $heavenlyVirtue = self::HEAVENLY_VIRTUES[$monthBranch] ?? null;
        $monthlyVirtue = self::MONTHLY_VIRTUES[$monthBranch] ?? null;

        if ($stemVirtue === null || $branchVirtue === null || $heavenlyVirtue === null || $monthlyVirtue === null) {
            return null;
        }

        // 1) 初传命中四类德神之一？
        $virtueMap = [
            'stem' => $stemVirtue,
            'branch' => $branchVirtue,
            'heavenly' => $heavenlyVirtue,
            'monthly' => $monthlyVirtue,
        ];
        $matchedVirtueTypes = [];
        foreach ($virtueMap as $type => $branch) {
            if ($initial === $branch) {
                $matchedVirtueTypes[] = $type;
            }
        }
        $initialIsVirtue = $matchedVirtueTypes !== [];
        if (! $initialIsVirtue) {
            return null;
        }

        // 2) 初传本身乘六吉将？
        $initialGeneral = $facts->generalRidingBranch($initial);
        $initialRidesAuspicious = is_int($initialGeneral)
            && in_array($initialGeneral, self::AUSPICIOUS_GENERALS, true);
        if (! $initialRidesAuspicious) {
            return null;
        }

        // 3) 初传加临本命宫或行年宫？（取上下文）
        $yearMing = $this->resolveYearMing($facts, $tianpan);
        if (! $yearMing['present']) {
            // 上下文缺失由 PanRuleEngine 的 ContextAwareRule 路径在引擎层拦截，
            // match() 不应在此情况下返回命中。保留为保守的 null。
            return null;
        }

        $matchedVia = [];
        if ($yearMing['nianming']['initial_is_upper'] === true) {
            $matchedVia[] = 'nianming';
        }
        if ($yearMing['xingnian']['initial_is_upper'] === true) {
            $matchedVia[] = 'xingnian';
        }
        if ($matchedVia === []) {
            return null;
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
                'virtue_types' => $matchedVirtueTypes,
                'initial_branch' => $initial,
                'initial_general' => $initialGeneral,
                'initial_rides_auspicious' => true,
                'day_stem' => $dayStem,
                'day_branch' => $dayBranch,
                'month_branch' => $monthBranch,
                'stem_virtue' => $stemVirtue,
                'branch_virtue' => $branchVirtue,
                'heavenly_virtue' => $heavenlyVirtue,
                'monthly_virtue' => $monthlyVirtue,
                'nianming' => $yearMing['nianming'],
                'xingnian' => $yearMing['xingnian'],
                'matched_via' => $matchedVia,
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /**
     * 读取占人本命宫上神与行年宫上神，并比对是否等于初传。
     *
     * 严格按《六壬大全》"年命乘墓、坐墓"旁证：
     *   "年命在子，辰加子为乘墓，天上子临辰为坐墓。"
     *   → 应当从地盘年命宫向上观察"上神"。
     *
     * @param  array<int, int>  $tianpan
     * @return array{
     *     present: bool,
     *     nianming: array{ground: ?int, upper: ?int, general: ?int, auspicious: bool, initial_is_upper: bool},
     *     xingnian: array{ground: ?int, upper: ?int, general: ?int, auspicious: bool, initial_is_upper: bool}
     * }
     */
    private function resolveYearMing(PanFacts $facts, array $tianpan): array
    {
        $blank = [
            'present' => false,
            'nianming' => ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false, 'initial_is_upper' => false],
            'xingnian' => ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false, 'initial_is_upper' => false],
        ];

        $querent = $facts->personByRole('querent');
        if (! is_array($querent)) {
            return $blank;
        }

        $inspect = function (?int $ground) use ($facts, $tianpan): array {
            if (! is_int($ground) || ! isset($tianpan[$ground]) || ! is_int($tianpan[$ground])) {
                return ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false, 'initial_is_upper' => false];
            }
            $upper = $tianpan[$ground];
            $general = $facts->generalRidingBranch($upper);

            return [
                'ground' => $ground,
                'upper' => $upper,
                'general' => $general,
                'auspicious' => is_int($general) && in_array($general, self::AUSPICIOUS_GENERALS, true),
                'initial_is_upper' => false, // 由 caller 在知道 $initial 后回填
            ];
        };

        $initial = $facts->get('sanchuan0');

        $nianming = $inspect($querent['nianming'] ?? null);
        $xingnian = $inspect($querent['xingnian'] ?? null);

        if (is_int($initial)) {
            if ($nianming['upper'] !== null && $nianming['upper'] === $initial) {
                $nianming['initial_is_upper'] = true;
            }
            if ($xingnian['upper'] !== null && $xingnian['upper'] === $initial) {
                $xingnian['initial_is_upper'] = true;
            }
        }

        $present = $nianming['upper'] !== null || $xingnian['upper'] !== null;

        return [
            'present' => $present,
            'nianming' => $nianming,
            'xingnian' => $xingnian,
        ];
    }
}
