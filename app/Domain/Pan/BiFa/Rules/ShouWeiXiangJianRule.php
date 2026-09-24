<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 文件作用：按《毕法赋》第二法"首尾相见始终宜"逐分格判断当前盘面。
 *
 * 第二法正文含三组分格，共四条程序判断 route：
 *
 *  - A. 周而复始·旬尾临干、旬首临支
 *      → route xun_tail_on_stem_xun_head_on_branch
 *  - B. 周而复始·旬首临干、旬尾临支
 *      → route xun_head_on_stem_xun_tail_on_branch
 *  - C. 天心格（仅采用《毕法赋》正文口径：四建尽入四课整体地支集合）
 *      → route tianxin_four_establishments_in_lessons
 *  - D. 回还格（三传尽入四课整体地支集合）
 *      → route huihuan_transmissions_in_lessons
 *
 * 古籍"十日白名单"（乙未、辛丑、丙申、壬寅、戊申 / 乙丑、辛未、丙寅、戊寅、壬申）
 * 是周而复始格结构自然命中的枚举示例，不作为 matcher 白名单；判断完全由日干寄宫、
 * 日支、旬首、旬尾及天盘自然得出。
 *
 * 《订讹》"四建俱在三传"扩展不入第二法 matcher；本法仅采用《毕法赋》正文口径，
 * 已在研究文档中独立记录旁证。
 *
 * 四课整体地支集合：lessonBranches = unique(sike[1..7])。sike[0] 为日干，
 * 不得误作地支纳入。
 *
 * 吉凶断义全部进入 definition()['judgments']，不参与 matcher 判定。
 */
final class ShouWeiXiangJianRule implements BiFaRule
{
    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    public function code(): string
    {
        return 'bifa.02';
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}
     */
    public function law(): array
    {
        $law = BiFaCatalog::findByCode($this->code());
        if ($law === null) {
            throw new LogicException('BiFaCatalog 找不到 '.$this->code().'；注册表与目录脱节。');
        }

        return $law;
    }

