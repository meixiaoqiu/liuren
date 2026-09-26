<?php

namespace App\Support;

/**
 * 文件作用：维护《毕法赋》"百法"的稳定编号目录。
 *
 *  - number :int                古籍法号，从 01 到 100 连续；
 *  - name   :string             法名（古籍通行本用名）；
 *  - code   :string             程序唯一编码，固定为 `bifa.NN`，与法号一致；
 *  - slug   :string             URL slug，全小写连字符，仅含 [a-z0-9-]，全局唯一；
 *  - summary:string             现代汉语一句简介，未研究法保持空串。
 *
 * 本目录与课经 `KeJingCatalog` 完全独立——
 *
 *  - 课经以《六壬大全·课经集》六十四课为单位；
 *  - 毕法以《六壬大全·毕法赋》百法为单位；
 *  - 即使同一古籍盘同时属两者（例如庚辰日），也由两套独立目录分别登记，
 *    各自的 case_id、summary、研究文档互不交叉。
 *
 * 当前目录按通行本《六壬大全·毕法赋》100 法顺序整理，目录经 `tests/Feature/BiFaPageTest.php`
 * 中独立的 `EXPECTED_LAWS` 表逐项固定断言；任何改名必须先更新 EXPECTED_LAWS，
 * 不得悄悄维护。
 *
 * 后续每法由各自独立研究文档 (`docs/毕法/NN-法名.md`) 补齐。未研究法的 summary
 * 保持空字符串，前台展示为"尚未研究"。
 *
 * @see docs/毕法/01-前后引从升迁吉.md  第一法研究文档
 */
final class BiFaCatalog
{
    /**
     * @return list<array{
        number: int,
        name: string,
        code: string,
        slug: string,
        summary: string
     * }>
     */
    public static function laws(): array
    {
        static $laws = null;
        if ($laws === null) {
            $laws = self::buildLaws();
        }

        return $laws;
    }

