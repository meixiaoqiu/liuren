<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》卷八《课经集（二）·合欢课》正文最小严格口径判断合欢课。
 *
 * 正文最小严格口径（暂不分子格）三项必须同时满足：
 *   1. 天干作合：日干上神（即地盘日干寄宫位的上神）按本日所在旬寄宫天干，
 *      该寄宫天干与日干作天干五合（甲己、乙庚、丙辛、丁壬、戊癸）。
 *      课例："戊日干上丑遁得癸作合"。
 *   2. 支三合与六合发用（同时成立，非"或"）：
 *        (a) 初传与干上神作地支六合（子丑、寅亥、卯戌、辰酉、巳申、午未）；
 *        (b) 初传必须参与，并与日支及中传或末传共同构成完整地支三合
 *            （亥卯未／巳酉丑／寅午戌／申子辰）。
 *      课例"戊申日子时申将"：初传子与干上丑作六合；日支申、初传子、末传辰作申子辰三合。
 *   3. 占人年命俱乘吉将：本命宫上神属于六吉将（贵人、六合、青龙、太常、太阴、天后）
 *      之一，且行年宫上神属于六吉将之一。
 *
 * 关于"支三合与六合"取 AND 的依据：
 *   - 主体定义"凡课日辰遇天干作合，及支三合六合发用"用"及"字（连接词），不写"或"；
 *   - 课例"戊申日子时申将"同时具备六合（子丑）与三合（申子辰），无法从课例
 *     区分是 AND 还是 OR；
 *   - 旁证材料中"或日辰阴阳年命六处传逢吉将"是象曰对"六处"的连接词用法，
 *     主体定义本身的连接词是"及"，不应用"或"覆盖。
 *   - 六合 OR 三合的扩大口径作为候选在笔记中保留，本轮不采用。
 *
 * 缺占人 / 缺本命或行年字段时，按项目既有 ContextAwareRule / not_evaluated
 * 机制标记"需要占人本命与行年信息（出生时间与性别），当前未进行判断"，不构成"不成立"。
 *
 * 不包含的"候选规则"或"减损结构"：
 *   - 《订讹》变体（丙申反吟、辛卯干支相会、壬寅亥加寅、甲申干上亥、丁丑/己丑干上午、
 *     戊辰干上丑支上子、辛酉干上午、乙酉三传水局生日等）；
 *   - "六处逢吉将"、"四杀没于四维"等增强条件；
 *   - "合带刑害"、"合空"、"合带暗鬼克日"等减损结构；
 *   - "二阴作合"、"三传递生传财"等情境/增强条件；
 *   - 六合 OR 三合的扩大口径作为候选规则，本轮未采用。
 *
 * 衍生结构与候选规则详见 docs/课经/27-合欢课.md。
 */
final class HeHuanRule implements ContextAwareRule
{
    /** @var list<int> 六吉将：贵、合、龙、常、阴、后。 */
    private const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    protected const RULE_CODE = 'lesson.he_huan';

    protected const NAME = '合欢课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干上神的寄宫天干与日干作天干五合，且初传与干上神作地支六合，且初传与日支及中传或末传共同构成完整地支三合，且占人本命宫上神与行年宫上神各乘六吉将；缺占人本命与行年信息时按尚未判断处理。';

    protected const GUA = '井';

    protected const GUA_SYMBOL = '䷯';

    protected const XIANG = '乾坤匹配，奇偶交并，占孕迟生，行人荣省，名利乔迁，财喜欢称，婚姻天缘，万事佳庆。';

    /** @var list<string> */
    private const UNCOVERED = [
        '六合 OR 三合的扩大口径作为异读在笔记中保留，本轮采用正文最小严格口径',
        '《订讹》变体（丙申反吟、辛卯干支相会、壬寅亥加寅、甲申干上亥、丁丑/己丑干上午、戊辰干上丑支上子、辛酉干上午、乙酉三传水局生日等）尚未作为格实现',
        '"六处逢吉将"、"四杀没于四维"等增强条件未实现',
        '"合带刑害"（蜜里藏砒）减损结构未实现',
        '"合空"（合逢旬空）减损结构未实现',
        '"合带暗鬼克日，乘蛇虎雀"减损结构未实现',
        '"二阴作合"（求婚礼）情境条件未实现',
        '"三传递生传财"（取还魂债、利取财）增强结构未实现',
        '原文"合多吉多"中"合"的多种组合（六合叠加三合等）未单独区分',
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
        $tianpan = $facts->get('tianpan');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($tianpan)
            || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            return null;
        }

        $dayStemLodging = $facts->stemLodgingBranch($dayStem);
        if (! is_int($dayStemLodging) || ! isset($tianpan[$dayStemLodging]) || ! is_int($tianpan[$dayStemLodging])) {
            return null;
        }

        $dayUpper = $tianpan[$dayStemLodging];

        // 1) 天干作合：日干上神按本日所在旬寄宫天干与日干作天干五合。
        $dayUpperStem = $this->resolveBranchLodgingStem($facts, $dayUpper);
        if ($dayUpperStem === null) {
            return null;
        }

        $stemHexed = ($dayUpperStem + 5) % 10 === $dayStem;
        if (! $stemHexed) {
            return null;
        }

