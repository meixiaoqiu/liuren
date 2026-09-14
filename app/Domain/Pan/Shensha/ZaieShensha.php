<?php

namespace App\Domain\Pan\Shensha;

use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全·灾厄课》主体定义，提供正文所需的 9 种神煞（丧车、游魂、伏殃、病符、丧门、吊客、三丘、五墓、岁虎）的统一算法。
 *
 * 规则边界：
 *  - 这里冻结的只是《六壬大全·灾厄课》正文所采用的同名神煞公式。
 *  - 不同章节或其他古籍对"丧车""游魂""伏殃"等可能给出异法，本类不自动合并异说。
 *  - 月份基准统一使用生产排盘的 yuezhi（month branch，寅=2 卯=3 ... 丑=1），不使用 yuejiang。
 *  - 岁神基准统一使用生产排盘的 nianzhi（year branch）。
 *  - 三丘五墓按 yuezhi 所属季节查表，不使用日干墓，也不使用 PanFacts::seasonalPeriod() 的土旺十八日。
 *  - 季节 → 三丘五墓 → seasonOf → qiuMuTable 必须共享同一份 SEASONS 数据源，禁
 *    止在多个位置分别手写四季对应表。
 *
 * 本类为只读函数集合；不会执行任何 I/O 也不会写 PanFacts。
 */
final class ZaieShensha
{
    /**
     * 季节 → (本季月建序列, 三丘, 五墓) 的单一数据源。
     *
     * 任何对"三丘五墓"的修改都必须只在此处进行；sanqiu / wumu / seasonOf /
     * qiuMuTable / monthlyTable 全部从此处推导。
     */
    private const SEASONS = [
        '春' => ['months' => [2, 3, 4], 'month_set' => '寅卯辰', 'sanqiu' => 1, 'wumu' => 7],
        '夏' => ['months' => [5, 6, 7], 'month_set' => '巳午未', 'sanqiu' => 4, 'wumu' => 10],
        '秋' => ['months' => [8, 9, 10], 'month_set' => '申酉戌', 'sanqiu' => 7, 'wumu' => 1],
        '冬' => ['months' => [11, 0, 1], 'month_set' => '亥子丑', 'sanqiu' => 10, 'wumu' => 4],
    ];

    /** 全部神煞中文名（含别名前缀）。用于 rules 与速查页共享。 */
    public const NAMES = [
        'sangche' => ['primary' => '丧车', 'aliases' => ['丧魂']],
        'youhun' => ['primary' => '游魂', 'aliases' => []],
        'fuyang' => ['primary' => '伏殃', 'aliases' => ['天鬼煞']],
        'bingfu' => ['primary' => '病符', 'aliases' => []],
        'sangmen' => ['primary' => '丧门', 'aliases' => []],
        'diaoke' => ['primary' => '吊客', 'aliases' => []],
        'sanqiu' => ['primary' => '三丘', 'aliases' => []],
        'wumu' => ['primary' => '五墓', 'aliases' => []],
        'suihu' => ['primary' => '岁虎', 'aliases' => []],
    ];

    /**
     * 返回 9 种神煞所在的地支。
     *
     * @return array{
     *     sangche: int, youhun: int, fuyang: int,
     *     bingfu: int, sangmen: int, diaoke: int,
     *     sanqiu: int, wumu: int, suihu: int,
     * }
     */
    public static function forPan(int $monthBranch, int $yearBranch): array
    {
        return [
            'sangche' => self::sangche($monthBranch),
            'youhun' => self::youhun($monthBranch),
            'fuyang' => self::fuyang($monthBranch),
            'bingfu' => self::bingfu($yearBranch),
            'sangmen' => self::sangmen($yearBranch),
            'diaoke' => self::diaoke($yearBranch),
            'sanqiu' => self::sanqiu($monthBranch),
            'wumu' => self::wumu($monthBranch),
            'suihu' => self::suihu($yearBranch),
        ];
    }

    /**
     * 丧车：正月起未，逆行四季。
     *
     * 索引周期：[未, 辰, 丑, 戌] = [7, 4, 1, 10]
     * 月份映射：yuezhi=2（寅）→ idx 0、yuezhi=3（卯）→ idx 1、yuezhi=4（辰）→ idx 2 …
     * 用 (yuezhi + 2) % 4 求出在周期数组中的下标。
     */
    public static function sangche(int $monthBranch): int
    {
        return [7, 4, 1, 10][($monthBranch + 2) % 4];
    }

    /**
     * 游魂：正月起亥，顺行十二辰。
     *
     * yuezhi=2（寅/正月）→ 亥（11）
     * yuezhi=3（卯/二月）→ 子（0）
     * …
     * 公式：(monthBranch + 9) % 12
     */
    public static function youhun(int $monthBranch): int
    {
        return ($monthBranch + 9) % 12;
    }

    /**
     * 伏殃：正月起酉，逆行四仲。
     *
     * 索引周期：[酉, 午, 卯, 子] = [9, 6, 3, 0]
     * 月份映射：yuezhi=2（寅）→ idx 0、yuezhi=3（卯）→ idx 1 …
     * 用 (yuezhi + 2) % 4 求出在周期数组中的下标。
     */
    public static function fuyang(int $monthBranch): int
    {
        return [9, 6, 3, 0][($monthBranch + 2) % 4];
    }

    /** 病符：旧太岁 = 岁后一辰。 */
    public static function bingfu(int $yearBranch): int
    {
        return ($yearBranch + 11) % 12;
    }

    /** 丧门：岁前二辰。 */
    public static function sangmen(int $yearBranch): int
    {
        return ($yearBranch + 2) % 12;
    }

