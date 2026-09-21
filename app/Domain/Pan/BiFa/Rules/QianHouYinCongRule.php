<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;

/**
 * 文件作用：按《毕法赋》第一法"前后引从升迁吉"逐分格判断当前盘面是否成立。
 *
 * 该法对应《六壬大全·毕法赋》第一法，与课经第 22 课"引从课"在结构上大量重合，
 * 但属于不同知识体系：
 *
 *  - 课经第 22 课以"成课"为单位判定，命中即整课出现，并承担组课排盘逻辑；
 *  - 毕法第一法以"分格"为单位判定，一张盘可能同时命中若干分格，
 *    命中其一即第一法整体成立，断义随命中的分格变化。
 *
 * 因此本类不复用 App\Domain\Pan\Rules\YinCongRule 的匹配结果，也不与
 * 任何 PanRule 共享注册路径。两种体系的 RuleMatch 互相不交叉。
 *
 * 实现 9 个分格：
 *
 *  - A. 引从天干（基础格，初末夹拱日干寄宫）
 *  - B. 拱贵格（A + 干上神 ∈ {昼贵, 夜贵}）
 *  - C. 两贵引从天干格（A + {初传, 末传} == {昼贵, 夜贵}）
 *  - D. 初末引从地支（初末夹拱日支）
 *  - E. 贵临干支拱年命（{干上神, 支上神} == {昼贵, 夜贵} + 干支夹拱本命/行年）
 *  - F. 二贵拱年命（{初传, 末传} == {昼贵, 夜贵} + 初末夹拱本命/行年）
 *  - G. 干支拱日禄（伏吟 + 干支夹拱日禄）
 *  - H. 干支拱昼贵（伏吟 + 干支夹拱昼贵）
 *  - H. 干支拱夜贵（伏吟 + 干支夹拱夜贵）
 *  - I. 干支并初中拱地盘贵人（干支夹拱 target + 初中夹拱同一 target，target ∈ {昼贵, 夜贵}）
 *
 * 仅 E、F 需占测者人物资料；其余 7 个分格完全可独立判断，
 * 因此缺人资料不会让整条第一法变成 not_evaluated，只标记 E/F 为未评估。
 */
final class QianHouYinCongRule implements BiFaRule
{
    private const LAW_NUMBER = 1;

    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var array<int, int> 十干昼贵：甲戊庚丑、乙己子、丙丁亥、六辛午、壬癸巳 */
    private const DAY_NOBLE = [1, 0, 11, 11, 1, 0, 1, 6, 5, 5];

    /** @var array<int, int> 十干夜贵：甲戊庚未、乙己申、丙丁酉、六辛寅、壬癸卯 */
    private const NIGHT_NOBLE = [7, 8, 9, 9, 7, 8, 7, 2, 3, 3];

    /** @var array<int, int> 十干禄（临官）：甲寅、乙卯、丙巳、丁午、戊巳、己午、庚申、辛酉、壬亥、癸子 */
    private const DAY_PROSPERITY = [2, 3, 5, 6, 5, 6, 8, 9, 11, 0];

    public function code(): string
    {
        return 'bifa.qian_hou_yin_cong';
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}
     */
    public function law(): array
    {
        $law = BiFaCatalog::findByCode($this->code());

        // 该法必定存在；目录缺失是编程错误。
        return $law ?? [
            'number' => self::LAW_NUMBER,
            'name' => '前后引从升迁吉',
            'code' => $this->code(),
            'slug' => 'qian-hou-yin-cong',
            'summary' => '',
        ];
    }

