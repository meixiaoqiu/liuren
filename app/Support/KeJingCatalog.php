<?php

namespace App\Support;

/**
 * 文件作用：维护前台「课经」目录——每一课的成课条件、入选课例及其可点击的可执行链接。
 *
 * 每个 case 的 reason 为该课例被选入目录的「入选理由」，即它所满足的成课规则（判断规则）；
 * 对存在多条规则匹配路径的课（如引从课），每一条路径都应有一个课例覆盖。
 *
 * 课例来自 docs/课经/*.md 的「可执行测试课例」，并与 tests/Feature/FrontendPanTest.php
 * 中已验证的时间点保持一致。每个 case 的 datetime/birth/gender 组合必须满足
 * CreatePan 的校验（birth 不得晚于 datetime），否则排盘页会报错。
 */
final class KeJingCatalog
{
    private const DEFAULT_BIRTH = '1986-08-01T00:00';

    private const DEFAULT_GENDER = 'male';

    /**
     * @return list<array{
     *     name: string,
     *     code: string,
     *     gua: string,
     *     guaSymbol: string,
     *     summary: string,
     *     cases: list<array{label: string, reason: string, datetime: string, birth: string, gender: string, people: list<array{role: string, birth_datetime: string, gender: string}>, status: string}>
     * }>
     */
    public static function lessons(): array
    {
        return [
            [
                'name' => '三光课',
                'code' => 'lesson.sanguang',
                'gua' => '贲',
                'guaSymbol' => '䷕',
                'summary' => '日干、日支与发用均得季节旺相，日上神、辰上神与发用又均乘吉将。',
                'cases' => [
                    self::case(
                        'lesson.sanguang.example',
                        '三光课例',
                        '日干、日支与发用三处均得季节旺相，日上神、辰上神、发用三处又均乘吉将。',
                        '2000-02-18T11:00',
                    ),
                ],
            ],
            [
                'name' => '三阳课',
                'code' => 'lesson.sanyang',
                'gua' => '晋',
                'guaSymbol' => '䷢',
                'summary' => '贵人顺行，日干寄宫与日支均乘贵前五将，发用又得季节旺相。',
                'cases' => [
                    self::case(
                        'lesson.sanyang.yi_chou_you_shi_xu_jiang',
                        '乙丑日·酉时·戌将',
                        '贵人顺行，日干寄宫与日支均乘贵前五将，发用又得季节旺相。',
                        '2004-04-16T18:00',
                    ),
                ],
            ],
            [
                'name' => '三奇课',
                'code' => 'lesson.sanqi',
                'gua' => '豫',
                'guaSymbol' => '䷏',
                'summary' => '占日所在六甲旬的旬奇发用、入于中传或末传；日奇作为课内等级依据。',
                'cases' => [
                    self::case(
                        'lesson.sanqi.lian_zhu',
                        '三奇联珠·三传亥子丑',
                        '占日属甲申旬，旬奇子入于中传；三传亥子丑又为三奇联珠，且无日奇，验证有旬奇无日奇亦可用。',
                        '2000-05-27T14:00',
                    ),
                ],
            ],
            [
                'name' => '六仪课',
                'code' => 'lesson.liuyi',
                'gua' => '兑',
                'guaSymbol' => '䷹',
                'summary' => '占日所在六甲旬的旬首地支发用、入于中传或末传；支仪作为课内等级依据。',
                'cases' => [
                    self::case(
                        'lesson.liuyi.bing_chen_yin_shi_wei_jiang',
                        '丙辰日·寅时·未将',
                        '占日属甲寅旬，旬首寅为旬仪发用，辰日支仪亦为寅，旬仪、支仪并见。',
                        '2000-06-27T03:00',
                    ),
                ],
            ],
            [
                'name' => '时泰课',
                'code' => 'lesson.shitai',
                'gua' => '泰',
                'guaSymbol' => '䷊',
                'summary' => '用起太岁、月建，乘青龙、六合，又带财德之神。',
                'cases' => [
                    self::case(
                        'lesson.shitai.zi_nian_xu_yue_wu_yin_ri',
                        '子年戌月戊寅日',
                        '太岁子发用兼作日财，月建戌入末传，初传乘青龙、末传乘六合，日德巳入中传。',
                        '1900-11-01T20:00',
                        '1800-01-01T00:00',
                    ),
                ],
            ],
            [
                'name' => '龙德课',
                'code' => 'lesson.longde',
                'gua' => '萃',
                'guaSymbol' => '䷬',
                'summary' => '太岁、月将乘贵人发用。',
                'cases' => [
                    self::case(
                        'lesson.longde.gui_si_nian_gui_you_ri',
                        '癸巳年·癸酉日',
                        '太岁巳与月将巳同神，又乘天乙贵人发用，三传巳丑酉。',
                        '2013-09-04T18:00',
                    ),
                ],
            ],
            [
                'name' => '官爵课',
                'code' => 'lesson.guanjue',
                'gua' => '益',
                'guaSymbol' => '䷩',
                'summary' => '岁月年命驿马发用，天魁、太常入传。',
                'cases' => [
                    self::case(
                        'lesson.guanjue.wei_nian_er_yue_ding_hai_ri',
                        '未年二月丁亥日',
                        '太岁、月建、日支与本命亥的驿马均为巳发用，天魁戌入中传，三传见太常。',
                        '1986-09-28T03:00',
                    ),
                ],
            ],
            [
                'name' => '富贵课',
                'code' => 'lesson.fugui',
                'gua' => '大有',
                'guaSymbol' => '䷍',
                'summary' => '天乙乘旺相气，上下相生，更临日辰年命发用。',
                'cases' => [
                    self::case(
                        'lesson.fugui.xin_si_ri_yin_ming',
                        '辛巳日·寅命',
                        '初传寅乘天乙贵人、得旺气，天盘寅临地盘巳（木生火），地盘巳兼为日支，四组条件齐备。',
                        '2025-01-10T08:00',
                    ),
                    self::case(
                        'lesson.fugui.gui_ren_ruo_yu',
                        '贵人入狱·不以坐狱论',
                        '贵人临辰戌为入狱的减损结构；乙辛日不以坐狱论，最终仍按富贵课主体为吉。',
                        '2026-03-08T06:40',
                    ),
                ],
            ],
            [
                'name' => '轩盖课',
                'code' => 'lesson.xuangai',
                'gua' => '升',
                'guaSymbol' => '䷭',
                'summary' => '三传恰为午、卯、子，为轩盖课。',
                'cases' => [
                    self::case(
                        'lesson.xuangai.zheng_ge',
                        '正格·午卯子',
                        '三传恰为午、卯、子，初传午为胜光天马、中传卯为太冲天车、末传子为神后华盖。',
                        '2000-02-12T06:00',
                    ),
                    self::case(
                        'lesson.xuangai.ri_yong_wang_xiang',
                        '日用旺相',
                        '三传午卯子成立轩盖课；日干与发用午均得季节旺相（已核实课义）。',
                        '2000-02-09T06:00',
                    ),
                    self::case(
                        'lesson.xuangai.kong_wang',
                        '三传落空亡',
                        '三传午卯子成立轩盖课；三传有支落旬空（已核实课义）。',
                        '2001-02-15T06:00',
                    ),
                ],
            ],
            [
                'name' => '铸印课',
                'code' => 'lesson.zhuyin',
                'gua' => '鼎',
                'guaSymbol' => '䷱',
                'summary' => '天魁戌、太乙巳同入三传。',
                'cases' => [
                    self::case(
                        'lesson.zhuyin.bing_zi_ri',
                        '丙子日·戌加巳',
                        '天魁戌与太乙巳同入三传，三传巳戌卯，为书中课例戌加巳。',
                        '1903-02-17T14:00',
                        '1800-01-01T00:00',
                    ),
                    self::case(
                        'lesson.zhuyin.ding_hai_ri',
                        '丁亥日·戌加巳',
                        '天魁戌与太乙巳同入三传，三传巳戌卯，戌加巳。',
                        '2000-01-30T14:00',
                    ),
                ],
            ],
            [
                'name' => '斫轮课',
                'code' => 'lesson.zhuolun',
                'gua' => '颐',
                'guaSymbol' => '䷚',
                'summary' => '卯加庚或加辛发用。',
                'cases' => [
                    self::case(
                        'lesson.zhuolun.geng',
                        '卯加申（庚）',
                        '初传卯为车轮，天盘卯加临地盘申（庚金刀斧），卯加庚发用。',
                        '2000-02-13T09:00',
                    ),
                    self::case(
                        'lesson.zhuolun.xin',
                        '卯加酉（辛）',
                        '初传卯为车轮，天盘卯加临地盘酉（辛金刀斧），卯加辛发用。',
                        '2000-01-10T13:00',
                    ),
                ],
            ],
            [
                'name' => '引从课',
                'code' => 'lesson.yincong',
                'gua' => '涣',
                'guaSymbol' => '䷺',
                'summary' => '日辰干支前后上神发用为初末传，前后夹拱干支、两贵、年命、日禄或昼夜贵。',
                'cases' => [
                    self::case(
                        'lesson.yincong.gang_tian_gan',
                        '拱天干（庚辰日）',
                        '庚寄申，申前一宫酉之上神寅发用作初传，后一宫未之上神子作末传，前后夹拱天干。',
                        '2000-01-23T13:00',
                    ),
                    self::case(
                        'lesson.yincong.gang_di_zhi',
                        '拱地支（甲午日）',
                        '日支午前一宫未之上神子发用作初传，后一宫巳之上神戌作末传，前后夹拱地支。',
                        '2000-02-06T13:00',
                    ),
                    self::case(
                        'lesson.yincong.liang_gui',
                        '两贵引从（壬子日）',
                        '拱天干的同时，初传巳恰为壬日昼贵、末传卯恰为夜贵，为两贵引从。',
                        '2000-02-24T11:00',
                    ),
                    self::case(
                        'lesson.yincong.gui_lin_gan_zhi_gang_nian_ming',
                        '贵临干支拱年命（丁酉日）',
                        '夜贵酉加临丁干寄宫未，昼贵亥加临日支酉，干支前后夹拱年命申。',
                        '2000-02-09T19:00',
                        '1980-06-01T00:00',
                    ),
                    self::case(
                        'lesson.yincong.gui_lin_gan_zhi_gang_nian_ming_fan_xiang',
                        '贵临干支拱年命·反向（丁巳日）',
                        '昼贵亥加临丁干寄宫未，夜贵酉加临日支巳，干支前后夹拱年命午（与丁酉方向相反）。',
                        '2000-02-29T13:00',
                        '1990-06-01T00:00',
                    ),
                    self::case(
                        'lesson.yincong.gang_ri_lu',
                        '干支拱日禄（丁巳日伏吟）',
                        '伏吟盘，丁寄未与日支巳前后夹拱日禄午。',
                        '2000-06-28T13:00',
                    ),
                    self::case(
                        'lesson.yincong.gang_ye_gui',
                        '干支拱夜贵（庚午日伏吟）',
                        '伏吟盘，庚寄申与日支午前后夹拱夜贵未。',
                        '2000-07-11T13:00',
                    ),
                    self::case(
                        'lesson.yincong.gang_zhou_gui',
                        '干支拱昼贵（甲子日伏吟）',
                        '伏吟盘，甲寄寅与日支子前后夹拱昼贵丑。',
                        '2000-07-05T13:00',
                    ),
                ],
            ],
            [
                'name' => '亨通课',
                'code' => 'lesson.hengtong',
                'gua' => '渐',
                'guaSymbol' => '䷴',
                'summary' => '三传递生日干，或干支上下神互生俱生、互旺俱旺。',
                'cases' => [
                    self::case(
                        'lesson.hengtong.di_sheng_forward',
                        '递生格（顺·丙申日）',
                        '三传申、亥、寅，初生中、中生末、末生日干丙，为递生格。',
                        '2000-04-08T13:00',
                    ),
                    self::case(
                        'lesson.hengtong.di_sheng_backward',
                        '递生格（逆·癸丑日）',
                        '三传酉、丑、巳，末生中、中生初、初生日干癸，为递生格。',
                        '2000-02-25T13:00',
                    ),
                    self::case(
                        'lesson.hengtong.ju_sheng',
                        '俱生格（丙寅日）',
                        '干上寅生丙、支上亥生寅，为俱生格。',
                        '2000-01-09T07:00',
                    ),
                    self::case(
                        'lesson.hengtong.hu_sheng',
                        '互生格（辛卯日）',
                        '干上亥生支卯、支上辰生干辛，为互生格。',
                        '2000-06-02T13:00',
                    ),
                    self::case(
                        'lesson.hengtong.ju_wang',
                        '互旺格（甲申日）',
                        '干上酉为支申之旺神、支上卯为干甲之旺神，为互旺格。',
                        '2000-01-27T09:00',
                    ),
                    self::case(
                        'lesson.hengtong.hu_wang',
                        '俱旺格（壬寅日）',
                        '干上子为干壬之旺神、支上卯为支寅之旺神，为俱旺格。',
                        '2000-06-13T13:00',
                    ),
                ],
            ],
            [
                'name' => '繁昌课',
                'code' => 'lesson.fanchang',
                'gua' => '咸',
                'guaSymbol' => '䷞',
                'summary' => '繁昌课含德孕格、旺孕格两格：德孕格按《观月经》德孕段"行年天干五合 + 行年地支六合"实现，不读三传、不核本命五行；旺孕格按"行年地支三合且俱得季节旺相"实现。两格任一成立即为繁昌课。',
                'cases' => [
                    self::case(
                        'lesson.fanchang.de_yun',
                        '德孕格（甲己合·寅亥合）',
                        '壬申日未时巳将，月将巳，三传午辰寅，地盘寅上见天盘子、地盘亥上见天盘酉；夫行年甲寅、妻行年己亥，甲己天干五合、寅亥地支六合，为德孕格。',
                        '2000-09-11T13:00',
                        '1952-06-01T00:00',
                        'male',
                        [
                            ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'female'],
                        ],
                    ),
                    self::case(
                        'lesson.fanchang.wang_yun',
                        '旺孕格（夫寅妻午三合）',
                        '春占夫行年寅、妻行年午，寅午三合且俱得旺相，为旺孕格。',
                        '2000-04-15T13:00',
                        '1964-06-01T00:00',
                        'male',
                        [
                            ['role' => 'spouse', 'birth_datetime' => '1974-06-01T00:00', 'gender' => 'female'],
                        ],
                    ),
                ],
            ],
            [
                'name' => '荣华课',
                'code' => 'lesson.rong_hua',
                'gua' => '渐',
                'guaSymbol' => '䷴',
                'summary' => '禄、马、贵人齐见于干支年命上神或三传；三者之一旺相发用，传中相关神更乘吉将。',
                'cases' => [
                    self::case(
                        'lesson.rong_hua.bing_yin',
                        '丙寅日正文结构',
                        '干上申为寅日驿马，支上巳为丙日禄；申以相气发用，中传亥为昼贵，复现《六壬大全》正文结构。',
                        '2001-05-03T11:00',
                    ),
                    self::case(
                        'lesson.rong_hua.ren_shen',
                        '壬申日正文结构',
                        '干上寅马、支上亥禄；三传巳申亥，巳为昼贵并以旺相气发用，覆盖《大全》“壬申日干上寅之类亦然”。',
                        '2000-03-15T15:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '丙寅日', 'detail' => '干上申、支上巳，为干支禄马；申发用，中传贵人。'],
                    ['label' => '壬申日', 'detail' => '干上寅之类亦然；三传巳申亥，贵人巳可以旺相发用。'],
                    ['label' => '癸丑日', 'detail' => '巳加癸，日贵为财，大利求财。'],
                    ['label' => '丙寅日·财', 'detail' => '申加丙，日马为财，大利求财。'],
                    ['label' => '甲申日', 'detail' => '干上丑，为干支见昼夜贵人。'],
                    ['label' => '乙酉日', 'detail' => '子加申，为昼贵坐夜贵。'],
                    ['label' => '丁卯日', 'detail' => '夜酉加亥，为夜贵从昼贵。'],
                    ['label' => '甲戊庚日', 'detail' => '干上丑，为贵人临身。'],
                    ['label' => '乙辛日', 'detail' => '干见贵人临身辰戌上，非坐狱。'],
                    ['label' => '甲子日', 'detail' => '昼贵丑坐酉、夜贵未坐卯，为贵人蹉跎。'],
                    ['label' => '丁酉日', 'detail' => '干上酉、支上亥，四课皆昼夜贵人，为遍地贵人。'],
                    ['label' => '丙丁日', 'detail' => '亥加未、酉加巳，两贵坐受克方，为尖担两头脱。'],
                    ['label' => '丁丑日', 'detail' => '酉加未、亥加酉，两贵逢空。'],
                    ['label' => '六丁日', 'detail' => '亥加未，贵作日鬼临干；又论贵作六害。'],
                    ['label' => '丙申日卯时子将', 'detail' => '干上寅马、支上巳禄；三传巳寅亥，巳相气发用，末传亥贵；寅命与巳年再见贵、禄、马。'],
                    ['label' => '庚辰日亥加寅', 'detail' => '寅命占科举；命上月将官贵、行年上帘幕官，魁星并照、朱雀临身。'],
                ],
            ],
        ];
    }