    /** 吊客：岁后二辰。 */
    public static function diaoke(int $yearBranch): int
    {
        return ($yearBranch + 10) % 12;
    }

    /** 三丘：按 SEASONS 数据源中本季 sanqiu 给出。 */
    public static function sanqiu(int $monthBranch): int
    {
        return self::seasonEntry($monthBranch)['sanqiu'];
    }

    /** 五墓：按 SEASONS 数据源中本季 wumu 给出（与三丘互为冲位）。 */
    public static function wumu(int $monthBranch): int
    {
        return self::seasonEntry($monthBranch)['wumu'];
    }

    /** 岁虎：岁后四辰。 */
    public static function suihu(int $yearBranch): int
    {
        return ($yearBranch + 8) % 12;
    }

    /**
     * 给定初传地支，返回所有"落在初传上"的神煞 key 列表（保持 forPan 的 key 顺序）。
     *
     * @return list<string>
     */
    public static function matchInitial(array $shensha, int $initial): array
    {
        $hits = [];
        foreach (self::orderedKeys() as $key) {
            if (($shensha[$key] ?? null) === $initial) {
                $hits[] = $key;
            }
        }

        return $hits;
    }

    /** @return list<string> */
    public static function orderedKeys(): array
    {
        return ['sangche', 'youhun', 'fuyang', 'bingfu', 'sangmen', 'diaoke', 'sanqiu', 'wumu', 'suihu'];
    }

    /** @return list<array{key: string, primary: string, aliases: list<string>}> */
    public static function nameTable(): array
    {
        $rows = [];
        foreach (self::orderedKeys() as $key) {
            $rows[] = [
                'key' => $key,
                'primary' => self::NAMES[$key]['primary'],
                'aliases' => self::NAMES[$key]['aliases'],
            ];
        }

        return $rows;
    }

    /**
     * 给定键，返回主名 + 别名形式。
     */
    public static function displayName(string $key): string
    {
        $meta = self::NAMES[$key] ?? null;
        if ($meta === null) {
            return $key;
        }

        $aliases = $meta['aliases'] ?? [];
        if ($aliases === []) {
            return $meta['primary'];
        }

        return $meta['primary'].'（又名'.implode('、', $aliases).'）';
    }

    /**
     * 速查页所需的"灾厄课·月神"逐月表（按月建地支顺序）。
     *
     * @return list<array{month: int, month_name: string, sangche: string, youhun: string, fuyang: string, sanqiu: string, wumu: string, season: string}>
     */
    public static function monthlyTable(): array
    {
        $rows = [];
        foreach (self::monthBranchOrder() as $monthBranch) {
            $rows[] = [
                'month' => $monthBranch,
                'month_name' => PanCalculator::$dizhi[$monthBranch],
                'sangche' => PanCalculator::$dizhi[self::sangche($monthBranch)],
                'youhun' => PanCalculator::$dizhi[self::youhun($monthBranch)],
                'fuyang' => PanCalculator::$dizhi[self::fuyang($monthBranch)],
                'sanqiu' => PanCalculator::$dizhi[self::sanqiu($monthBranch)],
                'wumu' => PanCalculator::$dizhi[self::wumu($monthBranch)],
                'season' => self::seasonOf($monthBranch),
            ];
        }

        return $rows;
    }

    /**
     * 速查页所需的"灾厄课·岁神"逐年表（按太岁地支顺序）。
     *
     * @return list<array{year: int, year_name: string, bingfu: string, sangmen: string, diaoke: string, suihu: string}>
     */
    public static function yearlyTable(): array
    {
        $rows = [];
        for ($year = 0; $year < 12; $year++) {
            $rows[] = [
                'year' => $year,
                'year_name' => PanCalculator::$dizhi[$year],
                'bingfu' => PanCalculator::$dizhi[self::bingfu($year)],
                'sangmen' => PanCalculator::$dizhi[self::sangmen($year)],
                'diaoke' => PanCalculator::$dizhi[self::diaoke($year)],
                'suihu' => PanCalculator::$dizhi[self::suihu($year)],
            ];
        }

        return $rows;
    }

    /**
     * 速查页所需的"三丘五墓"季节表（从 SEASONS 单一数据源直接生成）。
     *
     * @return list<array{season: string, month_set: string, sanqiu: string, wumu: string}>
     */
    public static function qiuMuTable(): array
    {
        $rows = [];
        foreach (self::SEASONS as $season => $entry) {
            $rows[] = [
                'season' => $season,
                'month_set' => $entry['month_set'],
                'sanqiu' => PanCalculator::$dizhi[$entry['sanqiu']],
                'wumu' => PanCalculator::$dizhi[$entry['wumu']],
            ];
        }

        return $rows;
    }

    /** @return list<int> 按正月→十二月顺序的月建地支序列：[2,3,4,5,6,7,8,9,10,11,0,1] */
    public static function monthBranchOrder(): array
    {
        return [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1];
    }

    public static function seasonOf(int $monthBranch): string
    {
        return self::seasonEntry($monthBranch)['_season_name'] ?? array_key_first(self::SEASONS);
    }

    /**
     * 给定月建地支，返回所在季节的完整 SEASONS 记录（包含 _season_name）。
     *
     * @return array{months: list<int>, month_set: string, sanqiu: int, wumu: int, _season_name: string}
     */
    private static function seasonEntry(int $monthBranch): array
    {
        foreach (self::SEASONS as $season => $entry) {
            if (in_array($monthBranch, $entry['months'], true)) {
                return $entry + ['_season_name' => $season];
            }
        }

        // 不会到达：SEASONS 已覆盖 12 个月。
        return self::SEASONS['冬'] + ['_season_name' => '冬'];
    }
}
