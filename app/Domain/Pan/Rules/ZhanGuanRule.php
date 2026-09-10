<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》卷八《课经集（二）·斩关课》正文最小严格口径判断斩关课。
 *
 * 正文最小严格口径：魁（戌，10）或罡（辰，4）发用（为初传 sanchuan0），
 * 且该发用之神加临日干寄宫（天盘上神 = 发用）或者加临日支（天盘支上 = 发用）。
 *
 * 关于"加日辰"取 OR 的依据：
 *   - 正文"魁罡加日辰发用"中"日辰"是日干与日支的常见并列结构（"日"指日干，
 *     "辰"即日支，六壬术语中"日辰"=日干+日支）；
 *   - 正文唯一甲寅日课例因甲寄寅与日支寅同位，同时兼容 OR 与 AND，
 *     不能单独裁决两种解读；
 *   - 本实现按"日辰"的通常并列语义采用 OR，AND 异读及《观月经》更宽口径
 *     均在笔记中保留；命中统计只用于影响范围评估，不作为文献证据。
 *
 * 无关人物上下文：斩关课主体只涉及初传与日干寄宫/日支上下神，与本命、行年无关，
 * 因此不实现 ContextAwareRule，缺人物时仍按盘面正常判断。
 *
 * 衍生结构与候选规则（不进入主体判断，保留在笔记）：
 *   - "神藏煞没"（六凶神临克方 + 四煞陷四维）；
 *   - "魁渡天门"（戌加亥宫）；
 *   - "罡塞鬼户"（辰加寅宫）；
 *   - "传有虎阴申酉"（白虎/太阴 临申/酉 的得逃增强）；
 *   - 血支、血忌、呻吟、羊刃、三杀等煞神条件；
 *   - "加日干 AND 加日支" 的额外收紧候选。
 *
 * 详见 docs/课经/29-斩关课.md。
 */
final class ZhanGuanRule implements PanRule
{
    /** 辰 = 天罡。 */
    private const TIAN_GANG = 4;

    /** 戌 = 天魁（河魁）。 */
    private const TIAN_KUI = 10;

    protected const RULE_CODE = 'lesson.zhan_guan';

    protected const NAME = '斩关课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '天魁（戌）或天罡（辰）发用为初传，且该神加临日干寄宫（天盘日干位上神为该神）或加临日支（天盘日支位上神为该神）。';

    protected const GUA = '遁';

    protected const GUA_SYMBOL = '䷠';

    protected const XIANG = '关梁逾越，最利逃亡。捉贼难获，出行自强。病讼凶祸，厌祷吉详。书符合药，方法最良。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《大全》"神藏煞没"（六凶神临克方 + 四煞陷四维）作为吉化增强结构尚未实现',
        '《大全》"魁渡天门"（戌加亥宫，抑塞难通）作为格未实现',
        '《大全》"罡塞鬼户"（辰加寅宫，谋为顺利）作为格未实现',
        '《大全》"传有虎阴申酉"（白虎/太阴 临申/酉 得逃增强）尚未实现',
        '血支、血忌、呻吟、羊刃、三杀等煞神条件尚未实现',
        '"加日干 AND 加日支" 的额外收紧候选未作为主体',
        '甲戊庚日 "神藏煞没" 日干限定未与主体联动',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $tianpan = $facts->get('tianpan');
        $initial = $facts->get('sanchuan0');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($tianpan) || ! is_int($initial)) {
            return null;
        }

        if (! in_array($initial, [self::TIAN_GANG, self::TIAN_KUI], true)) {
            return null;
        }

        $stemLodging = $facts->stemLodgingBranch($dayStem);
        if (! is_int($stemLodging)
            || ! isset($tianpan[$stemLodging]) || ! is_int($tianpan[$stemLodging])
            || ! isset($tianpan[$dayBranch]) || ! is_int($tianpan[$dayBranch])) {
            return null;
        }

        $dayUpper = $tianpan[$stemLodging];
        $branchUpper = $tianpan[$dayBranch];

        $isTianGang = $initial === self::TIAN_GANG;
        $isTianKui = $initial === self::TIAN_KUI;
        $onDayStem = $initial === $dayUpper;
        $onDayBranch = $initial === $branchUpper;

        if (! ($onDayStem || $onDayBranch)) {
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
                'day_stem_lodging_branch' => $stemLodging,
                'day_upper' => $dayUpper,
                'branch_upper' => $branchUpper,
                'initial' => $initial,
                'is_tian_gang' => $isTianGang,
                'is_tian_kui' => $isTianKui,
                'on_day_stem' => $onDayStem,
                'on_day_branch' => $onDayBranch,
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