    /**
     * @return list<array{number: int, name: string, code: string, slug: string, summary: string}>
     */
    private static function buildLaws(): array
    {
        return [
            self::law(1, '前后引从升迁吉', 'qian-hou-yin-cong', '初末传分临日干（或日支）前后宫，前引后从，主迁官进职、修宅迁居。'),
            self::law(2, '首尾相见始终宜', 'shou-wei-xiang-jian', '旬首旬尾加临干支，或四建尽入四课，或三传尽入四课，主事绪前后相续、吉凶易成。'),
            self::law(3, '帘幕贵人高甲第', 'lian-mu-gui-ren', '昼夜反取帘幕贵人，兼察旬首、斗鬼、亚魁、德入天门、真朱雀与二贵拱年命，主科名之象。'),
            self::law(4, '催官使者赴官期', 'cui-guan-shi-zhe', '官星乘白虎临干年命为催官使者，或得催官符、父母爻、长生贵人，主赴任催促与恩主举荐之象。'),
            self::law(5, '六阳数足须公用', 'liu-yang-shu-zu', '四课四上神与中末传六位全阳，或五阳一阴而本命、行年得阳填实，主公用明白、利公不利私。'),
            self::law(6, '六阴相继尽昏迷', 'liu-yin-xiang-ji', '四课四上神与中末传六位全阴，或五阴得阴支年命填实，或四课与三传逐层相生，主昏迷与脱耗之象。'),
            self::law(7, '旺禄临身徒妄作', 'wang-lu-lin-shen', '阴干日禄临干，原则上宜守现有之禄；禄空、闭口或被玄武夺则不可拘守，乘白虎则减力而须兼察制化。'),
            self::law(8, '权摄不正禄临支', 'quan-she-bu-zheng', '日干之禄正临日支之上，自身禄利寄于支方；若支辰又墓、克或泄耗禄神，则禄气进一步受损。'),
            self::law(9, '避难逃生须弃旧', 'bi-nan-tao-sheng', '课传无可取之处时，舍弃旧路而转就干上、支上、地盘之生，或本命丁神长生、日干下临财乡；并辨逃生失败、舍益就损、舍就皆不可及墓作太阳等变格。'),
            self::law(10, '朽木难雕别作为', 'xiu-mu-nan-diao', '斫轮发用而卯木本身旬空，为朽木难雕，原文主宜改业别谋；若卯不空而所临申酉刀斧之地旬空，则另属斧斤不利。'),
            self::law(11, '虎临干鬼凶无比', 'hu-lin-gang-gui', ''),
            self::law(12, '蛇鬼乘墓终不吉', 'she-gui-cheng-mu', ''),
            self::law(13, '伏吟卦体定幽明', 'fu-yin-gua-ti', ''),
            self::law(14, '反吟卦体事须分', 'fan-yin-gua-ti', ''),
            self::law(15, '三光并起立名声', 'san-guang-bing-qi', ''),
            self::law(16, '三阳发用自荣昌', 'san-yang-fa-yong', ''),
            self::law(17, '旺相气发用须急进', 'wang-xiang-fa-yong', ''),
            self::law(18, '衰囚气发用退宜深', 'shuai-qiu-fa-yong', ''),
            self::law(19, '进神传课宜进达', 'jin-shen-chuan-ke', ''),
            self::law(20, '退神传课宜退藏', 'tui-shen-chuan-ke', ''),
            self::law(21, '天乙乘旺临干支', 'tian-yi-cheng-wang', ''),
            self::law(22, '天乙乘墓临干支', 'tian-yi-cheng-mu', ''),
            self::law(23, '天乙临支发用', 'tian-yi-lin-zhi', ''),
            self::law(24, '天乙临干发用', 'tian-yi-lin-gan', ''),
            self::law(25, '天乙乘蛇雀克干', 'tian-yi-ke-gan', ''),
            self::law(26, '天乙乘虎阴克支', 'tian-yi-ke-zhi', ''),
            self::law(27, '日辰上见天乙', 'ri-chen-shang-tian', ''),
            self::law(28, '天乙乘墓支干', 'tian-yi-cheng-mu-zhigan', ''),
            self::law(29, '日辰天乙俱乘旺', 'ri-chen-tian-yi-wang', ''),
            self::law(30, '天乙同会干支', 'tian-yi-tong-hui', ''),
            self::law(31, '天乙顺行终吉', 'tian-yi-shun-xing', ''),
            self::law(32, '天乙逆行终凶', 'tian-yi-ni-xing', ''),
            self::law(33, '课传三阳终吉', 'san-yang-zhong-ji', ''),
            self::law(34, '课传三阴终凶', 'san-yin-zhong-xiong', ''),
            self::law(35, '三阳课格宜进身', 'san-yang-ke-ge', ''),
            self::law(36, '三阴课格宜退步', 'san-yin-ke-ge', ''),
            self::law(37, '阳将阳日阳方吉', 'yang-jiang-yang-ri', ''),
            self::law(38, '阴将阴日阴方凶', 'yin-jiang-yin-ri', ''),
            self::law(39, '日辰旺相临用', 'ri-chen-wang-xiang', ''),
            self::law(40, '日辰休囚临用', 'ri-chen-xiu-qiu', ''),
            self::law(41, '日辰上见勾陈', 'ri-chen-gou-chen', ''),
            self::law(42, '日辰上见玄武', 'ri-chen-xuan-wu', ''),
            self::law(43, '日辰上见青龙', 'ri-chen-qing-long', ''),
            self::law(44, '日辰上见白虎', 'ri-chen-bai-hu', ''),
            self::law(45, '日辰上见太常', 'ri-chen-tai-chang', ''),
            self::law(46, '日辰上见六合', 'ri-chen-liu-he', ''),
            self::law(47, '日辰上见朱雀', 'ri-chen-zhu-que', ''),
            self::law(48, '日辰上见腾蛇', 'ri-chen-teng-she', ''),
            self::law(49, '日辰上见天空', 'ri-chen-tian-kong', ''),
            self::law(50, '日辰上见天乙', 'ri-chen-tian-yi', ''),
            self::law(51, '三传俱见天乙', 'san-chuan-ju-tian-yi', ''),
            self::law(52, '三传俱见日鬼', 'san-chuan-ju-ri-gui', ''),
            self::law(53, '三传生旺终吉', 'san-chuan-sheng-wang', ''),
            self::law(54, '三传墓绝终凶', 'san-chuan-mu-jue', ''),
            self::law(55, '初末传终吉', 'chu-mo-chuan', ''),
            self::law(56, '初中传终吉', 'chu-zhong-chuan', ''),
            self::law(57, '中末传终吉', 'zhong-mo-chuan', ''),
            self::law(58, '初末墓绝终凶', 'chu-mo-mu-jue', ''),
            self::law(59, '初中墓绝终凶', 'chu-zhong-mu-jue', ''),
            self::law(60, '中末墓绝终凶', 'zhong-mo-mu-jue', ''),
            self::law(61, '初传旺相终吉', 'chu-wang-xiang', ''),
            self::law(62, '初传休囚终凶', 'chu-xiu-qiu', ''),
            self::law(63, '末传旺相终吉', 'mo-wang-xiang', ''),
            self::law(64, '末传休囚终凶', 'mo-xiu-qiu', ''),
            self::law(65, '中传旺相终吉', 'zhong-wang-xiang', ''),
            self::law(66, '中传休囚终凶', 'zhong-xiu-qiu', ''),
            self::law(67, '初传生日终吉', 'chu-sheng-ri', ''),
            self::law(68, '末传生日终吉', 'mo-sheng-ri', ''),
            self::law(69, '初传克日终凶', 'chu-ke-ri', ''),
            self::law(70, '末传克日终凶', 'mo-ke-ri', ''),
            self::law(71, '初传比和终吉', 'chu-bi-he', ''),
            self::law(72, '初传墓日终凶', 'chu-mu-ri', ''),
            self::law(73, '末传墓日终凶', 'mo-mu-ri', ''),
            self::law(74, '初传绝日终凶', 'chu-jue-ri', ''),
            self::law(75, '末传绝日终凶', 'mo-jue-ri', ''),
            self::law(76, '初传空亡终凶', 'chu-kong-wang', ''),
            self::law(77, '末传空亡终凶', 'mo-kong-wang', ''),
            self::law(78, '初传旬空终凶', 'chu-xun-kong', ''),
            self::law(79, '末传旬空终凶', 'mo-xun-kong', ''),
            self::law(80, '初传伏吟终凶', 'chu-fu-yin', ''),
            self::law(81, '末传伏吟终凶', 'mo-fu-yin', ''),
            self::law(82, '初传反吟终凶', 'chu-fan-yin', ''),
            self::law(83, '末传反吟终凶', 'mo-fan-yin', ''),
            self::law(84, '初传六害终凶', 'chu-liu-hai', ''),
            self::law(85, '末传六害终凶', 'mo-liu-hai', ''),
            self::law(86, '初传三刑终凶', 'chu-san-xing', ''),
            self::law(87, '末传三刑终凶', 'mo-san-xing', ''),
            self::law(88, '初传六破终凶', 'chu-liu-po', ''),
            self::law(89, '末传六破终凶', 'mo-liu-po', ''),
            self::law(90, '初传刑冲终凶', 'chu-xing-chong', ''),
            self::law(91, '末传刑冲终凶', 'mo-xing-chong', ''),
            self::law(92, '初传见贵终吉', 'chu-jian-gui', ''),
            self::law(93, '末传见贵终吉', 'mo-jian-gui', ''),
            self::law(94, '初传见禄终吉', 'chu-jian-lu', ''),
            self::law(95, '末传见禄终吉', 'mo-jian-lu', ''),
            self::law(96, '初传见马终吉', 'chu-jian-ma', ''),
            self::law(97, '末传见马终吉', 'mo-jian-ma', ''),
            self::law(98, '初传见财终吉', 'chu-jian-cai', ''),
            self::law(99, '末传见财终吉', 'mo-jian-cai', ''),
            self::law(100, '初末传相生终吉', 'chu-mo-sheng', ''),
        ];
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}
     */
    private static function law(int $number, string $name, string $slug, string $summary): array
    {
        return [
            'number' => $number,
            'name' => $name,
            'code' => self::codeFor($number),
            'slug' => $slug,
            'summary' => $summary,
        ];
    }

    public static function codeFor(int $number): string
    {
        if ($number < 1 || $number > 100) {
            throw new \InvalidArgumentException('BiFaCatalog 法号必须介于 1..100。');
        }

        return 'bifa.'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}|null
     */
    public static function findBySlug(string $slug): ?array
    {
        foreach (self::laws() as $law) {
            if ($law['slug'] === $slug) {
                return $law;
            }
        }

        return null;
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        foreach (self::laws() as $law) {
            if ($law['code'] === $code) {
                return $law;
            }
        }

        return null;
    }

    /**
     * @return array{number: int, name: string, code: string, slug: string, summary: string}|null
     */
    public static function findByNumber(int $number): ?array
    {
        foreach (self::laws() as $law) {
            if ($law['number'] === $number) {
                return $law;
            }
        }

        return null;
    }
}
