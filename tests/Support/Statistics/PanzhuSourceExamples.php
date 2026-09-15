<?php

/** 文件作用：在真实生产排盘中搜索《六壬大全》盘珠篇三个非主盘课例的现代可执行复现，不手工拼造 PanFacts。 */

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HuihuanRule;
use App\Domain\Pan\Rules\TianxinRule;
use App\Services\PanCalculator;

$startYear = (int) ($argv[1] ?? 1900);
$endYear = (int) ($argv[2] ?? 2100);
$progressEveryDays = max(1, (int) ($argv[3] ?? 500));
if ($endYear < $startYear) {
    [$startYear, $endYear] = [$endYear, $startYear];
}

$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$tianxin = new TianxinRule;
$huihuan = new HuihuanRule;
$found = [
    'jiazi_year_seventh_month_yisi_you_time_si_general' => [],
    'wuzi_day_zi_time_wei_general' => [],
    'xinhai_day_xu_you_shen_transmissions' => [],
];
$prefilterCounts = [
    'yisi_days' => 0,
    'wuzi_00_candidates' => 0,
    'wuzi_23_candidates' => 0,
    'xinhai_days' => 0,
];

$record = static function (array &$bucket, string $datetime, PanFacts $facts): void {
    if (count($bucket) >= 10) {
        return;
    }

    $bucket[] = [
        'datetime' => $datetime,
        'year' => [$facts->get('niangan'), $facts->get('nianzhi')],
        'month_branch' => $facts->get('yuezhi'),
        'day' => [$facts->get('rigan'), $facts->get('rizhi')],
        'hour_branch' => $facts->get('shizhi'),
        'month_general' => $facts->get('yuejiang'),
        'sike' => $facts->get('sike'),
        'transmissions' => [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')],
    ];
};

$sexagenaryIndex = static function (int $stem, int $branch): int {
    for ($index = 0; $index < 60; $index++) {
        if ($index % 10 === $stem && $index % 12 === $branch) {
            return $index;
        }
    }

    throw new LogicException("不存在干 {$stem} 支 {$branch} 对应的六十甲子日序。");
};

$start = new DateTimeImmutable("{$startYear}-01-01 00:00:00", $timezone);
$end = new DateTimeImmutable(($endYear + 1).'-01-01 00:00:00', $timezone);
$totalDays = (int) $start->diff($end)->format('%a');
$daysScanned = 0;
$panCalculations = 0;

$seedDatetime = $start->setTime(12, 0)->format('Y-m-d H:i:s');
$seedFacts = PanFacts::from($calculator->calculate($seedDatetime));
$seedDayIndex = $seedFacts->civilDaySexagenaryDayIndex();
$panCalculations++;
if (! is_int($seedDayIndex)) {
    fwrite(STDERR, "无法取得起始日公历六十甲子日序：{$seedDatetime}".PHP_EOL);
    exit(2);
}

$yiSiDayIndex = $sexagenaryIndex(1, 5);
$wuZiDayIndex = $sexagenaryIndex(4, 0);
$dingHaiDayIndex = $sexagenaryIndex(3, 11);
$xinHaiDayIndex = $sexagenaryIndex(7, 11);

$emitProgress = static function (
    DateTimeImmutable $date,
    int $daysScanned,
    int $totalDays,
    int $panCalculations,
    array $prefilterCounts,
    array $found,
): void {
    $matches = array_sum(array_map('count', $found));
    $percent = $totalDays > 0 ? ($daysScanned / $totalDays) * 100 : 100;

    fwrite(STDERR, sprintf(
        "[PanzhuSourceExamples] %s  %d/%d days (%.1f%%)  pans=%d  prefilter[yisi=%d, wuzi00=%d, wuzi23=%d, xinhai=%d]  matches=%d\n",
        $date->format('Y-m-d'),
        $daysScanned,
        $totalDays,
        $percent,
        $panCalculations,
        $prefilterCounts['yisi_days'],
        $prefilterCounts['wuzi_00_candidates'],
        $prefilterCounts['wuzi_23_candidates'],
        $prefilterCounts['xinhai_days'],
        $matches,
    ));
};

for ($date = $start, $offset = 0; $date < $end; $date = $date->modify('+1 day'), $offset++) {
    $daysScanned++;
    $dayIndex = ($seedDayIndex + $offset) % 60;

    // 六十甲子日序只作预筛；所有真正命中仍由候选时刻的生产 PanCalculator + 正式规则确认。
    if ($dayIndex === $yiSiDayIndex) {
        $prefilterCounts['yisi_days']++;

        // 正文天心格：甲子年、七月（申月）、乙巳日、酉时、巳将。
        $datetime = $date->setTime(17, 0)->format('Y-m-d H:i:s');
        $facts = PanFacts::from($calculator->calculate($datetime));
        $panCalculations++;
        if ($facts->get('niangan') === 0
            && $facts->get('nianzhi') === 0
            && $facts->get('yuezhi') === 8
            && $facts->get('rigan') === 1
            && $facts->get('rizhi') === 5
            && $facts->get('shizhi') === 9
            && $facts->get('yuejiang') === 5
            && $tianxin->match($facts) !== null) {
            $record($found['jiazi_year_seventh_month_yisi_you_time_si_general'], $datetime, $facts);
        }
    }

    // 正文回还格：戊子日、子时、未将。
    // 00:00 取戊子公历日；23:00 按项目晚子时换日口径，从前一丁亥公历日取候选。
    if ($dayIndex === $wuZiDayIndex) {
        $prefilterCounts['wuzi_00_candidates']++;
        $datetime = $date->setTime(0, 0)->format('Y-m-d H:i:s');
        $facts = PanFacts::from($calculator->calculate($datetime));
        $panCalculations++;
        if ($facts->get('rigan') === 4
            && $facts->get('rizhi') === 0
            && $facts->get('shizhi') === 0
            && $facts->get('yuejiang') === 7
            && $huihuan->match($facts) !== null) {
            $record($found['wuzi_day_zi_time_wei_general'], $datetime, $facts);
        }
    }

    if ($dayIndex === $dingHaiDayIndex) {
        $prefilterCounts['wuzi_23_candidates']++;
        $datetime = $date->setTime(23, 0)->format('Y-m-d H:i:s');
        $facts = PanFacts::from($calculator->calculate($datetime));
        $panCalculations++;
        if ($facts->get('rigan') === 4
            && $facts->get('rizhi') === 0
            && $facts->get('shizhi') === 0
            && $facts->get('yuejiang') === 7
            && $huihuan->match($facts) !== null) {
            $record($found['wuzi_day_zi_time_wei_general'], $datetime, $facts);
        }
    }

    // 正文另一回还格只给辛亥日与三传戌酉申。
    if ($dayIndex === $xinHaiDayIndex) {
        $prefilterCounts['xinhai_days']++;
        foreach ([0, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21] as $hour) {
            $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
            $facts = PanFacts::from($calculator->calculate($datetime));
            $panCalculations++;
            if ($facts->get('rigan') === 7
                && $facts->get('rizhi') === 11
                && [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')] === [10, 9, 8]
                && $huihuan->match($facts) !== null) {
                $record($found['xinhai_day_xu_you_shen_transmissions'], $datetime, $facts);
            }
        }
    }

    if ($daysScanned % $progressEveryDays === 0 || $daysScanned === $totalDays) {
        $emitProgress($date, $daysScanned, $totalDays, $panCalculations, $prefilterCounts, $found);
    }
}

echo json_encode([
    'range' => [$startYear, $endYear],
    'timezone' => 'Asia/Shanghai',
    'days_scanned' => $daysScanned,
    'pan_calculations' => $panCalculations,
    'prefilter_counts' => $prefilterCounts,
    'progress_every_days' => $progressEveryDays,
    'matches' => $found,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
