<?php

namespace App\Support;

/**
 * 文件作用：维护《毕法赋》"百法"案例目录——每条案例与一个法号绑定、与课经 KeJingCatalog
 * 完全独立。即使同一古籍盘同时属课经与毕法，也由两套独立目录分别登记，
 * 各自的 case_id、源文档、链接互不交叉。
 *
 * 案例字段：
 *
 *  - case_id      :string              全局唯一 id，例如 `bifa.01.geng-chen-yin-gan`；
 *  - law_code     :string              归属毕法目录 code（`bifa.NN`）；
 *  - label        :string              UI 简述；
 *  - source_type  :'daquan'|'generated' 古籍正文案例 / 程序验证案例；
 *  - status       :'executable'|'reference_only' 是否可被当前 PanCalculator 复现；
 *  - datetime     :?string             起课时间（YYYY-MM-DDTHH:MM，Asia/Shanghai）；
 *  - birth        :?string             占测者出生时间；
 *  - gender       :?string             'male' / 'female'；
 *  - people       :list<...>           其它相关人物；
 *  - routes       :list<string>        命中的程序 route（用于详情页/排盘页相关案例筛选）；
 *  - reason       :string              UI 显示的"为什么被选入"理由；
 *  - source       :string              出处原始文字，例如"《六壬大全·毕法赋》第一法"。
 *
 * 设计与约束：
 *
 *  - 不依赖 KeJingCatalog 或课经 RuleMatch；
 *  - 不从 YinCongRule 或其它 PanRule 推导；
 *  - case_id 与课经 case_id 命名空间独立（`lesson.*` vs `bifa.*`）；
 *  - 排盘"毕法"区块按"案例的 routes 与当前 match.matchedRoutes 有交集"过滤；
 *  - 排盘页"相关案例"链接只展示 executable + routes 交集命中者。
 */
final class BiFaCaseCatalog
{
    /**
     * @return list<array{
     *     case_id: string,
     *     law_code: string,
     *     label: string,
     *     source_type: 'daquan'|'generated',
     *     status: 'executable'|'reference_only',
     *     datetime: ?string,
     *     birth: ?string,
     *     gender: ?string,
     *     people: list<array{role: string, birth_datetime: string, gender: string}>,
     *     routes: list<string>,
     *     reason: string,
     *     source: string
     * }>
     */
    public static function cases(): array
    {
        return self::CASES;
    }

