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
            [
                'name' => '合欢课',
                'code' => 'lesson.he_huan',
                'gua' => '井',
                'guaSymbol' => '䷯',
                'summary' => '日干上神的寄宫天干与日干作天干五合，且初传与干上神作地支六合，且初传与日支及中传或末传构成完整地支三合，且占人本命宫上神与行年宫上神各乘六吉将；缺占人本命与行年信息时按尚未判断处理。',
                'cases' => [
                    self::case(
                        'lesson.he_huan.wu_shen_ri',
                        '戊申日·子时·申将（合欢课课例·部分复原）',
                        '《六壬大全》“戊申日子时申将”课例的现代复现（部分复原）：戊申日子时（零时）、月将申（节气对应芒种），三传子申辰，初传子与干上丑作子丑六合；日支申、初传子、末传辰作申子辰三合，初传明确参与“支三合发用”。干上丑在甲辰旬中寄宫癸，与日干戊作戊癸合。现代补充的出生资料为1959年6月21日12时、男性（己亥年生人）：本命为亥，亥宫上神为未、乘贵人；按项目现行行年口径推得行年为未，未宫上神为卯、乘太常；两者均乘六吉将。课例原文“行年在辰见子为青龙”与这份现代补充资料不能同时成立，故此案例仅能部分复原年命结构。',
                        '2000-06-19T00:00',
                        '1959-06-21T12:00',
                        'male',
                    ),
                ],
                'source_examples' => [
                    ['label' => '戊申日子时申将', 'detail' => '子与干上丑作六合，加辰发用；戊日干上丑遁得癸作合，支辰与三传合作三合；亥命见未为贵人，行年在辰见子为青龙。'],
                    ['label' => '丙申日反吟', 'detail' => '日辰阴阳上下神作三六合。'],
                    ['label' => '辛卯日', 'detail' => '卯加辛，干支相会作六合。'],
                    ['label' => '壬寅日', 'detail' => '亥加寅，干支相会作六合。'],
                    ['label' => '甲申日', 'detail' => '干上亥与甲合，支上巳与申合。'],
                    ['label' => '丁丑、己丑日', 'detail' => '干上午，干支上下作六合。'],
                    ['label' => '戊辰日', 'detail' => '干上丑（戊癸合），支上子（子丑六合）。'],
                    ['label' => '辛酉日', 'detail' => '干上午，干支上下交互作三六合。'],
                    ['label' => '乙酉日', 'detail' => '三传申子辰水局生日，支上丑作六合，并三合为全吉。'],
                ],
            ],
            [
                'name' => '德庆课',
                'code' => 'lesson.de_qing',
                'gua' => '需',
                'guaSymbol' => '䷄',
                'summary' => '四类德神（日干德、日支德、天德、月德）之一发用入传，且该发用德神本身乘六吉将（贵人、六合、青龙、太常、太阴、天后），且该发用德神加临占人本命宫（地盘本命支之上的上神）或行年宫（地盘行年支之上的上神）；本命与行年任一符合即可。',
                'cases' => [
                    self::case(
                        'lesson.de_qing.wu_zi_ri',
                        '戊子日戌时·卯将·巳加子发用（同盘式复现时间）',
                        '《六壬大全》“戊子日戌时卯将占”课例的现代复现：戊子日、亥月、戌时、卯将，三传巳戌卯，初传巳同时为戊日干德与子日支德。起课时间复原《大全》原盘式；现代补充的出生资料为1984年6月1日零时、男性（不是古籍原例所载人物）。此人为甲子年生，本命为子；地盘子宫上神为巳，恰与初传相同，初传巳乘太阴这一六吉将，故通过本命路径严格命中德庆课。',
                        '2001-11-21T19:00',
                        '1984-06-01T00:00',
                        'male',
                    ),
                ],
                'source_examples' => [
                    ['label' => '戊子日戊时', 'detail' => '巳为德神加子发用，为德庆课；干支双德同到巳火。'],
                    ['label' => '子日巳德归亥', 'detail' => '巳乘元武夹克为减德，事参商。'],
                    ['label' => '乙日申德加酉', 'detail' => '酉来克乙、申化鬼，君子为小人，四杀不没，应九三致寇至凶象。'],
                ],
            ],
            [
                'name' => '和美课',
                'code' => 'lesson.he_mei',
                'gua' => '丰',
                'guaSymbol' => '䷶',
                'summary' => '正文三路结构：日干、日支与彼此上神交互作六合，或分别与各自上神作六合，或干支上神与三传中的一传共同构成完整三合。《订讹》的扩大入口另存笔记，暂不参与主体判断。',
                'cases' => [
                    self::case(
                        'lesson.he_mei.ren_wu_si_shi_chou_jiang',
                        '壬午日巳时丑将（《大全》正文课例）',
                        '《六壬大全》正文课例的完整结构：日干壬寄亥，支上神寅，寅亥六合；日支午，干上神未，午未六合。两组交互六合同时成立。',
                        '2026-01-08T09:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '壬午日巳时丑将', 'detail' => '三传戌午寅为寅午戌火局；干上未与中传午作午未六合；传逢三合。'],
                    ['label' => '戊辰日', 'detail' => '干上丑、支上子，干支上神作子丑六合。'],
                    ['label' => '辛酉日', 'detail' => '干上未、支上午，干支上神作午未六合。'],
                    ['label' => '甲申日', 'detail' => '干上亥、支上巳；干上亥与日干寄宫作寅亥六合；支上巳与日支作巳申六合（干上与日干、支上与日支双双成立）。'],
                    ['label' => '辛卯日', 'detail' => '"卯加辛"：干上卯与日干寄宫作卯戌六合。'],
                    ['label' => '壬寅日', 'detail' => '"亥加寅"：支上亥与日支作寅亥六合。'],
                    ['label' => '乙丑日', 'detail' => '干上子，日支丑；干上子与日支作子丑六合。'],
                    ['label' => '丙寅日', 'detail' => '干上亥，日支寅；干上亥与日支作寅亥六合。'],
                    ['label' => '乙酉日', 'detail' => '三传申子辰会成水局，水能生乙木；支上丑作六合，并三合为全吉。'],
                    ['label' => '乙酉日', 'detail' => '伏吟类"日辰上下阴阳神作六合"。'],
                    ['label' => '丙申日', 'detail' => '伏吟类"日辰上下阴阳神作六合"。'],
                    ['label' => '丁丑、己丑日', 'detail' => '伏吟类"日辰上下阴阳神作六合"。'],
                    ['label' => '壬申日', 'detail' => '干上寅、支上亥，干支上神作六合，而地下壬申作害，为"外好里牙槎"（减损结构）。'],
                ],
            ],
            [
                'name' => '斩关课',
                'code' => 'lesson.zhan_guan',
                'gua' => '遁',
                'guaSymbol' => '䷠',
                'summary' => '天魁（戌）或天罡（辰）发用为初传，且该发用之神加临日干寄宫或日支。《六壬大全》卷八《课经集（二）·斩关课》（识典底本130节）给出“如甲寅日亥时未将占，戌加寅为用，曰斩关课”为唯一正文课例；本目录首选案例为1904年7月19日21时（甲寅日亥时、小暑后月将未、三传戌午寅、戌加寅为用），是在1900至2100年范围内检得的最早命中。甲寅日的甲干寄宫与日支同在寅位，所以“戌加寅”同时符合加临日干与加临日支，不能单凭此例判定两者的逻辑关系；当前主体按“日辰”的通常并列语义，取加临日干或日支任一成立。',
                'cases' => [
                    self::case(
                        'lesson.zhan_guan.jia_yin_hai_shi_wei_jiang',
                        '甲寅日·亥时·未将（识典底本唯一课例·现代复现）',
                        '《六壬大全》卷八《课经集（二）·斩关课》（识典底本130节）正文课例“如甲寅日亥时未将占，戌加寅为用，曰斩关课”的现代真实时间复现。在1900至2100年范围内检索甲寅日、亥时、月将未的组合，共命中105例；1904年7月19日21时为最早一例。其日柱甲寅、时支亥、月将未、初传戌，三传戌午寅，地盘寅宫上见戌，完整命中斩关课主体。因起课时间较早，另配一份更早的出生资料以满足排盘页校验；斩关课主体本身不依赖人物信息。',
                        '1904-07-19T21:00',
                        '1900-01-01T00:00',
                    ),
                    self::case(
                        'lesson.zhan_guan.yi_hai_zi_shi',
                        '乙亥日·子时（“加日干或加日支”分支测试例，非文献证据）',
                        '2026年1月1日1时，乙亥日子时：日干乙寄宫辰，地盘辰宫上神也是辰（伏吟）；初传辰即天罡，也正是干上神，构成“天罡加临日干寄宫”，命中斩关课主体。本案例仅用于确认“加临日干”这一分支能够独立命中，不是古籍文献证据，不能反过来证明这种解释是唯一正解。',
                        '2026-01-01T01:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '甲寅日亥时未将', 'source' => '《六壬大全》正文（识典底本 130 节）', 'detail' => '如甲寅日亥时未将占，戌加寅为用，曰斩关课。'],
                    ['label' => '神藏煞没（四大吉时）', 'source' => '《六壬大全》正文（识典底本 130 节）', 'detail' => '传遇寅卯未子乘天乙、青龙、阴、合吉将，及甲戊庚日丑贵登天门，辰罡塞鬼户，六神藏、四杀没，为四大吉时。'],
                    ['label' => '魁渡天门（反向描述）', 'source' => '《六壬大全》正文（识典底本 130 节）', 'detail' => '如官鬼作直符，罡塞鬼户寅也，魁度天门亥也，乘凶将，为魁罡作罗网。'],
                    ['label' => '传有虎阴申酉（斩关得断）', 'source' => '《袖中金》（识典底本 130 节附录）', 'detail' => '魁罡临日辰，传见虎阴申酉为斩关得断，逃者永不获矣。更带血支、血忌、羊刃吟呻三杀，必伤人而走。'],
                    ['label' => '凡见辰戌加日辰发用者', 'source' => '《观月经》（识典底本130节附录）', 'detail' => '凡见辰戌加日辰发用者，为斩关卦，必有逃走之应也。此句未明确“加日辰”是加临日干、日支任一即可，还是要求两者同时成立；后接“戊申正月占，酉时此卦攻”诗句，按正月亥将、酉时还原盘式，得干上未、支上戌、三传子寅辰。其初传为子而非辰戌，提示《观月》可能采用比正文更宽的异说，即只要求日干或日支上见辰戌，不要求辰戌发为初传；主体规则暂不并入此异说。'],
                ],
            ],
            [
                'name' => '闭口课',
                'code' => 'lesson.bikou',
                'gua' => '谦',
                'guaSymbol' => '䷎',
                'summary' => '旬尾加旬首发用，或旬首乘玄武发用，或旬首位上神乘玄武发用。',
                'cases' => [
                    self::case(
                        'lesson.bikou.jia_shen_mao_shi_zi_jiang',
                        '甲申日·卯时·子将（《大全》正文课例）',
                        '旬首申，旬尾巳；天盘巳加地盘申，初传巳，为旬尾加旬首发用。',
                        '1904-02-20T05:00',
                        '1900-01-01T00:00',
                    ),
                    self::case(
                        'lesson.bikou.jia_zi_chen_jia_zi_fa_yong',
                        '甲子日·辰加子发用（地盘旬首位上神乘玄武）',
                        '甲子旬，旬首为子；地盘子宫上神为辰，该宫乘玄武，初传为辰，命中第三路。',
                        '1920-01-07T17:00',
                        '1900-01-01T00:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '甲申日卯时子将', 'path' => '第一路·旬尾加旬首', 'source' => '《六壬大全》正文（详例，已有可排课例）', 'detail' => '巳为旬尾，加临旬首申并发用；现代复现时间为1904年2月20日5时。'],
                    ['label' => '丁酉日午加酉', 'path' => '第二路·旬首乘玄武', 'source' => '《六壬大全》正文（盘式简例）', 'detail' => '夜将，天盘旬首乘玄武发用；按项目现行月将口径，尚未在1920至2030年的全部时辰中找到对应的真实可排时间，故保留为旁证。'],
                    ['label' => '甲子日辰加子', 'path' => '第三路·地盘旬首位上神乘玄武', 'source' => '《六壬大全》正文（盘式简例）', 'detail' => '昼夜皆以地盘旬首位的上神乘玄武发用；现代复现时间为1920年1月7日17时。'],
                    ['label' => '乙未日卯时寅将', 'path' => '独立附格·一旬周遍', 'source' => '《六壬大全》一旬周遍格', 'detail' => '旬尾加干、旬首加支；本格独立成立，不要求闭口课命中。'],
                ],
            ],
            [
                'name' => '游子课',
                'code' => 'lesson.youzi',
                'gua' => '观',
                'guaSymbol' => '䷓',
                'summary' => '三传皆为辰戌丑未四季土神，且旬丁或月内天马发用。',
                'cases' => [
                    self::case(
                        'lesson.youzi.yi_si_san_yue_wu_shi',
                        '乙巳日·午时·酉将（《大全》正文课例）',
                        '三传未戌丑皆为四季土神；乙巳属甲辰旬，旬丁未发用；辰月月内天马为戌，位于中传。按项目严格口径由旬丁发用成立游子课。',
                        '2022-04-22T11:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '三月将乙巳日午时', 'path' => '旬丁发用', 'source' => '《六壬大全》正文（详例）', 'detail' => '三传未戌丑；乙巳属甲辰旬，旬丁未发用；三月天马戌居中传。'],
                ],
            ],
            [
                'name' => '三交课',
                'code' => 'lesson.sanjiao',
                'gua' => '姤',
                'guaSymbol' => '䷫',
                'summary' => '四仲日占，支辰阴阳及三传皆为四仲，且四课上神或三传所见仲神至少一处乘太阴或六合。',
                'cases' => [
                    self::case(
                        'lesson.sanjiao.wu_zi_wu_shi_you_jiang',
                        '戊子日·午时·酉将（《大全》正文标准课例）',
                        '戊子为四仲日；支阳卯、支阴午皆为四仲；三传卯午酉皆仲；四课上神与初传所见的卯乘太阴，三交俱备。',
                        '2026-05-14T11:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '戊子日午时酉将', 'source' => '《六壬大全》正文标准课例', 'detail' => '四课戊申、申亥、子卯、卯午；三传卯午酉；课传中的卯乘太阴，三交俱备。'],
                ],
            ],
            [
                'name' => '赘婿课',
                'code' => 'lesson.zhuixu',
                'gua' => '旅',
                'guaSymbol' => '䷷',
                'summary' => '日干克日支，且日支临日干并由日支发用，或日干临日支并由日干寄宫之神发用。',
                'cases' => [
                    self::case(
                        'lesson.zhuixu.jia_xu_mao_shi_hai_jiang',
                        '甲戌日·卯时·亥将（《大全》正文课例）',
                        '甲木克戌土；干上神为戌，即日支戌临日干；初传亦为戌，构成支临干并由日支发用。',
                        '2024-03-11T05:00',
                    ),
                    self::case(
                        'lesson.zhuixu.bing_shen_chen_shi_chou_jiang',
                        '丙申日·辰时·丑将（《大全》正文课例）',
                        '丙火克申金；丙寄宫巳，日支申上神为巳，即日干临日支；初传亦为巳，构成干临支并由日干寄宫之神发用。',
                        '2019-12-25T07:00',
                    ),
                ],
                'source_examples' => [
                    ['label' => '甲戌日卯时亥将', 'path' => '支临干发用', 'source' => '《六壬大全》正文（详例）', 'detail' => '甲木克戌土；戌临甲并发用，三传戌午寅。'],
                    ['label' => '丙申日辰时丑将', 'path' => '干临支发用', 'source' => '《六壬大全》正文（详例）', 'detail' => '丙火克申金；丙寄宫巳，巳临申并发用，三传巳寅亥。'],
                    ['label' => '癸巳日第九局', 'path' => 'reference·宽口径旁证', 'source' => '《御定六壬直指》', 'detail' => '巳为癸日财并临癸干，但巳居末传、并未发用，该书仍列“赘婿”课体。'],
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
