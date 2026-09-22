<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 文件作用：按《毕法赋》第一法"前后引从升迁吉"逐分格判断当前盘面。
 *
 * 第一法共整理为 9 类古籍分格，对应 10 条程序判断 route——
 * 古籍"干支拱昼夜贵"为同一类，程序拆为昼贵与夜贵两条独立 route。
 *
 * 9 类古籍分格：
 *
 *  - 1. 引从天干                → route yin_gan
 *  - 2. 初末引从地支            → route yin_zhi
 *  - 3. 拱贵格                  → route gong_gui（基于引从天干）
 *  - 4. 两贵引从天干格          → route liang_gui_yin_gan（基于引从天干）
 *  - 5. 贵临干支拱年命           → route gui_lin_gan_zhi_gang_nianming
 *  - 6. 二贵拱年命              → route er_gui_gang_nianming
 *  - 7. 干支拱日禄（伏吟）       → route gan_zhi_gang_ri_lu
 *  - 8. 干支拱昼夜贵（伏吟）     → route gan_zhi_gang_zhou_gui / gan_zhi_gang_ye_gui（两条程序 route）
 *  - 9. 干支并初中拱地盘贵人     → route gan_zhi_bing_chu_zhong_gui
 *
 * 引从方向性：引从天干 / 引从地支 必须 初=前（寄宫/日支前一宫）、末=后（寄宫/日支后一宫）；
 * 颠倒则"引从"语义不成立，yin_gan / yin_zhi 不得命中。昼夜贵人 / 二贵等"无方向夹拱"分格
 * 仍使用 flanks(a, b, target) 不区分 a/b 前后。
 *
 * 人物资料边界：年命相关两条 route（gui_lin_gan_zhi_gang_nianming、er_gui_gang_nianming）
 * 需要 nianming 或 xingnian 任一存在；两者皆缺时整条 route 标记为待评估，但不影响其余
 * 7 类分格的判定。
 *
 * 命中证据：E/F 的 detail 文案只显示真正命中的本命/行年，不堆砌全部目标。
 */