    /**
     * @return list<array{
     *     case_id: string,
     *     law_code: string,
     *     label: string,
     *     source_type: 'daquan'|'generated',
     *     status: 'executable'|'reference_only',
     *     datetime: ?string,
     *     birth: ?string,
     *     gender: ?string,
     *     people: list<array{role: string, birth_datetime: string, gender: string}>,
     *     routes: list<string>,
     *     reason: string,
     *     source: string
     * }>
     */
    public static function casesForLaw(string $lawCode): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (array $case): bool => $case['law_code'] === $lawCode,
        ));
    }

    /**
     * @return array{
     *     case_id: string,
     *     law_code: string,
     *     label: string,
     *     source_type: 'daquan'|'generated',
     *     status: 'executable'|'reference_only',
     *     datetime: ?string,
     *     birth: ?string,
     *     gender: ?string,
     *     people: list<array{role: string, birth_datetime: string, gender: string}>,
     *     routes: list<string>,
     *     reason: string,
     *     source: string
     * }|null
     */
    public static function findByCaseId(string $caseId): ?array
    {
        foreach (self::cases() as $case) {
            if ($case['case_id'] === $caseId) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $matchedRoutes
     * @return list<array{
     *     case_id: string,
     *     law_code: string,
     *     label: string,
     *     source_type: 'daquan'|'generated',
     *     status: 'executable'|'reference_only',
     *     datetime: ?string,
     *     birth: ?string,
     *     gender: ?string,
     *     people: list<array{role: string, birth_datetime: string, gender: string}>,
     *     routes: list<string>,
     *     reason: string,
     *     source: string
     * }>
     */
    public static function casesByMatchedRoutes(string $lawCode, array $matchedRoutes): array
    {
        if ($matchedRoutes === []) {
            return [];
        }

        return array_values(array_filter(
            self::casesForLaw($lawCode),
            static fn (array $case): bool => array_intersect($case['routes'], $matchedRoutes) !== [],
        ));
    }

    /**
     * @var list<array{
     *     case_id: string,
     *     law_code: string,
     *     label: string,
     *     source_type: 'daquan'|'generated',
     *     status: 'executable'|'reference_only',
     *     datetime: ?string,
     *     birth: ?string,
     *     gender: ?string,
     *     people: list<array{role: string, birth_datetime: string, gender: string}>,
     *     routes: list<string>,
     *     reason: string,
     *     source: string
     * }>
     */
    private const CASES = [
        // ----------------------------------------------------------------------
        // 第三法 · 帘幕贵人高甲第
        // ----------------------------------------------------------------------
        [
            'case_id' => 'bifa.03.generated-ya-kui-on-stem', 'law_code' => 'bifa.03',
            'label' => '程序验证·亚魁临干', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-01T03:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['ya_kui_you_on_stem_or_fate'],
            'reason' => '生产排盘中酉加临日干寄宫，真实复现亚魁临干；这是程序搜索的现代盘，不冒充古籍 datetime。',
            'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-curtain-on-stem', 'law_code' => 'bifa.03',
            'label' => '程序验证·帘幕贵人临干', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-02T17:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['curtain_noble_on_stem_or_fate'], 'reason' => '生产排盘真实复现帘幕贵人临日干寄宫。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-xun-head-curtain', 'law_code' => 'bifa.03',
            'label' => '程序验证·旬首作帘幕', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-21T15:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['curtain_noble_on_stem_or_fate', 'xun_head_as_curtain_noble'], 'reason' => '生产排盘中旬首自然等于帘幕贵人并临干，无日干白名单。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-chen-xu-xun-head', 'law_code' => 'bifa.03',
            'label' => '程序验证·辰戌旬首临干', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-04T21:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['chen_xu_xun_head_on_stem_or_fate'], 'reason' => '生产排盘真实复现辰戌旬首临日干寄宫。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-dou-gui', 'law_code' => 'bifa.03',
            'label' => '程序验证·斗鬼相加', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-03T13:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['dou_gui_on_stem_or_fate'], 'reason' => '生产排盘真实复现丑未互加发生在日干路径。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-day-virtue', 'law_code' => 'bifa.03',
            'label' => '程序验证·德入天门', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-02T01:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['day_virtue_enters_heaven_gate'], 'reason' => '生产排盘真实复现日德加地盘亥宫并发用。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-true-vermilion', 'law_code' => 'bifa.03',
            'label' => '程序验证·真朱雀', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-08T23:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['true_vermilion_bird'], 'reason' => '生产排盘真实复现己酉日、夜占、夜贵逆布及午乘朱雀四项主体条件；当日太岁为戌（四季年），对应「真朱雀生太岁」增强断义。', 'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-true-vermilion-controls-taisui', 'law_code' => 'bifa.03',
            'label' => '程序验证·真朱雀克太岁', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2017-01-02T01:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['true_vermilion_bird'], 'reason' => '2017 丙申年生产排盘真实复现真朱雀四项主体条件；当日太岁为申，对应「真朱雀克太岁」减损断义；用于验证主体结构不依赖「四季年」、申酉年也能成立。', 'source' => '程序验证案例·2017 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.generated-two-nobles-flank-fate', 'law_code' => 'bifa.03',
            'label' => '程序验证·昼夜二贵拱本命', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-03-24T11:00', 'birth' => '1985-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['two_nobles_flank_fate'], 'reason' => '生产排盘昼夜二贵分别临干支，干支夹拱乙丑年出生占者的本命丑。', 'source' => '程序验证案例·2031 年生产 PanCalculator 与 FateCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.03.daquan-curtain-noble', 'law_code' => 'bifa.03',
            'label' => '正文结构·帘幕贵人临干年命', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['curtain_noble_on_stem_or_fate'],
            'reason' => '原文只给结构，没有完整公历 datetime，故为 reference_only，不能伪造时间回填。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-xun-head-curtain', 'law_code' => 'bifa.03',
            'label' => '正文结构·旬首作帘幕', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['curtain_noble_on_stem_or_fate', 'xun_head_as_curtain_noble'],
            'reason' => '原文仅列乙、己、辛日的结构结论，没有完整 datetime，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-ya-kui', 'law_code' => 'bifa.03',
            'label' => '正文结构·亚魁临干年命', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['ya_kui_you_on_stem_or_fate'],
            'reason' => '原文给出亚魁酉临年命、日干的结构，但没有足以唯一映射现代公历的完整 datetime，因此作为 reference_only 原文参考结构保存。',
            'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-chen-xu-xun-head', 'law_code' => 'bifa.03',
            'label' => '正文结构·甲辰甲戌旬首', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['chen_xu_xun_head_on_stem_or_fate'],
            'reason' => '正文给出甲辰、甲戌两旬而未给完整 datetime，无法直接复现，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-dou-gui', 'law_code' => 'bifa.03',
            'label' => '正文结构·丑未斗鬼相加', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['dou_gui_on_stem_or_fate'],
            'reason' => '正文只有丑加未、未加丑结构，没有完整 datetime，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-day-virtue', 'law_code' => 'bifa.03',
            'label' => '正文结构·德入天门', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['day_virtue_enters_heaven_gate'],
            'reason' => '正文只定义日德加亥发用，没有完整 datetime，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-true-vermilion', 'law_code' => 'bifa.03',
            'label' => '正文结构·真朱雀', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['true_vermilion_bird'],
            'reason' => '正文给六己日、四季年、夜贵逆布、朱雀乘午，但无完整 datetime，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法',
        ],
        [
            'case_id' => 'bifa.03.daquan-two-nobles', 'law_code' => 'bifa.03',
            'label' => '丁酉日·昼夜二贵拱年命', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['two_nobles_flank_fate'],
            'reason' => '正文给丁酉日、干上酉、支上亥、年命申，但未给完整 datetime，无法据此唯一复现，故为 reference_only。', 'source' => '《六壬大全·毕法赋》第三法·丁酉日条',
        ],

        // ----------------------------------------------------------------------
        // 第一法 · 前后引从升迁吉
        // ----------------------------------------------------------------------
        [
            'case_id' => 'bifa.01.geng-chen-yin-gan',
            'law_code' => 'bifa.01',
            'label' => '庚辰日·引从天干、拱贵',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-01-23T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['yin_gan', 'gong_gui'],
            'reason' => '初传寅加临地盘酉，末传子加临地盘未，前后夹拱庚干寄宫申；干上丑恰为庚日昼贵。',
            'source' => '《六壬大全·毕法赋》第一法·庚辰日条',
        ],
        [
            'case_id' => 'bifa.01.jia-wu-yin-zhi',
            'law_code' => 'bifa.01',
            'label' => '甲午日·初末引从地支',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-02-06T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['yin_zhi'],
            'reason' => '初传子居日支午前一位未，末传戌居日支午后一位巳，前后夹拱地支午。',
            'source' => '《六壬大全·毕法赋》第一法·甲午日条',
        ],
        [
            'case_id' => 'bifa.01.ren-zi-liang-gui-yin-gan',
            'law_code' => 'bifa.01',
            'label' => '壬子日·两贵引从天干',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-02-24T11:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['yin_gan', 'liang_gui_yin_gan'],
            'reason' => '拱天干的同时，初传巳恰为壬日昼贵、末传卯恰为夜贵，为两贵引从天干格。',
            'source' => '《六壬大全·毕法赋》第一法·壬子日条',
        ],
        [
            'case_id' => 'bifa.01.ding-you-gui-lin-gan-zhi-gang-nianming',
            'law_code' => 'bifa.01',
            'label' => '丁酉日·贵临干支拱本命',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-02-09T19:00',
            'birth' => '1980-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gui_lin_gan_zhi_gang_nianming'],
            'reason' => '夜贵酉加临丁干寄宫未，昼贵亥加临日支酉，干支前后夹拱年命申。',
            'source' => '《六壬大全·毕法赋》第一法·丁酉日条',
        ],
        [
            'case_id' => 'bifa.01.generated-ding-si-gang-xingnian',
            'law_code' => 'bifa.01',
            'label' => '程序验证·丁巳日·贵临干支拱行年',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-02-29T13:00',
            'birth' => '1990-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gui_lin_gan_zhi_gang_nianming'],
            'reason' => '用于验证 E 分格的"行年"路径：第一法古籍原文仅以丁酉日示例"贵临干支拱年命"，未给定"行年"侧具体日干支案例。本案例沿用丁巳日盘面（昼夜二贵分别加临丁寄宫未与日支巳），构造一个行年可被干支夹拱的命盘，复用现有排盘 datetime+birth+gender 即可自动起盘并命中声明 route。',
            'source' => '程序验证案例·由第一法分格 5（贵临干支拱年命）行年路径构造，盘面格式沿用通行本同类案例',
        ],
        [
            'case_id' => 'bifa.01.ding-si-fu-yin-gang-ri-lu',
            'law_code' => 'bifa.01',
            'label' => '丁巳日伏吟·干支拱日禄',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-06-28T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_ri_lu'],
            'reason' => '伏吟盘，丁寄未与日支巳前后夹拱日禄午。',
            'source' => '《六壬大全·毕法赋》第一法·丁巳日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.ji-si-fu-yin-gang-ri-lu',
            'law_code' => 'bifa.01',
            'label' => '己巳日伏吟·干支拱日禄',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-01-12T01:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_ri_lu'],
            'reason' => '伏吟盘，己寄未与日支巳前后夹拱日禄午（己禄午）。',
            'source' => '《六壬大全·毕法赋》第一法·己巳日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.gui-hai-fu-yin-gang-ri-lu',
            'law_code' => 'bifa.01',
            'label' => '癸亥日伏吟·干支拱日禄',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-01-06T01:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_ri_lu'],
            'reason' => '伏吟盘，癸寄丑与日支亥前后夹拱日禄子（癸禄子）。',
            'source' => '《六壬大全·毕法赋》第一法·癸亥日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.geng-wu-fu-yin-gang-ye-gui',
            'law_code' => 'bifa.01',
            'label' => '庚午日伏吟·干支拱夜贵',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-07-11T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_ye_gui'],
            'reason' => '伏吟盘，庚寄申与日支午前后夹拱夜贵未。',
            'source' => '《六壬大全·毕法赋》第一法·庚午日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.ji-you-fu-yin-gang-ye-gui',
            'law_code' => 'bifa.01',
            'label' => '己酉日伏吟·干支拱夜贵',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-02-21T21:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_ye_gui', 'gan_zhi_bing_chu_zhong_gui'],
            'reason' => '伏吟盘，己寄未与日支酉前后夹拱夜贵申（己夜贵申）；同时初传酉、中传未亦夹拱同一夜贵申，符合"干支并初中拱地盘贵人"。',
            'source' => '《六壬大全·毕法赋》第一法·己酉日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.jia-zi-fu-yin-gang-zhou-gui',
            'law_code' => 'bifa.01',
            'label' => '甲子日伏吟·干支拱昼贵',
            'source_type' => 'daquan',
            'status' => 'executable',
            'datetime' => '2000-07-05T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gan_zhi_gang_zhou_gui'],
            'reason' => '伏吟盘，甲寄寅与日支子前后夹拱昼贵丑。',
            'source' => '《六壬大全·毕法赋》第一法·甲子日伏吟条',
        ],
        [
            'case_id' => 'bifa.01.generated-ding-hai-gan-zhi-bing-chu',
            'law_code' => 'bifa.01',
            'label' => '程序验证·丁亥日伏吟·干支并初中拱地盘贵人',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['gan_zhi_bing_chu_zhong_gui'],
            'reason' => '第一法古籍原文对"若干支并初中及中末拱地贵"只给出抽象条文（"若干支并初中及中末拱地贵，告贵谋事亦吉"），未给定任何具体日干支案例。本案例由古籍条文 + 现代命盘规律自行构造一个可能命中 gan_zhi_bing_chu_zhong_gui 的命盘（丁日伏吟），待 PanCalculator 给出可自动复现的 datetime 后回填 executable。',
            'source' => '程序验证案例·基于第一法分格 9（干支并初中拱地盘贵人）条文构造',
        ],

        // ----------------------------------------------------------------------
        // 程序验证案例——覆盖部分需要程序日期复现、但古籍未直接给出现代公历的分支。
        // ----------------------------------------------------------------------
        [
            'case_id' => 'bifa.01.generated-yin-gan-no-people',
            'law_code' => 'bifa.01',
            'label' => '程序验证·引从天干（无人物资料）',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => '2000-01-23T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['yin_gan'],
            'reason' => '用于验证引从天干分格在缺人物资料时仍可判定。打开本案例 URL 后 Livewire 会自动用 birth+gender 创建 querent（含 nianming+xingnian），无法重现"无人物资料"状态，因此不可作为 executable——改为 reference_only，由 QianHouYinCongRuleTest::年命资料缺失不影响其他分格 单元测试直接覆盖该判定路径。',
            'source' => '程序验证案例·用于覆盖 yin_gan 在无人物资料下的命中路径（仅限单元测试）',
        ],
        [
            'case_id' => 'bifa.01.generated-er-gui-gang-nianming',
            'law_code' => 'bifa.01',
            'label' => '程序验证·二贵拱本命',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-02-24T11:00',
            'birth' => '1983-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['er_gui_gang_nianming'],
            'reason' => '用于验证 er_gui_gang_nianming 分格的本命路径；壬子日巳时三传巳（昼贵）、卯（夜贵），干支寄宫亥，本命亥（生于 1983 癸亥年）被初末夹拱。',
            'source' => '程序验证案例·用于覆盖 er_gui_gang_nianming 的本命路径',
        ],
        [
            'case_id' => 'bifa.01.generated-er-gui-gang-xingnian',
            'law_code' => 'bifa.01',
            'label' => '程序验证·二贵拱行年',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-02-24T11:00',
            'birth' => '1991-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['er_gui_gang_nianming'],
            'reason' => '用于验证 er_gui_gang_nianming 分格的行年路径；本命辛酉年（酉=9）不在夹拱带，行年落于亥（生于 1991 辛未年，行年顺数至亥）。',
            'source' => '程序验证案例·用于覆盖 er_gui_gang_nianming 的行年路径',
        ],
        [
            'case_id' => 'bifa.01.generated-only-nianming',
            'law_code' => 'bifa.01',
            'label' => '程序验证·仅本命存在可行年缺失',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => '2000-02-09T19:00',
            'birth' => '1980-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gui_lin_gan_zhi_gang_nianming'],
            'reason' => '用于验证 E 分格在仅本命存在、行年缺失的情况下能正常判定。打开本案例 URL 后 Livewire 会自动用 birth+gender 创建 querent（含 nianming+xingnian），无法重现"仅本命"状态，因此不可作为 executable——改为 reference_only，由 QianHouYinCongRuleTest::贵临干支拱年命 分格：仅本命存在可行年缺失也可判定 单元测试直接覆盖。',
            'source' => '程序验证案例·用于覆盖 people 部分缺失边界（仅限单元测试）',
        ],
        [
            'case_id' => 'bifa.01.generated-only-xingnian',
            'law_code' => 'bifa.01',
            'label' => '程序验证·仅行年存在可本命缺失',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => '2000-02-09T19:00',
            'birth' => '1980-06-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['gui_lin_gan_zhi_gang_nianming'],
            'reason' => '用于验证 E 分格在仅行年存在、本命缺失的情况下能正常判定。打开本案例 URL 后 Livewire 会自动用 birth+gender 创建 querent（含 nianming+xingnian），无法重现"仅行年"状态，因此不可作为 executable——改为 reference_only，由 QianHouYinCongRuleTest::贵临干支拱年命 分格：仅行年存在可本命缺失也可判定 单元测试直接覆盖。',
            'source' => '程序验证案例·用于覆盖 people 部分缺失边界（仅限单元测试）',
        ],
        [
            'case_id' => 'bifa.01.generated-no-match-no-pending',
            'law_code' => 'bifa.01',
            'label' => '程序验证·完全不命中且无待评估',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => '2000-01-09T07:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => [],
            'reason' => '用于验证 BiFaRuleEngine 在 matched_routes 与 pending_routes 同时为空时返回 null——即 100 法未命中时排盘页不应出现该毕法卡片。本案例本身不命中第一法，故标为 reference_only，对应 engine 行为由 BiFaPageTest::bifa engine returns null 单独覆盖。',
            'source' => '程序验证案例·用于覆盖 BiFaRuleEngine 的 null 返回策略',
        ],
        [
            'case_id' => 'bifa.01.generated-yin-zhi-only',
            'law_code' => 'bifa.01',
            'label' => '程序验证·初末引从地支（独立命中）',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-02-06T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['yin_zhi'],
            'reason' => '用于验证 yin_zhi 分格在 yin_gan 不成立时仍可单独命中——引支和引干分属两条独立 route，互不依赖。',
            'source' => '程序验证案例·用于覆盖 yin_zhi 独立命中路径',
        ],
        [
            'case_id' => 'bifa.01.generated-yin-gan-yin-zhi-both',
            'law_code' => 'bifa.01',
            'label' => '程序验证·引从天干、引从地支同时成立',
            'source_type' => 'generated',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['yin_gan', 'yin_zhi'],
            'reason' => '用于验证 yin_gan 与 yin_zhi 在同一盘面可同时成立的程序判定路径。理论上前引后从结构需要 lodging == rizhi（干寄宫与日支同位），但当前 PanCalculator 在 2000-2030 年的已知日子中未发现该条件下的合适时辰。本案例作为 reference_only 登记，待后续 PanCalculator 升级或调整实现后再回填 executable datetime。yin_gan 与 yin_zhi 各自的单路径命中已由 geng-chen-yin-gan（yin_gan）与 jia-wu-yin-zhi（yin_zhi）覆盖。',
            'source' => '程序验证案例·用于覆盖 yin_gan / yin_zhi 双命中',
        ],

        // ----------------------------------------------------------------------
        // 第二法 · 首尾相见始终宜
        // ----------------------------------------------------------------------
        [
            'case_id' => 'bifa.02.yi-wei-xun-tail-stem-xun-head-branch',
            'law_code' => 'bifa.02',
            'label' => '乙未日·周而复始·旬尾临干、旬首临支',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '1986-08-19T13:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['xun_tail_on_stem_xun_head_on_branch'],
            'reason' => '古籍给出乙未日旬尾临干、旬首临支的结构，但没有给出可直接对应现代公历 datetime 的完整课例。乙未日属甲午旬，旬首午、旬尾卯；乙寄辰，干上卯恰为旬尾，支上午恰为旬首。本案例 datetime 为使用 PanCalculator 搜索并验证得到的现代生产复现时间。',
            'source' => '基于《六壬大全·毕法赋》第二法正文结构示例的现代生产复现',
        ],
        [
            'case_id' => 'bifa.02.yi-chou-xun-head-stem-xun-tail-branch',
            'law_code' => 'bifa.02',
            'label' => '乙丑日·周而复始·旬首临干、旬尾临支',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2004-04-16T03:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['xun_head_on_stem_xun_tail_on_branch'],
            'reason' => '古籍给出乙丑日旬首临干、旬尾临支的结构，但没有给出可直接对应现代公历 datetime 的完整课例。乙丑日属甲子旬，旬首子、旬尾酉；乙寄辰，干上子恰为旬首，支上酉恰为旬尾。本案例 datetime 为使用 PanCalculator 搜索并验证得到的现代生产复现时间。',
            'source' => '基于《六壬大全·毕法赋》第二法正文结构示例的现代生产复现',
        ],
        [
            'case_id' => 'bifa.02.tianxin-yi-si',
            'law_code' => 'bifa.02',
            'label' => '乙巳日·天心格',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2044-08-24T17:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['tianxin_four_establishments_in_lessons'],
            'reason' => '古籍《六壬大全·课经·盘珠课》给出甲子年七月乙巳日酉时巳将的天心格案例材料；它不是《毕法赋》第二法直接给出的现代完整课例。本盘四建为太岁子、月建申、日支巳、占时酉，皆在四课地支集合内；2044-08-24T17:00 是使用 PanCalculator 搜索并验证得到的现代生产复现时间。',
            'source' => '基于《六壬大全·课经·盘珠课》天心格正文示例的现代生产复现',
        ],
        [
            'case_id' => 'bifa.02.huihuan-xin-hai',
            'law_code' => 'bifa.02',
            'label' => '辛亥日·回还格',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '1986-09-04T11:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['huihuan_transmissions_in_lessons'],
            'reason' => '古籍给出辛亥日三传戌酉申的回还格结构，但没有给出可直接对应现代公历 datetime 的完整课例。本盘四课地支集合含 {酉、申、亥、戌}，三传尽在集合内。本案例 datetime 为使用 PanCalculator 搜索并验证得到的现代生产复现时间。',
            'source' => '基于《六壬大全·毕法赋》第二法直接记载的辛亥日回还格结构的现代生产复现',
        ],

        // ----------------------------------------------------------------------
        // 第四法 · 催官使者赴官期
        // ----------------------------------------------------------------------
        // 古籍正文未给出完整现代 datetime 的结构，统一登记为 reference_only；
        // 程序验证案例由 PanCalculator 扫描 2000..2031 全年得到。
        [
            'case_id' => 'bifa.04.daquan-cui-guan-messenger',
            'law_code' => 'bifa.04',
            'label' => '正文结构·催官使者·官星乘白虎临日干寄宫',
            'source_type' => 'daquan',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['cui_guan_messenger'],
            'reason' => '《六壬大全·毕法赋》第四法催官使者条：原文仅描述抽象规则，无完整公历课例，故仅作 reference_only。',
            'source' => '《六壬大全·毕法赋》第四法·催官使者条',
        ],
        [
            'case_id' => 'bifa.04.daquan-cui-guan-talisman',
            'law_code' => 'bifa.04',
            'label' => '正文结构·催官符',
            'source_type' => 'daquan',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['cui_guan_talisman'],
            'reason' => '《大全》定义官星临日干年命，三传上神生其官星；无完整日期时间，故仅作 reference_only。',
            'source' => '《六壬大全·毕法赋》第四法·催官符条',
        ],
        [
            'case_id' => 'bifa.04.daquan-patron-parent-line',
            'law_code' => 'bifa.04',
            'label' => '正文结构·恩主举荐·父母爻',
            'source_type' => 'daquan',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['patron_parent_line'],
            'reason' => '《六壬大全·毕法赋》第四法父母爻条：原文给出父母爻定义与六处检查结构，无完整公历课例，故仅作 reference_only。',
            'source' => '《六壬大全·毕法赋》第四法·父母爻条',
        ],
        [
            'case_id' => 'bifa.04.daquan-patron-noble-growth',
            'law_code' => 'bifa.04',
            'label' => '正文结构·恩主举荐·长生作贵人',
            'source_type' => 'daquan',
            'status' => 'reference_only',
            'datetime' => null,
            'birth' => null,
            'gender' => null,
            'people' => [],
            'routes' => ['patron_noble_as_growth'],
            'reason' => '《六壬大全·毕法赋》第四法长生作贵人条：原文给出典型示例，无完整公历课例，故仅作 reference_only。',
            'source' => '《六壬大全·毕法赋》第四法·长生作贵人条',
        ],
        // 乙卯日昼贵空、己卯日夜贵空两个「不用」特例只用于单元测试覆盖，
        // 不强行塞进 BiFaCaseCatalog 命中案例目录。
        [
            'case_id' => 'bifa.04.generated-cui-guan-messenger',
            'law_code' => 'bifa.04',
            'label' => '程序验证·壬戌日夜占·催官使者·官星戌乘白虎临日干寄宫亥',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-01-05T03:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['cui_guan_messenger'],
            'reason' => '生产排盘壬戌日夜占、官星戌（壬癸日辰戌丑未土）乘白虎（天将序号 7）加临日干寄宫亥；用于验证 Route 1 按普通五行官鬼表、不复用课经鬼墓课特殊日鬼表。',
            'source' => '程序验证案例·2000..2031 PanCalculator 扫描得到的现代生产复现',
        ],
        [
            'case_id' => 'bifa.04.generated-cui-guan-talisman',
            'law_code' => 'bifa.04',
            'label' => '程序验证·丁丑日·催官符·亥官临干+三传巳酉丑金局',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-01-20T09:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['cui_guan_talisman'],
            'reason' => '生产排盘丁丑日昼占、官星亥（丙丁日亥子水）加临日干寄宫未，三传巳酉丑组成金局、金局整体生亥水官星，符合《六壬粹言》「三传成局生官」释义。由 PanCalculator 扫描 2000..2031 得到的现代 datetime。',
            'source' => '程序验证案例·2000..2031 PanCalculator 扫描得到的现代生产复现',
        ],
        [
            'case_id' => 'bifa.04.generated-patron-parent-line',
            'law_code' => 'bifa.04',
            'label' => '程序验证·戊午日·父母爻巳午见于干支初传',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-01-01T01:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['patron_parent_line'],
            'reason' => '生产排盘戊午日、戊己日父母爻巳午（火）：干上神己、支上神午、初传巳三处父母爻同时出现。由 PanCalculator 扫描 2000..2031 得到的现代 datetime，用于覆盖 Route 3 多位置命中场景。',
            'source' => '程序验证案例·2000..2031 PanCalculator 扫描得到的现代生产复现',
        ],
        [
            'case_id' => 'bifa.04.generated-patron-noble-growth',
            'law_code' => 'bifa.04',
            'label' => '程序验证·己未日夜占·夜贵申=长生贵人',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-01-01T23:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['patron_noble_as_growth'],
            'reason' => '生产排盘己未日、夜占、当前所用天乙贵人恰为申（己日夜贵=申），己土六壬五行长生位亦为申，等于日干长生贵人，正是正文典型用例。由 PanCalculator 扫描 2000..2031 得到的现代 datetime。',
            'source' => '程序验证案例·2000..2031 PanCalculator 扫描得到的现代生产复现',
        ],
        [
            'case_id' => 'bifa.04.generated-multi-route',
            'law_code' => 'bifa.04',
            'label' => '程序验证·催官使者与父母爻同盘同时成立',
            'source_type' => 'generated',
            'status' => 'executable',
            'datetime' => '2000-01-05T03:00',
            'birth' => '1986-08-01T00:00',
            'gender' => 'male',
            'people' => [],
            'routes' => ['cui_guan_messenger', 'patron_parent_line'],
            'reason' => '同盘同时命中催官使者（官星戌乘白虎临日干寄宫亥）与父母爻（壬癸日父母爻申酉：支上神戌不入、中传酉入父母爻）；用于锁定多 route 同时命中时顺序稳定（按成立条件定义顺序）。',
            'source' => '程序验证案例·2000..2031 PanCalculator 扫描得到的现代生产复现',
        ],

        // ----------------------------------------------------------------------
        // 第五法 · 六阳数足须公用
        // ----------------------------------------------------------------------
        [
            'case_id' => 'bifa.05.daquan-geng-zi-six-yang', 'law_code' => 'bifa.05',
            'label' => '庚子日·六阳格', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['six_yang'],
            'reason' => '正文记庚子日六阳结构，但未给足可唯一回填现代公历的完整时将，故仅作 reference_only，不硬编 datetime。',
            'source' => '《六壬大全·毕法赋》第五法正文',
        ],
        [
            'case_id' => 'bifa.05.daquan-jia-wu-retreating', 'law_code' => 'bifa.05',
            'label' => '甲午日·六阳遇退间传·倒拔蛇·悖戾格', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['six_yang'],
            'reason' => '正文给甲午日干上子、三传戌申午；对应天地盘在生产排盘中因涉害取用传统差异取寅子戌，无法复现原传，故降级为 reference_only，不修改核心排盘。',
            'source' => '《六壬大全·毕法赋》第五法正文',
        ],
        [
            'case_id' => 'bifa.05.daquan-jia-xu-night-to-day', 'law_code' => 'bifa.05',
            'label' => '甲戌日·六阳·自夜传昼', 'source_type' => 'daquan', 'status' => 'reference_only',
            'datetime' => null, 'birth' => null, 'gender' => null, 'people' => [], 'routes' => ['six_yang'],
            'reason' => '正文记甲戌日六阳自夜传昼，但未给足可唯一回填现代公历的完整时将，故仅作 reference_only。',
            'source' => '《六壬大全·毕法赋》第五法正文',
        ],
        [
            'case_id' => 'bifa.05.generated-five-yang-filled', 'law_code' => 'bifa.05',
            'label' => '程序验证·五阳年命填实·兼自夜传昼', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-04T01:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['five_yang_filled_by_person'],
            'reason' => '生产排盘六位寅、寅、辰、辰、巳、申，恰五阳一阴；自动占人本命寅、行年戌均为阳支，年命填实。初传寅属夜地，末传申属昼方。',
            'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.05.generated-six-yang-retreating', 'law_code' => 'bifa.05',
            'label' => '程序验证·六阳格·悖戾格', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-04T05:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['six_yang'],
            'reason' => '生产排盘四课上神子、戌、寅、子，三传寅、子、戌；六位全阳，且三传连续逆退二位，同时验证六阳与悖戾格。',
            'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
        [
            'case_id' => 'bifa.05.generated-six-yang-multi-judgment', 'law_code' => 'bifa.05',
            'label' => '程序验证·六阳格·悖戾格·自夜传昼', 'source_type' => 'generated', 'status' => 'executable',
            'datetime' => '2031-01-14T05:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male', 'people' => [],
            'routes' => ['six_yang'],
            'reason' => '生产排盘四课上神子、戌、子、戌，三传戌、申、午；六位全阳，三传连续逆退二位，且由夜地戌传入昼方午。',
            'source' => '程序验证案例·2031 年生产 PanCalculator 扫描',
        ],
    ];
}
