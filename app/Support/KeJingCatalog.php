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
     *     cases: list<array{label: string, reason: string, datetime: string, birth: string, gender: string}>
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
                        '辛巳日·寅命',
                        '初传寅乘天乙贵人、得旺气，天盘寅临地盘巳（木生火），地盘巳兼为日支，四组条件齐备。',
                        '2025-01-10T08:00',
                    ),
                    self::case(
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
                        '正格·午卯子',
                        '三传恰为午、卯、子，初传午为胜光天马、中传卯为太冲天车、末传子为神后华盖。',
                        '2000-02-12T06:00',
                    ),
                    self::case(
                        '日用旺相',
                        '三传午卯子成立轩盖课；日干与发用午均得季节旺相（已核实课义）。',
                        '2000-02-09T06:00',
                    ),
                    self::case(
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
                        '丙子日·戌加巳',
                        '天魁戌与太乙巳同入三传，三传巳戌卯，为书中课例戌加巳。',
                        '1903-02-17T14:00',
                        '1800-01-01T00:00',
                    ),
                    self::case(
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
                        '卯加申（庚）',
                        '初传卯为车轮，天盘卯加临地盘申（庚金刀斧），卯加庚发用。',
                        '2000-02-13T09:00',
                    ),
                    self::case(
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
                        '拱天干（庚辰日）',
                        '庚寄申，申前一宫酉之上神寅发用作初传，后一宫未之上神子作末传，前后夹拱天干。',
                        '2000-01-23T13:00',
                    ),
                    self::case(
                        '拱地支（甲午日）',
                        '日支午前一宫未之上神子发用作初传，后一宫巳之上神戌作末传，前后夹拱地支。',
                        '2000-02-06T13:00',
                    ),
                    self::case(
                        '两贵引从（壬子日）',
                        '拱天干的同时，初传巳恰为壬日昼贵、末传卯恰为夜贵，为两贵引从。',
                        '2000-02-24T11:00',
                    ),
                    self::case(
                        '贵临干支拱年命（丁酉日）',
                        '夜贵酉加临丁干寄宫未，昼贵亥加临日支酉，干支前后夹拱年命申。',
                        '2000-02-09T19:00',
                        '1980-06-01T00:00',
                    ),
                    self::case(
                        '贵临干支拱年命·反向（丁巳日）',
                        '昼贵亥加临丁干寄宫未，夜贵酉加临日支巳，干支前后夹拱年命午（与丁酉方向相反）。',
                        '2000-02-29T13:00',
                        '1990-06-01T00:00',
                    ),
                    self::case(
                        '干支拱日禄（丁巳日伏吟）',
                        '伏吟盘，丁寄未与日支巳前后夹拱日禄午。',
                        '2000-06-28T13:00',
                    ),
                    self::case(
                        '干支拱夜贵（庚午日伏吟）',
                        '伏吟盘，庚寄申与日支午前后夹拱夜贵未。',
                        '2000-07-11T13:00',
                    ),
                    self::case(
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
                        '递生格（顺·丙申日）',
                        '三传申、亥、寅，初生中、中生末、末生日干丙，为递生格。',
                        '2000-04-08T13:00',
                    ),
                    self::case(
                        '递生格（逆·癸丑日）',
                        '三传酉、丑、巳，末生中、中生初、初生日干癸，为递生格。',
                        '2000-02-25T13:00',
                    ),
                    self::case(
                        '俱生格（丙寅日）',
                        '干上寅生丙、支上亥生寅，为俱生格。',
                        '2000-01-09T07:00',
                    ),
                    self::case(
                        '互生格（辛卯日）',
                        '干上亥生支卯、支上辰生干辛，为互生格。',
                        '2000-06-02T13:00',
                    ),
                    self::case(
                        '互旺格（甲申日）',
                        '干上酉为支申之旺神、支上卯为干甲之旺神，为互旺格。',
                        '2000-01-27T09:00',
                    ),
                    self::case(
                        '俱旺格（壬寅日）',
                        '干上子为干壬之旺神、支上卯为支寅之旺神，为俱旺格。',
                        '2000-06-13T13:00',
                    ),
                ],
            ],
        ];
    }

    /**
     * @return array{label: string, reason: string, datetime: string, birth: string, gender: string}
     */
    private static function case(
        string $label,
        string $reason,
        string $datetime,
        string $birth = self::DEFAULT_BIRTH,
        string $gender = self::DEFAULT_GENDER,
    ): array {
        return [
            'label' => $label,
            'reason' => $reason,
            'datetime' => $datetime,
            'birth' => $birth,
            'gender' => $gender,
        ];
    }
}