final class QianHouYinCongRule implements BiFaRule
{
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
        return 'bifa.01';
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
     *     judgments: list<array<string, mixed>>
     * }
     */
    public function definition(): array
    {
        return [
            'description' => '初末传分临日干（或日支）前后宫，前引后从，主迁官进职、修宅迁居。',
            'foundations' => [
                ['code' => 'yin_gan',                        'title' => '引从天干',                       'description' => '日干寄宫前一宫位之上神发用作初传，后一宫位之上神作末传，前引后从夹拱天干。'],
                ['code' => 'yin_zhi',                        'title' => '初末引从地支',                   'description' => '日支前一宫位之上神发用作初传，后一宫位之上神作末传，前引后从夹拱地支。'],
                ['code' => 'gong_gui',                       'title' => '拱贵格',                         'description' => '引从天干成立，且日干寄宫之上神恰乘昼夜贵人之一。'],
                ['code' => 'liang_gui_yin_gan',              'title' => '两贵引从天干格',                 'description' => '引从天干成立，且初传、末传恰分别为昼夜二贵（贵人身份可互换，初末方向不可互换）。'],
                ['code' => 'gui_lin_gan_zhi_gang_nianming',  'title' => '贵临干支拱年命',                 'description' => '昼夜二贵分别加临日干寄宫与日支（方向不限），且干支前后夹拱占人本命或行年。'],
                ['code' => 'er_gui_gang_nianming',           'title' => '二贵拱年命',                     'description' => '初传、末传恰分别为昼夜二贵（贵人身份可互换），且初末前后夹拱本命或行年。'],
                ['code' => 'gan_zhi_gang_ri_lu',             'title' => '干支拱日禄',                     'description' => '伏吟盘，日干寄宫与日支前后夹拱日禄。'],
                ['code' => 'gan_zhi_gang_zhou_gui',          'title' => '干支拱昼贵',                     'description' => '伏吟盘，日干寄宫与日支前后夹拱昼贵。'],
                ['code' => 'gan_zhi_gang_ye_gui',            'title' => '干支拱夜贵',                     'description' => '伏吟盘，日干寄宫与日支前后夹拱夜贵。'],
                ['code' => 'gan_zhi_bing_chu_zhong_gui',     'title' => '干支并初中拱地盘贵人',           'description' => '干支夹拱昼夜贵人之一，且初传、中传也夹拱同一昼夜贵人；昼夜贵不得互换。'],
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

        $initialGround = $facts->heavenBranchGroundPosition($initial);
        $middleGround = $facts->heavenBranchGroundPosition($middle);
        $finalGround = $facts->heavenBranchGroundPosition($final);
        if ($initialGround === null || $middleGround === null || $finalGround === null) {
            return null;
        }

        $fuyin = $facts->hasPlatePattern('fuyin');
        $ganShangShen = $tianpan[$lodging] ?? null;
        $zhiShangShen = $tianpan[$rizhi] ?? null;

        // 方向性引从：初=前、末=后。前 = (+1) mod 12；后 = (+11) mod 12。
        $lodgingFront = ($lodging + 1) % 12;
        $lodgingBack = ($lodging + 11) % 12;
        $rizhiFront = ($rizhi + 1) % 12;
        $rizhiBack = ($rizhi + 11) % 12;

        // 分格 A：引从天干。初传临 lodign 前一宫，末传临 lodging 后一宫。
        $yinGan = $initialGround === $lodgingFront && $finalGround === $lodgingBack;

        // 分格 B：初末引从地支。初传临 rizhi 前一宫，末传临 rizhi 后一宫。
        $yinZhi = $initialGround === $rizhiFront && $finalGround === $rizhiBack;

        // 分格 C：拱贵格。引从天干 + 干上神 ∈ {昼夜贵}。
        $gongGui = $yinGan && self::isNoble($ganShangShen, $dayNoble, $nightNoble);

        // 分格 D：两贵引从天干格。引从天干 + {初传, 末传} == {昼贵, 夜贵}。
        // 贵人身份可互换，初末方向已由 yinGan 锁定为初=昼/夜、末=夜/昼。
        $liangGuiYinGan = $yinGan && self::areTransmissionsTwoNobles(
            $initial,
            $final,
            $dayNoble,
            $nightNoble,
        );

        // 占测者资料：nianming / xingnian 任一存在即视为可参与判断。
        $person = self::resolvePerson($facts);

        // 分格 E：贵临干支拱年命。{干上神, 支上神} == {昼贵, 夜贵} + 干支前后夹拱本命/行年。
        $guiLinGanZhiMatched = self::isTwoNoblesOnGanZhi(
            $ganShangShen,
            $zhiShangShen,
            $dayNoble,
            $nightNoble,
        );
        $guiLinGanZhiGangNianming = false;
        $guiLinGanZhiPeopleMissing = $person === null;
        $guiLinGanZhiHits = ['nianming' => false, 'xingnian' => false];
        if ($guiLinGanZhiMatched && $person !== null) {
            $guiLinGanZhiHits = self::flanksAnyTarget($lodging, $rizhi, $person);
            $guiLinGanZhiGangNianming = $guiLinGanZhiHits['nianming'] || $guiLinGanZhiHits['xingnian'];
        }

        // 分格 F：二贵拱年命。{初传, 末传} == {昼贵, 夜贵} + 初末前后夹拱本命/行年。
        $erGuiTransmissionsTwoNobles = self::areTransmissionsTwoNobles(
            $initial,
            $final,
            $dayNoble,
            $nightNoble,
        );
        $erGuiGangNianming = false;
        $erGuiPeopleMissing = $person === null;
        $erGuiHits = ['nianming' => false, 'xingnian' => false];
        if ($erGuiTransmissionsTwoNobles && $person !== null) {
            $erGuiHits = self::flanksAnyTarget($initialGround, $finalGround, $person);
            $erGuiGangNianming = $erGuiHits['nianming'] || $erGuiHits['xingnian'];
        }

        // 分格 G：干支拱日禄（伏吟）。
        $ganZhiGangRiLu = $fuyin && is_int($lu)
            && self::flanks($lodging, $rizhi, $lu);

        // 分格 H：干支拱昼贵 / 夜贵（伏吟）。
        $ganZhiGangZhouGui = $fuyin && is_int($dayNoble)
            && self::flanks($lodging, $rizhi, $dayNoble);
        $ganZhiGangYeGui = $fuyin && is_int($nightNoble)
            && self::flanks($lodging, $rizhi, $nightNoble);

        // 分格 I：干支并初中拱同一昼夜贵人。干支与初中必须拱同一贵人，不许互换。
        $ganZhiBingChuZhongGuiMatched = false;
        $ganZhiBingChuZongTargetKey = null;
        if (is_int($dayNoble)
            && self::flanks($lodging, $rizhi, $dayNoble)
            && self::flanks($initialGround, $middleGround, $dayNoble)) {
            $ganZhiBingChuZhongGuiMatched = true;
            $ganZhiBingChuZongTargetKey = 'day_noble';
        } elseif (is_int($nightNoble)
            && self::flanks($lodging, $rizhi, $nightNoble)
            && self::flanks($initialGround, $middleGround, $nightNoble)) {
            $ganZhiBingChuZhongGuiMatched = true;
            $ganZhiBingChuZongTargetKey = 'night_noble';
        }

        $subMatches = [
            self::subMatch('yin_gan', '引从天干', $yinGan,
                '日干寄宫前一宫位之上神发用作初传、后一宫位之上神作末传，前引后从夹拱天干。',
                $yinGan ? self::yinGanEvidence($initialGround, $finalGround, $lodging) : null,
                false, false),
            self::subMatch('yin_zhi', '初末引从地支', $yinZhi,
                '日支前一宫位之上神发用作初传、后一宫位之上神作末传，前引后从夹拱地支。',
                $yinZhi ? self::yinZhiEvidence($initialGround, $finalGround, $rizhi) : null,
                false, false),
            self::subMatch('gong_gui', '拱贵格', $gongGui,
                '引从天干成立的同时，日干寄宫之上神恰乘昼夜贵人之一。',
                self::gongGuiEvidence($gongGui, $ganShangShen, $dayNoble, $nightNoble),
                false, false),
            self::subMatch('liang_gui_yin_gan', '两贵引从天干格', $liangGuiYinGan,
                '引从天干成立的同时，初传、末传恰分别为昼夜二贵（贵人身份可互换，初末方向不可互换）。',
                self::liangGuiYinGanEvidence($liangGuiYinGan, $initial, $final, $dayNoble, $nightNoble),
                false, false),
            self::subMatch('gui_lin_gan_zhi_gang_nianming', '贵临干支拱年命',
                $guiLinGanZhiGangNianming,
                '昼夜二贵分别加临日干寄宫与日支（方向不限），且干支前后夹拱占人本命或行年。',
                self::guiLinGanZhiEvidence(
                    $guiLinGanZhiMatched,
                    $guiLinGanZhiGangNianming,
                    $guiLinGanZhiPeopleMissing,
                    $ganShangShen,
                    $zhiShangShen,
                    $lodging,
                    $rizhi,
                    $person,
                    $dayNoble,
                    $nightNoble,
                    $guiLinGanZhiHits,
                ),
                true, $guiLinGanZhiPeopleMissing),
            self::subMatch('er_gui_gang_nianming', '二贵拱年命',
                $erGuiGangNianming,
                '初传、末传恰分别为昼夜二贵（贵人身份可互换），且初末前后夹拱占人本命或行年。',
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
                    $erGuiHits,
                ),
                true, $erGuiPeopleMissing),
            self::subMatch('gan_zhi_gang_ri_lu', '干支拱日禄', $ganZhiGangRiLu,
                '伏吟盘，日干寄宫与日支前后夹拱日禄。',
                self::ganZhiGangRiLuEvidence($ganZhiGangRiLu, $fuyin, $lodging, $rizhi, $lu),
                false, false),
            self::subMatch('gan_zhi_gang_zhou_gui', '干支拱昼贵', $ganZhiGangZhouGui,
                '伏吟盘，日干寄宫与日支前后夹拱昼贵。',
                self::ganZhiGangGuiEvidence($ganZhiGangZhouGui, $fuyin, $lodging, $rizhi, $dayNoble, '昼贵'),
                false, false),
            self::subMatch('gan_zhi_gang_ye_gui', '干支拱夜贵', $ganZhiGangYeGui,
                '伏吟盘，日干寄宫与日支前后夹拱夜贵。',
                self::ganZhiGangGuiEvidence($ganZhiGangYeGui, $fuyin, $lodging, $rizhi, $nightNoble, '夜贵'),
                false, false),
            self::subMatch('gan_zhi_bing_chu_zhong_gui', '干支并初中拱地盘贵人',
                $ganZhiBingChuZhongGuiMatched,
                '干支夹拱昼夜贵人之一，同时初传、中传也夹拱同一昼夜贵人；昼夜贵不得互换。',
                self::ganZhiBingChuZhongEvidence(
                    $ganZhiBingChuZhongGuiMatched,
                    $ganZhiBingChuZongTargetKey,
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
        $pendingRoutes = [];
        foreach ($subMatches as $sub) {
            if ($sub['matched']) {
                $matchedRoutes[] = $sub['code'];
            } elseif ($sub['requires_people'] && $sub['people_missing']) {
                $pendingRoutes[] = $sub['code'];
            }
        }

        // 正常排盘只返回"已命中"或"有真正待评估路线"的毕法；
        // 两者皆空时返回 null，避免 100 法全未命中时还把卡片铺满排盘页。
        if ($matchedRoutes === [] && $pendingRoutes === []) {
            return null;
        }

        return new BiFaRuleMatch(
            code: $this->code(),
            number: $this->law()['number'],
            name: $this->law()['name'],
            summary: $this->law()['summary'],
            subMatches: $subMatches,
            matchedRoutes: $matchedRoutes,
            pendingRoutes: $pendingRoutes,
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
     * 无方向夹拱：a、b 两个宫位只要分别位于 target 的前一宫 / 后一宫，前后顺序不区分。
     */
    private static function flanks(int $a, int $b, int $target): bool
    {
        $front = ($target + 1) % 12;
        $back = ($target + 11) % 12;

        return ($a === $front && $b === $back) || ($a === $back && $b === $front);
    }

    /**
     * 返回结构化的占测者本命 / 行年信息：nianming 或 xingnian 任一存在即视为可参与判断。
     *
     * @return array{nianming: ?int, xingnian: ?int, xingnian_gan: ?int}|null
     */
    private static function resolvePerson(PanFacts $facts): ?array
    {
        $person = $facts->personByRole('querent');
        if ($person === null) {
            return null;
        }

        $nianmingRaw = $person['nianming'] ?? null;
        $xingnianRaw = $person['xingnian'] ?? null;

        $nianming = is_int($nianmingRaw) ? $nianmingRaw : null;
        $xingnian = is_int($xingnianRaw) ? $xingnianRaw : null;
        $xingnianGanRaw = $person['xingnian_gan'] ?? null;
        $xingnianGan = is_int($xingnianGanRaw) ? $xingnianGanRaw : null;

        if ($nianming === null && $xingnian === null) {
            return null;
        }

        return [
            'nianming' => $nianming,
            'xingnian' => $xingnian,
            'xingnian_gan' => $xingnianGan,
        ];
    }

    /**
     * 干支夹拱本命 / 行年（无方向）：分别检 target = 本命 与 target = 行年，
     * 返回各自是否命中；调用者按"只显示真正命中的目标"组合文案。
     *
     * @param  array{nianming: ?int, xingnian: ?int, xingnian_gan: ?int}  $person
     * @return array{nianming: bool, xingnian: bool}
     */
    private static function flanksAnyTarget(int $lodgingFront, int $lodgingBack, array $person): array
    {
        return [
            'nianming' => $person['nianming'] !== null
                && self::flanks($lodgingFront, $lodgingBack, $person['nianming']),
            'xingnian' => $person['xingnian'] !== null
                && self::flanks($lodgingFront, $lodgingBack, $person['xingnian']),
        ];
    }

    private static function yinGanEvidence(int $initialGround, int $finalGround, int $lodging): ?string
    {
        return sprintf(
            '初传临地盘%s，末传临地盘%s，前后夹拱日干寄宫%s。',
            self::BRANCH_NAMES[$initialGround],
            self::BRANCH_NAMES[$finalGround],
            self::BRANCH_NAMES[$lodging],
        );
    }

    private static function yinZhiEvidence(int $initialGround, int $finalGround, int $rizhi): ?string
    {
        return sprintf(
            '初传临地盘%s，末传临地盘%s，前后夹拱日支%s。',
            self::BRANCH_NAMES[$initialGround],
            self::BRANCH_NAMES[$finalGround],
            self::BRANCH_NAMES[$rizhi],
        );
    }

    private static function gongGuiEvidence(bool $matched, ?int $ganShang, ?int $dayNoble, ?int $nightNoble): ?string
    {
        if (! $matched || $ganShang === null) {
            return null;
        }

        if ($dayNoble !== null && $ganShang === $dayNoble) {
            return sprintf('日干寄宫上乘昼贵%s。', self::BRANCH_NAMES[$dayNoble]);
        }
        if ($nightNoble !== null && $ganShang === $nightNoble) {
            return sprintf('日干寄宫上乘夜贵%s。', self::BRANCH_NAMES[$nightNoble]);
        }

        return null;
    }

    private static function liangGuiYinGanEvidence(bool $matched, int $initial, int $final, ?int $dayNoble, ?int $nightNoble): ?string
    {
        if (! $matched || $dayNoble === null || $nightNoble === null) {
            return null;
        }

        return sprintf(
            '初传%s（%s），末传%s（%s），恰为昼夜二贵。',
            self::BRANCH_NAMES[$initial],
            self::nobleLabel($initial, $dayNoble, $nightNoble),
            self::BRANCH_NAMES[$final],
            self::nobleLabel($final, $dayNoble, $nightNoble),
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
     * E 分格的命中证据：只显示真正命中的目标，避免堆砌本命/行年全部文案。
     *
     * @param  array{nianming: ?int, xingnian: ?int, xingnian_gan: ?int}|null  $person
     */
    private static function guiLinGanZhiEvidence(
        bool $matched,
        bool $gangNianming,
        bool $peopleMissing,
        ?int $ganShang,
        ?int $zhiShang,
        int $lodging,
        int $rizhi,
        ?array $person,
        ?int $dayNoble,
        ?int $nightNoble,
        array $hits = ['nianming' => false, 'xingnian' => false],
    ): ?string {
        if ($peopleMissing && $matched) {
            return '昼夜二贵已分别加临日干寄宫与日支，但缺少占测者本命 / 行年资料，无法判断干支是否夹拱年命。';
        }

        if (! $gangNianming || $person === null) {
            return null;
        }

        $ganLabel = $ganShang === $nightNoble ? '夜贵' : '昼贵';
        $zhiLabel = $zhiShang === $nightNoble ? '夜贵' : '昼贵';

        $targets = [];
        if ($person['nianming'] !== null && $hits['nianming']) {
            $targets[] = '本命'.self::BRANCH_NAMES[$person['nianming']];
        }
        if ($person['xingnian'] !== null && $hits['xingnian']) {
            $targets[] = '行年'.self::BRANCH_NAMES[$person['xingnian']];
        }
        if ($targets === []) {
            return null;
        }

        return sprintf(
            '%s%s加临日干寄宫%s，%s%s加临日支%s，干支前后夹拱%s。',
            $ganLabel,
            self::BRANCH_NAMES[$ganShang],
            self::BRANCH_NAMES[$lodging],
            $zhiLabel,
            self::BRANCH_NAMES[$zhiShang],
            self::BRANCH_NAMES[$rizhi],
            implode('、', $targets),
        );
    }

    /**
     * F 分格的命中证据：只显示真正命中的目标。
     *
     * @param  array{nianming: ?int, xingnian: ?int, xingnian_gan: ?int}|null  $person
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
        array $hits = ['nianming' => false, 'xingnian' => false],
    ): ?string {
        if ($peopleMissing && $twoNobles) {
            return '初传、末传已分别为昼夜二贵，但缺少占测者本命 / 行年资料，无法判断初末是否夹拱年命。';
        }

        if (! $gangNianming || $person === null) {
            return null;
        }

        $initialLabel = self::nobleLabel($initial, $dayNoble ?? -1, $nightNoble ?? -1);
        $finalLabel = self::nobleLabel($final, $dayNoble ?? -1, $nightNoble ?? -1);

        $targets = [];
        if ($person['nianming'] !== null && $hits['nianming']) {
            $targets[] = '本命'.self::BRANCH_NAMES[$person['nianming']];
        }
        if ($person['xingnian'] !== null && $hits['xingnian']) {
            $targets[] = '行年'.self::BRANCH_NAMES[$person['xingnian']];
        }
        if ($targets === []) {
            return null;
        }

        return sprintf(
            '初传%s临地盘%s（%s），末传%s临地盘%s（%s），初末前后夹拱%s。',
            self::BRANCH_NAMES[$initial],
            self::BRANCH_NAMES[$initialGround],
            $initialLabel,
            self::BRANCH_NAMES[$final],
            self::BRANCH_NAMES[$finalGround],
            $finalLabel,
            implode('、', $targets),
        );
    }

    private static function ganZhiGangRiLuEvidence(bool $matched, bool $fuyin, int $lodging, int $rizhi, ?int $lu): ?string
    {
        if (! $matched || ! $fuyin || $lu === null) {
            return null;
        }

        return sprintf(
            '伏吟盘，日干寄宫%s与日支%s前后夹拱日禄%s。',
            self::BRANCH_NAMES[$lodging],
            self::BRANCH_NAMES[$rizhi],
            self::BRANCH_NAMES[$lu],
        );
    }

    private static function ganZhiGangGuiEvidence(bool $matched, bool $fuyin, int $lodging, int $rizhi, ?int $target, string $label): ?string
    {
        if (! $matched || ! $fuyin || $target === null) {
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
        if (! $matched || $targetKey === null) {
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
