<?php

namespace App\Services;

use App\Data\PanResult;
use com\tyme\culture\Element;
use com\tyme\solar\SolarTime;

class PanCalculator
{
    // 定义地支
    public static ?array $dizhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    // 定义天干
    public static ?array $tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸', '空', '空'];

    // 定义五行
    public static ?array $wuxing = ['木', '火', '土', '金', '水'];

    // 定义天干的五行
    public static ?array $wuxingTian = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4];

    // 定义地支的五行
    public static ?array $wuxingDi = [4, 2, 0, 0, 2, 1, 1, 2, 3, 3, 2, 4];

    // 定义天干的阴阳
    public static ?array $yinyangTian = [1, 0, 1, 0, 1, 0, 1, 0, 1, 0];

    // 定义地支的阴阳
    public static ?array $yinyangDi = [1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0];

    // 地支的刑
    public static ?array $xing = [3, 10, 5, 0, 4, 8, 6, 1, 2, 9, 7, 11];

    // 地支的相冲
    public static ?array $chong = [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5];

    // 定义月将
    public static ?array $yuejiang = [
        0 => '子/神后/大寒到雨水',
        1 => '丑/大吉/冬至到大寒',
        2 => '寅/功曹/小雪到冬至',
        3 => '卯/太冲/霜降到小雪',
        4 => '辰/天罡/秋分到霜降',
        5 => '巳/太乙/处暑到秋分',
        6 => '午/胜光/大暑到处暑',
        7 => '未/小吉/夏至到大暑',
        8 => '申/传送/小满到夏至',
        9 => '酉/从魁/谷雨到小满',
        10 => '戌/河魁/春分到谷雨',
        11 => '亥/登明/雨水到春分',
    ];

    // 定义天将
    public static ?array $tianjiang = ['贵人', '螣蛇', '朱雀', '六合', '勾陈', '青龙', '天空', '白虎', '太常', '玄武', '太阴', '天后'];

    // 定义九宗门名字
    public static ?array $jiuzongmen = ['未知', '元首', '重审', '比用', '比用知一', '涉害', '涉害见机', '涉害察微', '涉害缀瑕', '遥克蒿矢', '遥克弹射', '昴星虎视', '昴星冬蛇掩目', '别责', '八专', '八专独足', '伏吟不虞', '伏吟自任', '伏吟自信', '伏吟杜撰', '反吟无依', '反吟无亲'];

    // 定义六亲
    public static ?array $liuqin = [
        -2 => '子孙',
        -1 => '妻财',
        0 => '兄弟',
        1 => '官鬼',
        2 => '父母',
    ];

    // 定义每个节气对应的月将index
    public static ?array $jieqi2Yuejiang = [1, 1, 0, 0, 11, 11, 10, 10, 9, 9, 8, 8, 7, 7, 6, 6, 5, 5, 4, 4, 3, 3, 2, 2];

    // 定义十干寄宫
    public static ?array $jigong = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];

    // 定义六十甲子对应的干支
    public static ?array $jiazi2Ganzhi = [
        [0, 0],
        [1, 1],
        [2, 2],
        [3, 3],
        [4, 4],
        [5, 5],
        [6, 6],
        [7, 7],
        [8, 8],
        [9, 9],
        [0, 10],
        [1, 11], // 甲子 - 乙亥
        [2, 0],
        [3, 1],
        [4, 2],
        [5, 3],
        [6, 4],
        [7, 5],
        [8, 6],
        [9, 7],
        [0, 8],
        [1, 9],
        [2, 10],
        [3, 11], // 丙子 - 丁亥
        [4, 0],
        [5, 1],
        [6, 2],
        [7, 3],
        [8, 4],
        [9, 5],
        [0, 6],
        [1, 7],
        [2, 8],
        [3, 9],
        [4, 10],
        [5, 11], // 戊子 - 己亥
        [6, 0],
        [7, 1],
        [8, 2],
        [9, 3],
        [0, 4],
        [1, 5],
        [2, 6],
        [3, 7],
        [4, 8],
        [5, 9],
        [6, 10],
        [7, 11], // 庚子 - 辛亥
        [8, 0],
        [9, 1],
        [0, 2],
        [1, 3],
        [2, 4],
        [3, 5],
        [4, 6],
        [5, 7],
        [6, 8],
        [7, 9],
        [8, 10],
        [9, 11],  // 壬子 - 癸亥
    ];

    // 定义小时转时辰
    public static ?array $hour2Shichen = [0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10, 11, 11, 0];

    /**
     * 月将加时，得出天盘结果
     *
     *
     * @param  tinyint  $yuejiang  月将
     * @param  DateTime  $shichenTime  时间
     * @param  tinyint  $tiangan  天盘索引，第几个天盘
     * @return array
     */
    public static function yuejiangJiashi($yuejiang, $shichenTime, $tianpanIndex)
    {
        $shichen = self::time2Shichen($shichenTime);
        $yueJiangJiaShi = $yuejiang - $shichen;
        if ($yueJiangJiaShi < 0) {
            $yueJiangJiaShi += 12;
        }
        $return = $yueJiangJiaShi + $tianpanIndex;
        if ($return > 11) {
            $return -= 12;
        }

        return $return;
    }

    /**
     * 通过时间计算出时辰
     *
     *
     * @param  DateTime  $shichenTime  时间
     * @return array
     */
    public static function time2Shichen($shichenTime)
    {
        $hour = date('G', strtotime($shichenTime));
        $shichen = self::$hour2Shichen[$hour];

        return $shichen;
    }

    /**
     * 获取月将
     *
     *
     * @param  array  $datetime  一个包含了年月日时分秒六个元素的数组
     * @return array
     */
    public static function getYuejiang($datetime)
    {
        // 获取节气index
        $solar = SolarTime::fromYmdHms($datetime[0], $datetime[1], $datetime[2], $datetime[3], $datetime[4], $datetime[5]);
        $solarIndex = $solar->getTerm()->getIndex();

        // 通过节气算出月将
        return self::$jieqi2Yuejiang[$solarIndex];
    }

    /**
     * 把字符串类型的时间转换为一个包括年月日时分秒的数组
     *
     *
     * @param  DateTime  $datetime  时间
     * @return array
     */
    public static function time2array($datetime)
    {
        $time = strtotime($datetime);
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('d', $time);
        $h = date('H', $time);
        $i = date('i', $time);
        $s = date('s', $time);

        return [$y, $m, $d, $h, $i, $s];
    }

    /**
     * 获取两个五行的生克关系
     *
     *
     * @param  tinyint  $upIndex  上的五行index
     * @param  tinyint  $downIndex  下的五行index
     * @return array
     */
    public static function getShengke($upIndex, $downIndex)
    {
        $up = Element::fromIndex($upIndex);
        $down = Element::fromIndex($downIndex);

        $woSheng = $up->getReinforce();
        $woKe = $up->getRestrain();
        $shengWo = $up->getReinforced();
        $keWo = $up->getRestrained();
        $wuxingShengke = [0, '无']; // 生克状态 0：无生克，1：上克下，2：上生下，-1：下贼上，-2：下生上
        if ($woSheng == $down) {
            $wuxingShengke = [2, '上生下'];
        } elseif ($woKe == $down) {
            $wuxingShengke = [1, '上克下'];
        } elseif ($shengWo == $down) {
            $wuxingShengke = [-2, '下生上'];
        } elseif ($keWo == $down) {
            $wuxingShengke = [-1, '下贼上'];
        }

        return $wuxingShengke;
    }

    /**
     * 起盘
     *
     *
     * @param  DateTime  $datetime  起盘的时间
     * @return array
     */
    public function calculate($datetime): PanResult
    {

        // 第零步，初始化本次计算数据
        $pan = [];

        // 第一步，根据时间确定月将
        $time = self::time2array($datetime);
        $yuejiang = self::getYuejiang($time);
        $pan['yuejiang'] = $yuejiang;

        // 第二步，获取四柱
        $solarTime = SolarTime::fromYmdHms($time[0], $time[1], $time[2], $time[3], $time[4], $time[5]);
        $sizhu = $solarTime->getLunarHour();
        $sizhuYear = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getYear()->getIndex()];
        $sizhuMonth = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getMonth()->getIndex()];
        $sizhuDay = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getDay()->getIndex()];
        $sizhuHour = self::$jiazi2Ganzhi[$sizhu->getEightChar()->getHour()->getIndex()];
        $pan['niangan'] = $sizhuYear[0];
        $pan['nianzhi'] = $sizhuYear[1];
        $pan['yuegan'] = $sizhuMonth[0];
        $pan['yuezhi'] = $sizhuMonth[1];
        $pan['rigan'] = $sizhuDay[0];
        $pan['rizhi'] = $sizhuDay[1];
        $pan['shigan'] = $sizhuHour[0];
        $pan['shizhi'] = $sizhuHour[1];
        $pan['sizhu'] = self::$tiangan[$sizhuYear[0]].self::$dizhi[$sizhuYear[1]].' / '
            .self::$tiangan[$sizhuMonth[0]].self::$dizhi[$sizhuMonth[1]].' / '
            .self::$tiangan[$sizhuDay[0]].self::$dizhi[$sizhuDay[1]].' / '
            .self::$tiangan[$sizhuHour[0]].self::$dizhi[$sizhuHour[1]];

        // 第三步，设置天盘数据
        $pan['tianpan'] = [
            self::yuejiangJiashi($yuejiang, $datetime, 0),
            self::yuejiangJiashi($yuejiang, $datetime, 1),
            self::yuejiangJiashi($yuejiang, $datetime, 2),
            self::yuejiangJiashi($yuejiang, $datetime, 3),
            self::yuejiangJiashi($yuejiang, $datetime, 4),
            self::yuejiangJiashi($yuejiang, $datetime, 5),
            self::yuejiangJiashi($yuejiang, $datetime, 6),
            self::yuejiangJiashi($yuejiang, $datetime, 7),
            self::yuejiangJiashi($yuejiang, $datetime, 8),
            self::yuejiangJiashi($yuejiang, $datetime, 9),
            self::yuejiangJiashi($yuejiang, $datetime, 10),
            self::yuejiangJiashi($yuejiang, $datetime, 11),
        ];

        // 第四步，设置四课数据
        $pan['sike'] = [];
        $pan['sike'][] = $pan['rigan'];
        $pan['sike'][] = $pan['tianpan'][self::$jigong[$pan['rigan']]];
        $pan['sike'][] = $pan['tianpan'][self::$jigong[$pan['rigan']]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][2]];
        $pan['sike'][] = $pan['rizhi'];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][4]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][4]];
        $pan['sike'][] = $pan['tianpan'][$pan['sike'][6]];

        // 四课去重之后的数据（包含生克关系）
        $sikeUnique = [];
        if (! array_search($pan['sike'][1].self::$wuxingTian[$pan['sike'][0]], $sikeUnique)) {
            $sikeUnique[1] = $pan['sike'][1].self::$wuxingTian[$pan['sike'][0]];
        }
        if (! array_search($pan['sike'][3].self::$wuxingDi[$pan['sike'][2]], $sikeUnique)) {
            $sikeUnique[3] = $pan['sike'][3].self::$wuxingDi[$pan['sike'][2]];
        }
        if (! array_search($pan['sike'][5].self::$wuxingDi[$pan['sike'][4]], $sikeUnique)) {
            $sikeUnique[5] = $pan['sike'][5].self::$wuxingDi[$pan['sike'][4]];
        }
        if (! array_search($pan['sike'][7].self::$wuxingDi[$pan['sike'][6]], $sikeUnique)) {
            $sikeUnique[7] = $pan['sike'][7].self::$wuxingDi[$pan['sike'][6]];
        }

        // 四课的五行生克关系
        $jiuZongMen = 0; // 九宗门 未知
        $shangKeXiaIndex = [];  // 记录上克下的四课index
        $xiaZeiShangIndex = []; // 记录下贼上的四课index
        $pan['wuxingShengke0'] = self::getShengke(self::$wuxingDi[$pan['sike'][1]], self::$wuxingTian[$pan['sike'][0]]);
        if ($pan['wuxingShengke0'][0] == 1) { // 上克下
            $shangKeXiaIndex[] = 1;
        }
        if ($pan['wuxingShengke0'][0] == -1) { // 下贼上
            $xiaZeiShangIndex[] = 1;
        }
        $pan['wuxingShengke1'] = self::getShengke(self::$wuxingDi[$pan['sike'][3]], self::$wuxingDi[$pan['sike'][2]]);
        if ($pan['wuxingShengke1'][0] == 1 && isset($sikeUnique[3])) { // 上克下
            $shangKeXiaIndex[] = 3;
        }
        if ($pan['wuxingShengke1'][0] == -1 && isset($sikeUnique[3])) { // 下贼上
            $xiaZeiShangIndex[] = 3;
        }
        $pan['wuxingShengke2'] = self::getShengke(self::$wuxingDi[$pan['sike'][5]], self::$wuxingDi[$pan['sike'][4]]);
        if ($pan['wuxingShengke2'][0] == 1 && isset($sikeUnique[5])) { // 上克下
            $shangKeXiaIndex[] = 5;
        }
        if ($pan['wuxingShengke2'][0] == -1 && isset($sikeUnique[5])) { // 下贼上
            $xiaZeiShangIndex[] = 5;
        }
        $pan['wuxingShengke3'] = self::getShengke(self::$wuxingDi[$pan['sike'][7]], self::$wuxingDi[$pan['sike'][6]]);
        if ($pan['wuxingShengke3'][0] == 1 && isset($sikeUnique[7])) { // 上克下
            $shangKeXiaIndex[] = 7;
        }
        if ($pan['wuxingShengke3'][0] == -1 && isset($sikeUnique[7])) { // 下贼上
            $xiaZeiShangIndex[] = 7;
        }

        // 第五步，设置三传数据
        $pan['sanchuan0'] = 0;
        $pan['sanchuan1'] = 0;
        $pan['sanchuan2'] = 0;
        // 九宗门：重审或元首
        if (count($xiaZeiShangIndex) == 1 || (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 1)) {
            if (count($xiaZeiShangIndex) == 1) {
                $pan['sanchuan0'] = $pan['sike'][$xiaZeiShangIndex[0]];
                $jiuZongMen = 2; // 重审
            } else {
                $pan['sanchuan0'] = $pan['sike'][$shangKeXiaIndex[0]];
                $jiuZongMen = 1; // 元首
            }
        }
        // 九宗门：比用或知一
        $riganXiangbi = []; // 与日干相比的四课index
        $riganBubi = []; // 与日干不相比的四课index
        if (count($xiaZeiShangIndex) > 1) {
            $jiuZongMen = 3; // 比用
            $riganYinyang = self::$yinyangTian[$pan['rigan']]; // 日干阴阳
            foreach ($xiaZeiShangIndex as $v) {
                if (self::$yinyangDi[$pan['sike'][$v]] == $riganYinyang) {
                    $riganXiangbi[] = $v;
                } else {
                    $riganBubi[] = $v;
                }
            }
            if (count($riganXiangbi) == 1 || (count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] == $pan['sike'][$riganXiangbi[1]])) { // 四课有重复的，也按比用算
                $pan['sanchuan0'] = $pan['sike'][$riganXiangbi[0]];
            }
        }
        if (count($shangKeXiaIndex) > 1 && count($xiaZeiShangIndex) == 0) {
            $jiuZongMen = 4; // 知一
            $riganYinyang = self::$yinyangTian[$pan['rigan']]; // 日干阴阳
            foreach ($shangKeXiaIndex as $v) {
                if (self::$yinyangDi[$pan['sike'][$v]] == $riganYinyang) {
                    $riganXiangbi[] = $v;
                } else {
                    $riganBubi[] = $v;
                }
            }
            if (count($riganXiangbi) == 1 || (count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] == $pan['sike'][$riganXiangbi[1]])) { // 四课有重复的，也按比用算
                $pan['sanchuan0'] = $pan['sike'][$riganXiangbi[0]];
            }
        }
        // 九宗门：涉害
        $xianghai = []; // 相害数据，key=>四课index，value：相害数
        $pan['shehaiTrace'] = null;
        if ((count($xiaZeiShangIndex) > 1 || (count($shangKeXiaIndex) > 1 && count($xiaZeiShangIndex) == 0)) && ((count($riganXiangbi) > 1 && $pan['sike'][$riganXiangbi[0]] != $pan['sike'][$riganXiangbi[1]]) || count($riganXiangbi) == 0)) { // 与日干俱比或俱不比
            $jiuZongMen = 5; // 涉害
            if (count($riganXiangbi) > 1) { // 如果与日干俱不比，则使用俱不比的数组判断涉害数
                $biyongArr = $riganXiangbi;
            } else {
                $biyongArr = $riganBubi;
            }
            $pan['shehaiTrace'] = [
                'relation' => count($xiaZeiShangIndex) > 1 ? '下贼上' : '上克下',
                'candidates' => [],
                'decision' => null,
            ];
            // 轮询
            $sikeXia = []; // 用于和四孟四仲求交集的四课下课数组
            foreach ($biyongArr as $v) {
                if ($v - 1 == 0) { // 如果是天干位置，则应通过寄宫查地支
                    $lowerGround = self::$jigong[$pan['sike'][$v - 1]];
                } else {
                    $lowerGround = $pan['sike'][$v - 1];
                }
                $sikeXia[] = $lowerGround;
                $shehaiBegin = array_search($pan['sike'][$v], $pan['tianpan']);
                $shehaiEnd = $pan['sike'][$v];

                $shehaiArr = []; // 创建一个数组，是此一课中上方的值在地盘上的index，一直到这个值在地盘上的本位
                if ($shehaiBegin <= $shehaiEnd) {
                    $shehaiArr = range($shehaiBegin, $shehaiEnd);
                } else {
                    $shehaiArr = array_merge(range($shehaiBegin, 11), range(0, $shehaiEnd));
                }
                $xianghai[$v] = 0;
                $candidateTrace = [
                    'lesson' => intdiv($v + 1, 2),
                    'lesson_index' => $v,
                    'upper' => $pan['sike'][$v],
                    'lower_type' => $v === 1 ? 'stem' : 'branch',
                    'lower' => $pan['sike'][$v - 1],
                    'lower_ground' => $lowerGround,
                    'path' => $shehaiArr,
                    'encounters' => [],
                    'depth' => 0,
                ];
                foreach ($shehaiArr as $w) { // 涉害过程轮询
                    if (count($xiaZeiShangIndex) > 1 && self::getShengke(self::$wuxingDi[$w], self::$wuxingDi[$pan['sike'][$v]])[0] == 1) { // 看地盘位的值是否相害
                        $xianghai[$v]++; // 下贼上时，获取生克关系也应取下贼上
                        $candidateTrace['encounters'][] = [
                            'source_kind' => 'branch',
                            'source' => $w,
                            'ground' => $w,
                            'relation' => '贼',
                            'target_kind' => 'branch',
                            'target' => $pan['sike'][$v],
                        ];
                    } elseif (count($shangKeXiaIndex) > 1 && self::getShengke(self::$wuxingDi[$pan['sike'][$v]], self::$wuxingDi[$w])[0] == 1) { // 看地盘位的值是否相害
                        $xianghai[$v]++; // 上克下时，获取生克关系也应取上克下
                        $candidateTrace['encounters'][] = [
                            'source_kind' => 'branch',
                            'source' => $pan['sike'][$v],
                            'ground' => $w,
                            'relation' => '克',
                            'target_kind' => 'branch',
                            'target' => $w,
                        ];
                    }
                    // 看地盘所寄宫的天干的五行是否与这一课相害
                    $jigongTiangan = array_keys(self::$jigong, $w);
                    foreach ($jigongTiangan as $x) {
                        if (count($xiaZeiShangIndex) > 1 && self::getShengke(self::$wuxingTian[$x], self::$wuxingDi[$pan['sike'][$v]])[0] == 1) {
                            $xianghai[$v]++;
                            $candidateTrace['encounters'][] = [
                                'source_kind' => 'stem',
                                'source' => $x,
                                'ground' => $w,
                                'relation' => '贼',
                                'target_kind' => 'branch',
                                'target' => $pan['sike'][$v],
                            ];
                        } elseif (count($shangKeXiaIndex) > 1 && self::getShengke(self::$wuxingDi[$pan['sike'][$v]], self::$wuxingTian[$x])[0] == 1) {
                            $xianghai[$v]++;
                            $candidateTrace['encounters'][] = [
                                'source_kind' => 'branch',
                                'source' => $pan['sike'][$v],
                                'ground' => $w,
                                'relation' => '克',
                                'target_kind' => 'stem',
                                'target' => $x,
                            ];
                        }
                    }
                }
                $candidateTrace['depth'] = $xianghai[$v];
                $pan['shehaiTrace']['candidates'][] = $candidateTrace;
            }
            // 取涉害多者为初传
            $decisionRule = '取涉害较深者';
            $selectedLessonIndex = null;
            if (count($xianghai) > 0) {
                $shehaiMax = array_search(max($xianghai), $xianghai); // 涉害多者在四课中的index
                $selectedLessonIndex = $shehaiMax;
                $pan['sanchuan0'] = $pan['sike'][$shehaiMax];
            }
            if ((count($xianghai) > 1 || count($xianghai) == 0) && count(array_unique($xianghai)) == 1) { // 涉害数相等
                // 依次取孟仲季
                $meng = [2, 5, 8, 11]; // 四孟
                $zhong = [0, 3, 6, 9]; // 四仲
                $ifMeng = array_intersect($sikeXia, $meng);
                $ifZhong = array_intersect($sikeXia, $zhong);
                if (! empty($ifMeng)) {
                    $jiuZongMen = 6; // 涉害见机
                    $decisionRule = '涉害相等，取四孟';
                    $mengDipan = array_shift($ifMeng);
                    $selectedLessonIndex = $biyongArr[array_search($mengDipan, $sikeXia, true)];
                    $pan['sanchuan0'] = $pan['tianpan'][$mengDipan];
                } elseif (! empty($ifZhong)) {
                    $jiuZongMen = 7; // 涉害察微
                    $decisionRule = '涉害相等，取四仲';
                    $zhongDipan = array_shift($ifZhong);
                    $selectedLessonIndex = $biyongArr[array_search($zhongDipan, $sikeXia, true)];
                    $pan['sanchuan0'] = $pan['tianpan'][$zhongDipan];
                } else {
                    $jiuZongMen = 8; // 涉害缀瑕
                    $decisionRule = '涉害相等且不临孟仲，依日干阴阳取用';
                    if (self::$yinyangTian[$pan['rigan']] == 1) { // 阳日取干上神
                        $selectedLessonIndex = 1;
                        $pan['sanchuan0'] = $pan['sike'][1];
                    } else {                                    // 阴日取支上神
                        $selectedLessonIndex = 5;
                        $pan['sanchuan0'] = $pan['sike'][5];
                    }
                }
            }
            $pan['shehaiTrace']['decision'] = [
                'rule' => $decisionRule,
                'tied' => count($xianghai) > 1 && count(array_unique($xianghai)) === 1,
                'selected_lesson_index' => $selectedLessonIndex,
                'selected_branch' => $pan['sanchuan0'],
            ];
        }
        $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
        $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];

        if ($pan['tianpan'][0] == 6 && ! in_array($pan['rigan'].$pan['rizhi'], ['37', '57'])) {
            // 除丁未、己未，均为反吟
            $jiuZongMen = 20; // 反吟无依
            $pan['sanchuan1'] = self::$chong[$pan['sanchuan0']];
            $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
            if (($pan['rigan'] == 7 && $pan['rizhi'] == 7) || ($pan['rigan'] == 7 && $pan['rizhi'] == 1) || ($pan['rigan'] == 3 && $pan['rizhi'] == 1) || ($pan['rigan'] == 5 && $pan['rizhi'] == 1)) {
                // 辛未，辛丑，丁丑，己丑 无亲格
                $jiuZongMen = 21; // 反吟无亲
                if ($pan['rizhi'] == 1) {
                    $pan['sanchuan0'] = 11;
                } else {
                    $pan['sanchuan0'] = 5;
                }
                $pan['sanchuan1'] = $pan['sike'][5];
                $pan['sanchuan2'] = $pan['sike'][1];
            }
        } else {
            // 九宗门 遥克
            if (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 0 && ($pan['sike'][1] != $pan['sike'][5] || $pan['sike'][3] != $pan['sike'][7])) {
                $yaokeShangKeXia = [];
                $yaokeXiaZeiShang = [];
                foreach ($sikeUnique as $k => $v) { // 只对二三四课中去重后的数据做判断
                    if (self::getShengke(self::$wuxingDi[$pan['sike'][$k]], self::$wuxingTian[$pan['rigan']])[0] == 1) {
                        $yaokeShangKeXia[] = $k;
                    }
                    if (self::getShengke(self::$wuxingTian[$pan['rigan']], self::$wuxingDi[$pan['sike'][$k]])[0] == 1) {
                        $yaokeXiaZeiShang[] = $k;
                    }
                }
                if (count($yaokeShangKeXia) > 0) {
                    $jiuZongMen = 9; // 遥克蒿矢
                    if (count($yaokeShangKeXia) == 1) {
                        $pan['sanchuan0'] = $pan['sike'][$yaokeShangKeXia[0]];
                    } else {
                        foreach ($yaokeShangKeXia as $a) {
                            if (self::$yinyangTian[$pan['rigan']] == self::$yinyangDi[$pan['sike'][$a]]) {
                                $pan['sanchuan0'] = $pan['sike'][$a];
                                break;
                            }
                        }
                    }
                    $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
                    $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];
                } elseif (count($yaokeXiaZeiShang) > 0) {
                    $jiuZongMen = 10; // 遥克弹射
                    if (count($yaokeXiaZeiShang) == 1) {
                        $pan['sanchuan0'] = $pan['sike'][$yaokeXiaZeiShang[0]];
                    } else {
                        foreach ($yaokeXiaZeiShang as $a) {
                            if (self::$yinyangTian[$pan['rigan']] == self::$yinyangDi[$pan['sike'][$a]]) {
                                $pan['sanchuan0'] = $pan['sike'][$a];
                                break;
                            }
                        }
                    }
                    $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
                    $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];
                } elseif (count($yaokeShangKeXia) == 0 && count($yaokeXiaZeiShang) == 0) {
                    if ($pan['sike'][1] != $pan['sike'][7] && $pan['sike'][3] != $pan['sike'][5]) { // 四课不重复者为昴星
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $jiuZongMen = 11; // 昴星虎视
                            $pan['sanchuan0'] = $pan['tianpan'][9]; // 初传取地盘酉上神
                            $pan['sanchuan1'] = $pan['sike'][5];  // 中传取支上神
                            $pan['sanchuan2'] = $pan['sike'][1];  // 末传取干上神
                        } else {
                            $jiuZongMen = 12; // 昴星冬蛇掩目
                            $pan['sanchuan0'] = array_search(9, $pan['tianpan']); // 初传取天盘酉下神
                            $pan['sanchuan1'] = $pan['sike'][1];                // 中传取干上神
                            $pan['sanchuan2'] = $pan['sike'][5];                // 末传取支上神

                        }
                    } elseif ($pan['sike'][1] == $pan['sike'][7] || $pan['sike'][3] == $pan['sike'][5]) { // 四课中，1和7相等，或3和5相等，判断别责
                        $jiuZongMen = 13; // 别责
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $ganhe = $pan['rigan'] + 5; // 日干加5就是日干的天干五合
                            if ($ganhe > 9) {
                                $ganhe = $ganhe - 10;
                            }
                            $pan['sanchuan0'] = $pan['tianpan'][self::$jigong[$ganhe]];
                        } else {
                            $zhihe = $pan['rizhi'] + 4; // 日支加4就是日支的地支三合
                            if ($zhihe > 11) {
                                $zhihe = $zhihe - 12;
                            }
                            $pan['sanchuan0'] = $zhihe;
                        }
                        $pan['sanchuan1'] = $pan['sanchuan2'] = $pan['sike'][1];
                    }
                }
            } elseif (($pan['sike'][1] == $pan['sike'][5] && $pan['sike'][3] == $pan['sike'][7]) && $pan['tianpan'][0] != 0 && count($shangKeXiaIndex) == 0 && count($xiaZeiShangIndex) == 0) { // 只有两课且天地盘不重合且没有上下课为八专
                $jiuZongMen = 14; // 八专
                if (self::$yinyangTian[$pan['rigan']] == 1) {
                    $pan['sanchuan0'] = $pan['sike'][1] + 2;
                    if ($pan['sanchuan0'] > 11) {
                        $pan['sanchuan0'] = $pan['sanchuan0'] - 12;
                    }
                } else {
                    $pan['sanchuan0'] = $pan['sike'][7] - 2;
                    if ($pan['sanchuan0'] < 0) {
                        $pan['sanchuan0'] = $pan['sanchuan0'] + 12;
                    }
                }
                $pan['sanchuan1'] = $pan['sanchuan2'] = $pan['sike'][1];
                if ($pan['sanchuan0'] == $pan['sanchuan1'] && $pan['sanchuan1'] == $pan['sanchuan2']) {
                    $jiuZongMen = 15; // 八专独足
                }
            }
            if ($pan['tianpan'][0] == 0) {
                $jiuZongMen = 16; // 伏吟不虞
                if (count($xiaZeiShangIndex) > 0) {
                    $pan['sanchuan0'] = $pan['sike'][$xiaZeiShangIndex[0]];
                } elseif (count($shangKeXiaIndex) > 0) {
                    $pan['sanchuan0'] = $pan['sike'][$shangKeXiaIndex[0]];
                }
                $pan['sanchuan1'] = self::$xing[$pan['sanchuan0']];
                $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                if (in_array($pan['sanchuan0'], [4, 6, 9, 11])) { // 初传自刑
                    $pan['sanchuan1'] = $pan['sike'][5];
                    $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                    if (in_array($pan['sanchuan1'], [4, 6, 9, 11])) { // 中传自刑
                        $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
                    }
                }
                if (count($xiaZeiShangIndex) == 0 && count($shangKeXiaIndex) == 0) {
                    if (self::$yinyangTian[$pan['rigan']] == 1) {
                        $pan['sanchuan0'] = $pan['sike'][1];
                        $jiuZongMen = 17; // 伏吟自任
                    } else {
                        $pan['sanchuan0'] = $pan['sike'][5];
                        $jiuZongMen = 18; // 伏吟自信
                    }
                    if (in_array($pan['sanchuan0'], [4, 6, 9, 11])) { // 初传自刑
                        $jiuZongMen = 19; // 伏吟杜传
                        if (self::$yinyangTian[$pan['rigan']] == 1) {
                            $pan['sanchuan1'] = $pan['sike'][5];
                        } else {
                            $pan['sanchuan1'] = $pan['sike'][1];
                        }
                    } else {
                        $pan['sanchuan1'] = self::$xing[$pan['sanchuan0']];
                    }
                    if ($pan['sanchuan0'] === 3 && $pan['sanchuan1'] === 0) { // 子卯互刑不复再传，以子冲午为末传
                        $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
                    } elseif (in_array($pan['sanchuan1'], [4, 6, 9, 11])) { // 中传自刑
                        $pan['sanchuan2'] = self::$chong[$pan['sanchuan1']];
                    } else {
                        $pan['sanchuan2'] = self::$xing[$pan['sanchuan1']];
                    }
                }
            }
        }

        $pan['jiuzongmen'] = $jiuZongMen;

        $liuqin0 = self::getShengke(self::$wuxingDi[$pan['sanchuan0']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin0'] = $liuqin0[0];
        $liuqin1 = self::getShengke(self::$wuxingDi[$pan['sanchuan1']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin1'] = $liuqin1[0];
        $liuqin2 = self::getShengke(self::$wuxingDi[$pan['sanchuan2']], self::$wuxingTian[$pan['rigan']]);
        $pan['liuqin2'] = $liuqin2[0];

        // 计算旬遁空亡
        $xunIndex = intval($sizhu->getEightChar()->getDay()->getIndex() / 10); // 日柱的index除以10得知是甲x旬
        $xunFirstZhi = [0, 10, 8, 6, 4, 2]; // 甲子旬以子开头，甲戌旬以戌开头，申，午，辰，寅
        $pan['xundun0'] = $pan['sanchuan0'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan0'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan0'] - $xunFirstZhi[$xunIndex]; // 计算地支在本旬内排第几个
        $pan['xundun1'] = $pan['sanchuan1'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan1'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan1'] - $xunFirstZhi[$xunIndex];
        $pan['xundun2'] = $pan['sanchuan2'] - $xunFirstZhi[$xunIndex] < 0 ? $pan['sanchuan2'] - $xunFirstZhi[$xunIndex] + 12 : $pan['sanchuan2'] - $xunFirstZhi[$xunIndex];

        // 起贵人
        $tianjiang = [[1, 7], [0, 8], [11, 9], [11, 9], [1, 7], [0, 8], [1, 7], [6, 2], [5, 3], [5, 3]];
        $guiren = $tianjiang[$pan['rigan']][1]; // 夜贵
        if (in_array($pan['shizhi'], [3, 4, 5, 6, 7, 8])) { // 昼贵
            $guiren = $tianjiang[$pan['rigan']][0];
        }
        $dipanGuiren = array_search($guiren, $pan['tianpan']);
        $shunni = 1; // 贵人顺行
        if (in_array($dipanGuiren, [5, 6, 7, 8, 9, 10])) {
            $shunni = -1; // 贵人逆行
        }
        $pan['tianjiang'] = [];
        for ($i = 0; $i < 12; $i++) {
            if ($shunni == 1) {
                $pan['tianjiang'][$i] = $pan['tianpan'][0] - $guiren + $i;
            } else {
                $pan['tianjiang'][$i] = $guiren - $i - $pan['tianpan'][0];
            }
            if ($pan['tianjiang'][$i] < -12) {
                $pan['tianjiang'][$i] += 24;
            } elseif ($pan['tianjiang'][$i] < 0) {
                $pan['tianjiang'][$i] += 12;
            } elseif ($pan['tianjiang'][$i] > 11) {
                $pan['tianjiang'][$i] -= 12;
            }
        }

        // 三传的贵人
        $sanchuan0tianjiangIndex = array_search($pan['sanchuan0'], $pan['tianpan']);
        $pan['sanchuan0tianjiang'] = $pan['tianjiang'][$sanchuan0tianjiangIndex];
        $sanchuan1tianjiangIndex = array_search($pan['sanchuan1'], $pan['tianpan']);
        $pan['sanchuan1tianjiang'] = $pan['tianjiang'][$sanchuan1tianjiangIndex];
        $sanchuan2tianjiangIndex = array_search($pan['sanchuan2'], $pan['tianpan']);
        $pan['sanchuan2tianjiang'] = $pan['tianjiang'][$sanchuan2tianjiangIndex];

        return new PanResult($pan);
    }
}