    /**
     * @return array{
     *     description: string,
     *     foundations: list<array{code: string, title: string, description: string}>,
     *     judgments: list<array<string, mixed>>,
     *     sections: list<array{title: string, content: string}>
     * }
     */
    public function definition(): array
    {
        return [
            'description' => '旬首、旬尾分别临日干与日支，或四建尽入四课，或三传尽入四课，皆属“首尾相见始终宜”，主事情前后相续、吉凶易成。',
            'foundations' => [
                ['code' => 'xun_tail_on_stem_xun_head_on_branch', 'title' => '周而复始·旬尾临干、旬首临支', 'description' => '本旬旬尾加临日干寄宫，且本旬旬首加临日支；干支前后分别得旬尾、旬首，前后相续如环。'],
                ['code' => 'xun_head_on_stem_xun_tail_on_branch', 'title' => '周而复始·旬首临干、旬尾临支', 'description' => '本旬旬首加临日干寄宫，且本旬旬尾加临日支；与上一路方向相反，仍属周而复始格的镜像结构。'],
                ['code' => 'tianxin_four_establishments_in_lessons', 'title' => '天心格', 'description' => '太岁、月建、日支、占时全部出现在四课整体地支集合之中；此为《毕法赋》正文口径，四建俱在三传不入本法。'],
                ['code' => 'huihuan_transmissions_in_lessons', 'title' => '回还格', 'description' => '初传、中传、末传全部出现在四课整体地支集合之中，三传尽在四课之内。'],
            ],
            'judgments' => [
                ['label' => '占事不脱、所谋皆成', 'effect' => 'increase', 'description' => '周而复始格主事情前后相续，所谋之事容易继续推进或重新回转而成。'],
                ['label' => '不宜释散', 'effect' => 'reduce', 'description' => '不利于求解除、消散、脱离之事；忧疑之事亦不易立即决断。'],
                ['label' => '非常大事、朝廷之事易成', 'effect' => 'increase', 'description' => '天心格正文称非常之事可即日而成，涉及朝廷、天庭等大事尤有成就之象。'],
                ['label' => '阴私鄙常之事反咎', 'effect' => 'reduce', 'description' => '天心格对阴私、鄙陋、日常之求不吉，反成其咎。'],
                ['label' => '吉事吉就，凶事凶成', 'effect' => 'neutral', 'description' => '回还格加强事情本身的发展结果，并非无条件吉利。'],
                ['label' => '宜守旧，不宜动作', 'effect' => 'neutral', 'description' => '回还格所主宜守旧、不宜更张；人事、动作不主动则吉，主动则咎。'],
                ['label' => '病难退、讼难解', 'effect' => 'reduce', 'description' => '回还格主病难退、讼难解，求消除、解除、脱离之事反受阻滞。'],
            ],
            'sections' => [[
                'title' => '成立条件说明',
                'content' => '本法四条程序 route 均为独立判定条件，任一成立即第二法整体成立。A、B 两路为周而复始格的两个镜像结构，方向相反、不可混为同一路；天心格仅采用《毕法赋》正文口径“四建尽入四课”，《订讹》“四建俱在三传”仅作研究旁证，不进入本法 matcher；回还格仅要求三传尽入四课整体地支集合，不附加四课不备、三合、干支自作三合等额外条件。',
            ]],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $tianpan = $facts->get('tianpan');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $nianzhi = $facts->get('nianzhi');
        $yuezhi = $facts->get('yuezhi');
        $shizhi = $facts->get('shizhi');
        $sanchuan0 = $facts->get('sanchuan0');
        $sanchuan1 = $facts->get('sanchuan1');
        $sanchuan2 = $facts->get('sanchuan2');
        $sike = $facts->get('sike');

        if (! is_array($tianpan) || ! is_int($rigan) || ! is_int($rizhi)
            || ! is_int($nianzhi) || ! is_int($yuezhi) || ! is_int($shizhi)
            || ! is_int($sanchuan0) || ! is_int($sanchuan1) || ! is_int($sanchuan2)
            || ! is_array($sike) || count($sike) < 8) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($rigan);
        $xunHead = $facts->dayXunHeadBranch();
        if ($lodging === null || $xunHead === null) {
            return null;
        }

        $xunTail = ($xunHead + 9) % 12;

        // 干上神 = 天盘[日干寄宫]；支上神 = 天盘[日支]。
        $ganShang = $tianpan[$lodging] ?? null;
        $zhiShang = $tianpan[$rizhi] ?? null;
        if (! is_int($ganShang) || ! is_int($zhiShang)) {
            return null;
        }

        // A 路：旬尾临干 + 旬首临支。
        $xunTailOnStemXunHeadOnBranch = $ganShang === $xunTail && $zhiShang === $xunHead;

        // B 路：旬首临干 + 旬尾临支。
        $xunHeadOnStemXunTailOnBranch = $ganShang === $xunHead && $zhiShang === $xunTail;

        // 四课整体地支集合：sike[1..7]（sike[0] 为日干，绝对不作为同索引地支加入）。
        $lessonBranches = self::extractLessonBranches($sike);

        // C 路：四建（太岁、月建、日支、占时）均在四课整体地支集合中。
        $tianxinFourEstablishments = isset($lessonBranches[$nianzhi])
            && isset($lessonBranches[$yuezhi])
            && isset($lessonBranches[$rizhi])
            && isset($lessonBranches[$shizhi]);

        // D 路：三传均在四课整体地支集合中。
        $huihuanTransmissions = isset($lessonBranches[$sanchuan0])
            && isset($lessonBranches[$sanchuan1])
            && isset($lessonBranches[$sanchuan2]);

        $subMatches = [
            self::subMatch(
                'xun_tail_on_stem_xun_head_on_branch',
                '周而复始·旬尾临干、旬首临支',
                $xunTailOnStemXunHeadOnBranch,
                '本旬旬尾加临日干寄宫，且本旬旬首加临日支。',
                $xunTailOnStemXunHeadOnBranch
                    ? self::xunTailStemXunHeadBranchEvidence($xunHead, $xunTail, $lodging, $rizhi, $ganShang, $zhiShang)
                    : null,
            ),
            self::subMatch(
                'xun_head_on_stem_xun_tail_on_branch',
                '周而复始·旬首临干、旬尾临支',
                $xunHeadOnStemXunTailOnBranch,
                '本旬旬首加临日干寄宫，且本旬旬尾加临日支。',
                $xunHeadOnStemXunTailOnBranch
                    ? self::xunHeadStemXunTailBranchEvidence($xunHead, $xunTail, $lodging, $rizhi, $ganShang, $zhiShang)
                    : null,
            ),
            self::subMatch(
                'tianxin_four_establishments_in_lessons',
                '天心格',
                $tianxinFourEstablishments,
                '太岁、月建、日支、占时全部出现在四课整体地支集合中。',
                $tianxinFourEstablishments
                    ? self::tianxinEvidence($nianzhi, $yuezhi, $rizhi, $shizhi)
                    : null,
            ),
            self::subMatch(
                'huihuan_transmissions_in_lessons',
                '回还格',
                $huihuanTransmissions,
                '初传、中传、末传全部出现在四课整体地支集合中。',
                $huihuanTransmissions
                    ? self::huihuanEvidence($sanchuan0, $sanchuan1, $sanchuan2)
                    : null,
            ),
        ];

        $matchedRoutes = [];
        foreach ($subMatches as $sub) {
            if ($sub['matched']) {
                $matchedRoutes[] = $sub['code'];
            }
        }

        if ($matchedRoutes === []) {
            return null;
        }

        return new BiFaRuleMatch(
            code: $this->code(),
            number: $this->law()['number'],
            name: $this->law()['name'],
            summary: $this->law()['summary'],
            subMatches: $subMatches,
            matchedRoutes: $matchedRoutes,
            pendingRoutes: [],
            evidence: [
                'rigan' => $rigan,
                'rizhi' => $rizhi,
                'lodging' => $lodging,
                'xun_head' => $xunHead,
                'xun_tail' => $xunTail,
                'gan_shang' => $ganShang,
                'zhi_shang' => $zhiShang,
                'nianzhi' => $nianzhi,
                'yuezhi' => $yuezhi,
                'shizhi' => $shizhi,
                'lesson_branches' => array_keys($lessonBranches),
                'sanchuan' => [$sanchuan0, $sanchuan1, $sanchuan2],
            ],
        );
    }

    /**
     * 从四课结构提取“除日干外”的整体地支集合。
     *
     *  - sike[0] 是日干，绝不能作为同索引地支加入；
     *  - sike[1..7] 视为可参与四课整体地支集合的下位/上位地支；
     *  - 由于四课下位（sike[2]、sike[4]、sike[6]）可能就是日干寄宫、日支等同位地支，
     *    整体集合允许出现日支、寄宫。
     *
     * @param  array<int, mixed>  $sike
     * @return array<int, true>
     */
    private static function extractLessonBranches(array $sike): array
    {
        $set = [];
        // sike[0] 是日干，绝对不加入。
        for ($i = 1; $i <= 7; $i++) {
            $value = $sike[$i] ?? null;
            if (is_int($value)) {
                $set[$value] = true;
            }
        }

        return $set;
    }

    /**
     * @return array{
     *     code: string,
     *     title: string,
     *     description: string,
     *     matched: bool,
     *     detail: ?string,
     *     requires_people: bool,
     *     people_missing: bool
     * }
     */
    private static function subMatch(
        string $code,
        string $title,
        bool $matched,
        string $description,
        ?string $detail,
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'matched' => $matched,
            'detail' => $detail,
            'requires_people' => false,
            'people_missing' => false,
        ];
    }