    /**
     * @return array{
     *     description: string,
     *     foundations: list<array{code: string, title: string, description: string}>,
     *     judgments: list<array{code: string, effect: string, label: string, description: string}>
     * }
     */
    public function definition(): array
    {
        return [
            'description' => '初末传分临日干（或日支）前后宫，前引后从，主迁官进职、修宅迁居。',
            'foundations' => [
                [
                    'code' => 'yin_gan',
                    'title' => '引从天干',
                    'description' => '日干寄宫前一宫位之上神发用作初传，后一宫位之上神作末传，前后夹拱天干。',
                ],
                [
                    'code' => 'yin_zhi',
                    'title' => '初末引从地支',
                    'description' => '日支前一宫位之上神发用作初传，后一宫位之上神作末传，前后夹拱地支。',
                ],
                [
                    'code' => 'gong_gui',
                    'title' => '拱贵格',
                    'description' => '引从天干成立，且日干寄宫之上神恰乘昼夜贵人之一。',
                ],
                [
                    'code' => 'liang_gui_yin_gan',
                    'title' => '两贵引从天干格',
                    'description' => '引从天干成立，且初传、末传恰分别为昼夜二贵（昼贵 / 夜贵允许互换方向）。',
                ],
                [
                    'code' => 'gui_lin_gan_zhi_gang_nianming',
                    'title' => '贵临干支拱年命',
                    'description' => '昼夜二贵分别加临日干寄宫与日支（方向不限），且干支前后夹拱占人本命或行年。',
                ],
                [
                    'code' => 'er_gui_gang_nianming',
                    'title' => '二贵拱年命',
                    'description' => '初传、末传恰分别为昼夜二贵（允许互换方向），且初末夹拱本命或行年。',
                ],
                [
                    'code' => 'gan_zhi_gang_ri_lu',
                    'title' => '干支拱日禄',
                    'description' => '伏吟盘，日干寄宫与日支前后夹拱日禄。',
                ],
                [
                    'code' => 'gan_zhi_gang_zhou_gui',
                    'title' => '干支拱昼贵',
                    'description' => '伏吟盘，日干寄宫与日支前后夹拱昼贵。',
                ],
                [
                    'code' => 'gan_zhi_gang_ye_gui',
                    'title' => '干支拱夜贵',
                    'description' => '伏吟盘，日干寄宫与日支前后夹拱夜贵。',
                ],
                [
                    'code' => 'gan_zhi_bing_chu_zhong_gui',
                    'title' => '干支并初中拱地盘贵人',
                    'description' => '干支夹拱昼夜贵人之一，且初传、中传也夹拱同一个昼夜贵人（不许昼贵 / 夜贵互换后拼凑成立）。',
                ],
            ],
            'judgments' => [],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');
        $tianpan = $facts->get('tianpan');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');

        if (! is_int($initial) || ! is_int($middle) || ! is_int($final)
            || ! is_array($tianpan) || ! is_int($rigan) || ! is_int($rizhi)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($rigan);
        if ($lodging === null) {
            return null;
        }

        $dayNoble = self::DAY_NOBLE[$rigan] ?? null;
        $nightNoble = self::NIGHT_NOBLE[$rigan] ?? null;
        $lu = self::DAY_PROSPERITY[$rigan] ?? null;

        // 关键事实——初、末、中传所临地盘（=天盘支→地盘宫位）。
        $initialGround = self::groundPositionOf($tianpan, $initial);
        $middleGround = self::groundPositionOf($tianpan, $middle);
        $finalGround = self::groundPositionOf($tianpan, $final);
        if ($initialGround === null || $middleGround === null || $finalGround === null) {
            return null;
        }

        $fuyin = $facts->hasPlatePattern('fuyin');

        // 干上神 / 支上神：tianpan[lodging] / tianpan[rizhi]。
        $ganShangShen = $tianpan[$lodging] ?? null;
        $zhiShangShen = $tianpan[$rizhi] ?? null;

        // 基础分格 A / D。
        $yinGan = self::flanks($initialGround, $finalGround, $lodging);
        $yinZhi = self::flanks($initialGround, $finalGround, $rizhi);

        // 分格 B：引从天干 + 干上神是昼夜贵人之一。
        $gongGui = $yinGan && self::isNoble($ganShangShen, $dayNoble, $nightNoble);

        // 分格 C：引从天干 + {初传, 末传} == {昼贵, 夜贵}（允许互换方向）。
        $liangGuiYinGan = $yinGan && self::areTransmissionsTwoNobles(
            $initial,
            $final,
            $dayNoble,
            $nightNoble,
        );

        // 占测者本命 / 行年（缺人资料时 E / F 标记 people_missing）。
        $person = self::resolvePerson($facts);

        // 分格 E：贵临干支 + 干支夹拱本命或行年。
        $guiLinGanZhiMatched = self::isTwoNoblesOnGanZhi(
            $ganShangShen,
            $zhiShangShen,
            $dayNoble,
            $nightNoble,
        );
        $guiLinGanZhiGangNianming = false;
        $guiLinGanZhiPeopleMissing = $person === null;
        if ($guiLinGanZhiMatched && $person !== null) {
            $guiLinGanZhiGangNianming = self::flanks($lodging, $rizhi, $person['nianming'])
                || ($person['xingnian'] !== null && self::flanks($lodging, $rizhi, $person['xingnian']));
        }

        // 分格 F：{初传, 末传} == {昼贵, 夜贵} + 初末夹拱本命或行年。
        $erGuiTransmissionsTwoNobles = self::areTransmissionsTwoNobles(
            $initial,
            $final,
            $dayNoble,
            $nightNoble,
        );
        $erGuiGangNianming = false;
        $erGuiPeopleMissing = $person === null;
        if ($erGuiTransmissionsTwoNobles && $person !== null) {
            $erGuiGangNianming = self::flanks($initialGround, $finalGround, $person['nianming'])
                || ($person['xingnian'] !== null && self::flanks($initialGround, $finalGround, $person['xingnian']));
        }

        // 分格 G：伏吟 + 干支夹拱日禄。
        $ganZhiGangRiLu = $fuyin && is_int($lu) && self::flanks($lodging, $rizhi, $lu);

        // 分格 H（昼贵 / 夜贵 分别记录）。
        $ganZhiGangZhouGui = $fuyin && is_int($dayNoble) && self::flanks($lodging, $rizhi, $dayNoble);
        $ganZhiGangYeGui = $fuyin && is_int($nightNoble) && self::flanks($lodging, $rizhi, $nightNoble);

        // 分格 I：干支并初中拱同一昼夜贵人。
        $ganZhiBingChuZhongGuiMatched = false;
        $ganZhiBingChuZhongGuiTarget = null;
        if (is_int($dayNoble) && self::flanks($lodging, $rizhi, $dayNoble) && self::flanks($initialGround, $middleGround, $dayNoble)) {
            $ganZhiBingChuZhongGuiMatched = true;
            $ganZhiBingChuZhongGuiTarget = 'day_noble';
        } elseif (is_int($nightNoble) && self::flanks($lodging, $rizhi, $nightNoble) && self::flanks($initialGround, $middleGround, $nightNoble)) {
            $ganZhiBingChuZhongGuiMatched = true;
            $ganZhiBingChuZhongGuiTarget = 'night_noble';
        }

        $subMatches = [
            self::subMatch('yin_gan', '引从天干', $yinGan,
                '日干寄宫'.self::BRANCH_NAMES[$lodging].'前一宫位上神'.self::BRANCH_NAMES[$initial].'发用作初传，后一宫位上神'.self::BRANCH_NAMES[$final].'作末传，前后夹拱天干。',
                self::yinGanEvidence($lodging, $initial, $final, $tianpan),
                false, false),

            self::subMatch('yin_zhi', '初末引从地支', $yinZhi,
                '日支'.self::BRANCH_NAMES[$rizhi].'前一宫位上神'.self::BRANCH_NAMES[$initial].'发用作初传，后一宫位上神'.self::BRANCH_NAMES[$final].'作末传，前后夹拱地支。',
                self::yinZhiEvidence($rizhi, $initial, $final, $tianpan),
                false, false),

            self::subMatch('gong_gui', '拱贵格', $gongGui,
                '引从天干的同时，日干寄宫之上神恰乘昼夜贵人之一。',
                self::gongGuiEvidence($lodging, $yinGan, $ganShangShen, $dayNoble, $nightNoble),
                false, false),

            self::subMatch('liang_gui_yin_gan', '两贵引从天干格', $liangGuiYinGan,
                '引从天干的同时，初传、末传恰分别为昼夜二贵（昼贵 / 夜贵允许互换方向）。',
                self::liangGuiYinGanEvidence($initial, $final, $dayNoble, $nightNoble),
                false, false),

            self::subMatch('gui_lin_gan_zhi_gang_nianming', '贵临干支拱年命', $guiLinGanZhiGangNianming,
                '昼夜二贵分别加临日干寄宫与日支（方向不限），且干支前后夹拱占人本命或行年。',
                self::guiLinGanZhiEvidence(
                    $guiLinGanZhiMatched,
                    $guiLinGanZhiGangNianming,
                    $guiLinGanZhiPeopleMissing,
                    $lodging,
                    $rizhi,
                    $ganShangShen,
                    $zhiShangShen,
                    $person,
                    $dayNoble,
                    $nightNoble,
                ),
                true, $guiLinGanZhiPeopleMissing && $guiLinGanZhiMatched),

            self::subMatch('er_gui_gang_nianming', '二贵拱年命', $erGuiGangNianming,
                '初传、末传恰分别为昼夜二贵（允许互换方向），且初末夹拱占人本命或行年。',
                self::erGuiGangNianmingEvidence(
                    $erGuiTransmissionsTwoNobles,
                    $erGuiGangNianming,
                    $erGuiPeopleMissing,
                    $initial,
                    $final,
                    $initialGround,
                    $finalGround,
                    $person,
                    $dayNoble,
                    $nightNoble,
                ),
                true, $erGuiPeopleMissing && $erGuiTransmissionsTwoNobles),

            self::subMatch('gan_zhi_gang_ri_lu', '干支拱日禄', $ganZhiGangRiLu,
                '伏吟盘，日干寄宫与日支前后夹拱日禄'.self::BRANCH_NAMES[$lu ?? 0].'。',
                self::ganZhiGangRiLuEvidence($fuyin, $lodging, $rizhi, $lu),
                false, false),

            self::subMatch('gan_zhi_gang_zhou_gui', '干支拱昼贵', $ganZhiGangZhouGui,
                '伏吟盘，日干寄宫与日支前后夹拱昼贵'.self::BRANCH_NAMES[$dayNoble ?? 0].'。',
                self::ganZhiGangGuiEvidence($fuyin, $lodging, $rizhi, $dayNoble, '昼贵'),
                false, false),

            self::subMatch('gan_zhi_gang_ye_gui', '干支拱夜贵', $ganZhiGangYeGui,
                '伏吟盘，日干寄宫与日支前后夹拱夜贵'.self::BRANCH_NAMES[$nightNoble ?? 0].'。',
                self::ganZhiGangGuiEvidence($fuyin, $lodging, $rizhi, $nightNoble, '夜贵'),
                false, false),

            self::subMatch('gan_zhi_bing_chu_zhong_gui', '干支并初中拱地盘贵人',
                $ganZhiBingChuZhongGuiMatched,
                '干支夹拱昼夜贵人之一，同时初传、中传也夹拱同一昼夜贵人；不许昼贵 / 夜贵互换后拼凑成立。',
                self::ganZhiBingChuZhongEvidence(
                    $ganZhiBingChuZhongGuiMatched,
                    $ganZhiBingChuZhongGuiTarget,
                    $lodging,
                    $rizhi,
                    $initialGround,
                    $middleGround,
                    $dayNoble,
                    $nightNoble,
                ),
                false, false),
        ];

        $matchedRoutes = [];
        foreach ($subMatches as $sub) {
            if ($sub['matched']) {
                $matchedRoutes[] = $sub['code'];
            }
        }

        return new BiFaRuleMatch(
            code: $this->code(),
            name: $this->law()['name'],
            summary: $this->law()['summary'],
            subMatches: $subMatches,
            matchedRoutes: $matchedRoutes,
            evidence: [
                'rigan' => $rigan,
                'rizhi' => $rizhi,
                'lodging' => $lodging,
                'day_noble' => $dayNoble,
                'night_noble' => $nightNoble,
                'ri_lu' => $lu,
                'initial_ground' => $initialGround,
                'middle_ground' => $middleGround,
                'final_ground' => $finalGround,
                'fuyin' => $fuyin,
                'nianming' => $person['nianming'] ?? null,
                'xingnian' => $person['xingnian'] ?? null,
            ],
        );
    }

    /**
     * @param  array<int, int>  $tianpan
     */
    private static function groundPositionOf(array $tianpan, int $heavenBranch): ?int
    {
        $position = array_search($heavenBranch, $tianpan, true);

        return is_int($position) ? $position : null;
    }

    private static function flanks(int $a, int $b, int $target): bool
    {
        $front = ($target + 1) % 12;
        $back = ($target + 11) % 12;

        return ($a === $front && $b === $back) || ($a === $back && $b === $front);
    }

    private static function isNoble(?int $branch, ?int $dayNoble, ?int $nightNoble): bool
    {
        return $branch !== null
            && (($dayNoble !== null && $branch === $dayNoble)
                || ($nightNoble !== null && $branch === $nightNoble));
    }

    private static function areTransmissionsTwoNobles(int $initial, int $final, ?int $dayNoble, ?int $nightNoble): bool
    {
        if ($dayNoble === null || $nightNoble === null) {
            return false;
        }

        $set = [$initial, $final];
        sort($set);
        $targets = [$dayNoble, $nightNoble];
        sort($targets);

        return $set === $targets;
    }

    private static function isTwoNoblesOnGanZhi(?int $ganShang, ?int $zhiShang, ?int $dayNoble, ?int $nightNoble): bool
    {
        if ($ganShang === null || $zhiShang === null || $dayNoble === null || $nightNoble === null) {
            return false;
        }

        $set = [$ganShang, $zhiShang];
        sort($set);
        $targets = [$dayNoble, $nightNoble];
        sort($targets);

        return $set === $targets;
    }

    /**
     * @return array{role: string, nianming: int, xingnian: ?int, xingnian_gan: ?int}|null
     */
    private static function resolvePerson(PanFacts $facts): ?array
    {
        $person = $facts->personByRole('querent');
        if ($person === null) {
            return null;
        }

        $nianming = $person['nianming'] ?? null;
        if (! is_int($nianming)) {
            return null;
        }

        $xingnian = $person['xingnian'] ?? null;
        $xingnian_gan = $person['xingnian_gan'] ?? null;

        return [
            'role' => 'querent',
            'nianming' => $nianming,
            'xingnian' => is_int($xingnian) ? $xingnian : null,
            'xingnian_gan' => is_int($xingnian_gan) ? $xingnian_gan : null,
        ];
    }

    /**
     * @param  array<int, int>  $tianpan
     */
    private static function yinGanEvidence(int $lodging, int $initial, int $final, array $tianpan): string
    {
        return sprintf(
            '初传临地盘%s（天盘%s），末传临地盘%s（天盘%s），前后夹拱日干寄宫%s。',
            self::BRANCH_NAMES[($lodging + 1) % 12],
            self::BRANCH_NAMES[$initial],
            self::BRANCH_NAMES[($lodging + 11) % 12],
            self::BRANCH_NAMES[$final],
            self::BRANCH_NAMES[$lodging],
        );
    }

    /**
     * @param  array<int, int>  $tianpan
     */
    private static function yinZhiEvidence(int $rizhi, int $initial, int $final, array $tianpan): string
    {
        return sprintf(
            '初传临地盘%s（天盘%s），末传临地盘%s（天盘%s），前后夹拱日支%s。',
            self::BRANCH_NAMES[($rizhi + 1) % 12],
            self::BRANCH_NAMES[$initial],
            self::BRANCH_NAMES[($rizhi + 11) % 12],
            self::BRANCH_NAMES[$final],
            self::BRANCH_NAMES[$rizhi],
        );
    }

    /**
     * @return string|null
     */
    private static function gongGuiEvidence(int $lodging, bool $yinGan, ?int $ganShang, ?int $dayNoble, ?int $nightNoble)
    {
        if (! $yinGan || $ganShang === null) {
            return null;
        }

        if ($dayNoble !== null && $ganShang === $dayNoble) {
            return sprintf('日干寄宫%s上乘昼贵%s。', self::BRANCH_NAMES[$lodging], self::BRANCH_NAMES[$dayNoble]);
        }
        if ($nightNoble !== null && $ganShang === $nightNoble) {
            return sprintf('日干寄宫%s上乘夜贵%s。', self::BRANCH_NAMES[$lodging], self::BRANCH_NAMES[$nightNoble]);
        }

        return null;
    }

    private static function liangGuiYinGanEvidence(int $initial, int $final, ?int $dayNoble, ?int $nightNoble): ?string
    {
        if ($dayNoble === null || $nightNoble === null) {
            return null;
        }

        $initialLabel = self::nobleLabel($initial, $dayNoble, $nightNoble);
        $finalLabel = self::nobleLabel($final, $dayNoble, $nightNoble);

        return sprintf(
            '初传%s（%s），末传%s（%s），恰为昼夜二贵。',
            self::BRANCH_NAMES[$initial],
            $initialLabel,
            self::BRANCH_NAMES[$final],
            $finalLabel,
        );
    }

    private static function nobleLabel(int $branch, int $dayNoble, int $nightNoble): string
    {
        if ($branch === $dayNoble) {
            return '昼贵';
        }
        if ($branch === $nightNoble) {
            return '夜贵';
        }

        return '非贵人';
    }

    /**
     * @param  array{role: string, nianming: int, xingnian: ?int, xingnian_gan: ?int}|null  $person
     */
    private static function guiLinGanZhiEvidence(
        bool $matched,
        bool $gangNianming,
        bool $peopleMissing,
        int $lodging,
        int $rizhi,
        ?int $ganShang,
        ?int $zhiShang,
        ?array $person,
        ?int $dayNoble,
        ?int $nightNoble,
    ): ?string {
        if ($peopleMissing && $matched) {
            return '昼夜二贵已分别加临日干寄宫与日支，但缺少占测者本命 / 行年资料，无法判断干支是否夹拱年命。';
        }

        if (! $gangNianming) {
            return null;
        }

        $ganLabel = $ganShang === $nightNoble ? '夜贵' : '昼贵';
        $zhiLabel = $zhiShang === $nightNoble ? '夜贵' : '昼贵';

        $targets = [];
        if ($person['nianming'] !== null) {
            $targets[] = '本命' . self::BRANCH_NAMES[$person['nianming']];
        }
        if ($person['xingnian'] !== null) {
            $targets[] = '行年' . self::BRANCH_NAMES[$person['xingnian']];
        }

        return sprintf(
            '%s%s加临日干寄宫%s，%s%s加临日支%s，干支前后夹拱%s。',
            $ganLabel,
            self::BRANCH_NAMES[$ganShang],
            self::BRANCH_NAMES[$lodging],
            $zhiLabel,
            self::BRANCH_NAMES[$zhiShang],
            self::BRANCH_NAMES[$rizhi],
            implode(' / ', $targets),
        );
    }

    /**
     * @param  array{role: string, nianming: int, xingnian: ?int, xingnian_gan: ?int}|null  $person
     */
    private static function erGuiGangNianmingEvidence(
        bool $twoNobles,
        bool $gangNianming,
        bool $peopleMissing,
        int $initial,
        int $final,
        int $initialGround,
        int $finalGround,
        ?array $person,
        ?int $dayNoble,
        ?int $nightNoble,
    ): ?string {
        if ($peopleMissing && $twoNobles) {
            return '初传、末传已分别为昼夜二贵，但缺少占测者本命 / 行年资料，无法判断初末是否夹拱年命。';
        }

        if (! $gangNianming) {
            return null;
        }

        $initialLabel = self::nobleLabel($initial, $dayNoble ?? -1, $nightNoble ?? -1);
        $finalLabel = self::nobleLabel($final, $dayNoble ?? -1, $nightNoble ?? -1);

        $targets = [];
        if ($person['nianming'] !== null) {
            $targets[] = '本命' . self::BRANCH_NAMES[$person['nianming']];
        }
        if ($person['xingnian'] !== null) {
            $targets[] = '行年' . self::BRANCH_NAMES[$person['xingnian']];
        }

        return sprintf(
            '初传%s临地盘%s（%s），末传%s临地盘%s（%s），初末夹拱%s。',
            self::BRANCH_NAMES[$initial],
            self::BRANCH_NAMES[$initialGround],
            $initialLabel,
            self::BRANCH_NAMES[$final],
            self::BRANCH_NAMES[$finalGround],
            $finalLabel,
            implode(' / ', $targets),
        );
    }

    private static function ganZhiGangRiLuEvidence(bool $fuyin, int $lodging, int $rizhi, ?int $lu): ?string
    {
        if (! $fuyin || $lu === null) {
            return null;
        }

        return sprintf(
            '伏吟盘，日干寄宫%s与日支%s前后夹拱日禄%s。',
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$rizhi],
            self::BRANCH_NAMES[$lu],
        );
    }

    private static function ganZhiGangGuiEvidence(bool $fuyin, int $lodging, int $rizhi, ?int $target, string $label): ?string
    {
        if (! $fuyin || $target === null) {
            return null;
        }

        return sprintf(
            '伏吟盘，日干寄宫%s与日支%s前后夹拱%s%s。',
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$rizhi],
            $label,
            self::BRANCH_NAMES[$target],
        );
    }

    private static function ganZhiBingChuZhongEvidence(
        bool $matched,
        ?string $targetKey,
        int $lodging,
        int $rizhi,
        int $initialGround,
        int $middleGround,
        ?int $dayNoble,
        ?int $nightNoble,
    ): ?string {
        if (! $matched) {
            return null;
        }

        $target = $targetKey === 'day_noble' ? $dayNoble : $nightNoble;
        $label = $targetKey === 'day_noble' ? '昼贵' : '夜贵';

        if ($target === null) {
            return null;
        }

        return sprintf(
            '干支夹拱%s%s（干寄宫%s、日支%s），同时初中夹拱同一%s（初传临地盘%s、中传临地盘%s）。',
            $label,
            self::BRANCH_NAMES[$target],
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$rizhi],
            $label,
            self::BRANCH_NAMES[$initialGround],
            self::BRANCH_NAMES[$middleGround],
        );
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
        bool $requiresPeople,
        bool $peopleMissing,
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'matched' => $matched,
            'detail' => $detail,
            'requires_people' => $requiresPeople,
            'people_missing' => $peopleMissing,
        ];
    }
}