        // 2a) 初传与干上神作地支六合。
        $initialHexesUpper = BranchRelations::isLiuhe($initial, $dayUpper);
        if (! $initialHexesUpper) {
            return null;
        }

        // 2b) 初传必须参与，并与日支及中传或末传共同构成完整地支三合。
        $sanchuanSanhe = $this->isSanchuanSanheWithDayBranch(
            $dayBranch, $initial, $middle, $final
        );
        if (! $sanchuanSanhe) {
            return null;
        }

        // 3) 年命宫上神各自乘六吉将；缺数据时由 ContextAwareRule 路径在引擎层拦截。
        $yearMing = $this->resolveYearMing($facts, $tianpan);
        if (! $yearMing['present']) {
            return null;
        }

        $nmAuspicious = $yearMing['nianming']['auspicious'] === true;
        $xnAuspicious = $yearMing['xingnian']['auspicious'] === true;
        if (! ($nmAuspicious && $xnAuspicious)) {
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
                'day_stem' => $dayStem,
                'day_branch' => $dayBranch,
                'day_stem_lodging_branch' => $dayStemLodging,
                'day_upper' => $dayUpper,
                'day_upper_stem' => $dayUpperStem,
                'day_upper_stem_hexed' => true,
                'initial' => $initial,
                'middle' => $middle,
                'final' => $final,
                'initial_hexes_upper' => true,
                'sanchuan_sanhe' => $sanchuanSanhe,
                'sanchuan_sanhe_triple' => $this->findDayBranchSanheTriple(
                    $dayBranch, $initial, $middle, $final
                ),
                'nianming' => $yearMing['nianming'],
                'xingnian' => $yearMing['xingnian'],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /**
     * 根据日干所在旬与目标地支计算寄宫天干。
     *
     * 六甲旬的旬首地支序列：子、戌、申、午、辰、寅；每旬中旬首到
     * 旬尾（10 个位置）依次对应甲乙丙丁戊己庚辛壬癸。
     */
    private function resolveBranchLodgingStem(PanFacts $facts, int $branch): ?int
    {
        $dayIndex = $facts->sexagenaryDayIndex();
        if ($dayIndex === null) {
            return null;
        }
        $xunIndex = intdiv($dayIndex, 10);
        $xunHeads = [0, 10, 8, 6, 4, 2];
        if (! isset($xunHeads[$xunIndex])) {
            return null;
        }
        $xunHead = $xunHeads[$xunIndex];
        $offset = ($branch - $xunHead + 12) % 12;
        if ($offset > 9) {
            return null;
        }

        return $offset;
    }

    /**
     * 判断初传是否与日支及中传或末传共同构成完整地支三合。
     *
     * 严格按原文"支三合发用"：发用即初传，因此初传必须参与；原文没有限定
     * 第三支必须是中传或末传，二者任一与日支、初传组成完整三合即可。
     * 课例"戊申日子时申将"：日支申 + 初传子 + 末传辰 → 申子辰 ✓。
     *
     * @return bool 是否与日支构成完整三合
     */
    private function isSanchuanSanheWithDayBranch(
        int $dayBranch,
        int $initial,
        int $middle,
        int $final
    ): bool {
        foreach ([[$dayBranch, $initial, $middle], [$dayBranch, $initial, $final]] as $branches) {
            if (BranchRelations::isSanhe(...$branches)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 找出与日支构成三合的具体三合支序列（用于 evidence 输出）。
     *
     * @return list<int>|null
     */
    private function findDayBranchSanheTriple(
        int $dayBranch,
        int $initial,
        int $middle,
        int $final
    ): ?array {
        foreach ([[$dayBranch, $initial, $middle], [$dayBranch, $initial, $final]] as $branches) {
            $triple = BranchRelations::sanheTriple(...$branches);
            if ($triple !== null) {
                return $triple;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $tianpan
     * @return array{
     *     present: bool,
     *     nianming: array{ground: ?int, upper: ?int, general: ?int, auspicious: bool},
     *     xingnian: array{ground: ?int, upper: ?int, general: ?int, auspicious: bool}
     * }
     */
    private function resolveYearMing(PanFacts $facts, array $tianpan): array
    {
        $blank = [
            'present' => false,
            'nianming' => ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false],
            'xingnian' => ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false],
        ];

        $querent = $facts->personByRole('querent');
        if (! is_array($querent)) {
            return $blank;
        }

        $inspect = function (?int $ground) use ($facts, $tianpan): array {
            if (! is_int($ground) || ! isset($tianpan[$ground]) || ! is_int($tianpan[$ground])) {
                return ['ground' => null, 'upper' => null, 'general' => null, 'auspicious' => false];
            }
            $upper = $tianpan[$ground];
            $general = $facts->generalRidingBranch($upper);

            return [
                'ground' => $ground,
                'upper' => $upper,
                'general' => $general,
                'auspicious' => is_int($general) && in_array($general, self::AUSPICIOUS_GENERALS, true),
            ];
        };

        $nianming = $inspect($querent['nianming'] ?? null);
        $xingnian = $inspect($querent['xingnian'] ?? null);

        $present = $nianming['upper'] !== null || $xingnian['upper'] !== null;

        return [
            'present' => $present,
            'nianming' => $nianming,
            'xingnian' => $xingnian,
        ];
    }
}