    private static function xunTailStemXunHeadBranchEvidence(
        int $xunHead,
        int $xunTail,
        int $lodging,
        int $rizhi,
        int $ganShang,
        int $zhiShang,
    ): string {
        return sprintf(
            '干上神为旬尾%s，加临日干寄宫%s；支上神为旬首%s，加临日支%s。',
            self::BRANCH_NAMES[$xunTail],
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$xunHead],
            self::BRANCH_NAMES[$rizhi],
        ).sprintf(' 干上神=%s、支上神=%s。', self::BRANCH_NAMES[$ganShang], self::BRANCH_NAMES[$zhiShang]);
    }

    private static function xunHeadStemXunTailBranchEvidence(
        int $xunHead,
        int $xunTail,
        int $lodging,
        int $rizhi,
        int $ganShang,
        int $zhiShang,
    ): string {
        return sprintf(
            '干上神为旬首%s，加临日干寄宫%s；支上神为旬尾%s，加临日支%s。',
            self::BRANCH_NAMES[$xunHead],
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$xunTail],
            self::BRANCH_NAMES[$rizhi],
        ).sprintf(' 干上神=%s、支上神=%s。', self::BRANCH_NAMES[$ganShang], self::BRANCH_NAMES[$zhiShang]);
    }

    private static function tianxinEvidence(int $nianzhi, int $yuezhi, int $rizhi, int $shizhi): string
    {
        return sprintf(
            '太岁%s、月建%s、日支%s、占时%s尽入四课整体地支集合。',
            self::BRANCH_NAMES[$nianzhi],
            self::BRANCH_NAMES[$yuezhi],
            self::BRANCH_NAMES[$rizhi],
            self::BRANCH_NAMES[$shizhi],
        );
    }

    private static function huihuanEvidence(int $sanchuan0, int $sanchuan1, int $sanchuan2): string
    {
        return sprintf(
            '初传%s、中传%s、末传%s尽入四课整体地支集合。',
            self::BRANCH_NAMES[$sanchuan0],
            self::BRANCH_NAMES[$sanchuan1],
            self::BRANCH_NAMES[$sanchuan2],
        );
    }
}