    /**
     * 白名单查找：根据 case_id 在全部课例中定位案例。
     * 排盘页只能基于本方法的结果展示"原文参考盘"提示，不得直接信任查询参数中的 status 或文案。
     *
     * @return array{case: array{label: string, reason: string, datetime: string, birth: string, gender: string, people: list<array{role: string, birth_datetime: string, gender: string}>, status: string, case_id: string}, lesson: array{name: string, code: string, gua: string, guaSymbol: string, summary: string, cases: list<mixed>}}|null
     */
    public static function findCase(string $caseId): ?array
    {
        foreach (self::lessons() as $lesson) {
            foreach ($lesson['cases'] as $case) {
                if (($case['case_id'] ?? null) === $caseId) {
                    return ['case' => $case, 'lesson' => $lesson];
                }
            }
        }

        return null;
    }

    /**
     * 仅在案例确为 reference_only 时返回（白名单二次校验）。
     *
     * @return array{case: array<string, mixed>, lesson: array<string, mixed>}|null
     */
    public static function findReferenceCase(string $caseId): ?array
    {
        $found = self::findCase($caseId);

        if ($found === null) {
            return null;
        }

        if (($found['case']['status'] ?? 'executable') !== 'reference_only') {
            return null;
        }

        return $found;
    }

    /**
     * @param  list<array{role: string, birth_datetime: string, gender: string}>  $people
     * @param  'executable'|'reference_only'  $status
     * @return array{case_id: string, label: string, reason: string, datetime: string, birth: string, gender: string, people: list<array{role: string, birth_datetime: string, gender: string}>, status: string}
     */
    private static function case(
        string $caseId,
        string $label,
        string $reason,
        string $datetime,
        string $birth = self::DEFAULT_BIRTH,
        string $gender = self::DEFAULT_GENDER,
        array $people = [],
        string $status = 'executable',
    ): array {
        return [
            'case_id' => $caseId,
            'label' => $label,
            'reason' => $reason,
            'datetime' => $datetime,
            'birth' => $birth,
            'gender' => $gender,
            'people' => $people,
            'status' => $status,
        ];
    }
}
