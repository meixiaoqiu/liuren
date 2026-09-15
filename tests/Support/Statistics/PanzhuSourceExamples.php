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

$start = new DateTimeImmutable("{$startYear}-01-01 00:00:00", $timezone);
$end = new DateTimeImmutable(($endYear + 1).'-01-01 00:00:00', $timezone);
$daysScanned = 0;
$panCalculations = 0;

for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
    $daysScanned++;
    $noon = $date->setTime(12, 0)->format('Y-m-d H:i:s');
    $noonFacts = PanFacts::from($calculator->calculate($noon));
    $panCalculations++;

    $stem = $noonFacts->get('rigan');
    $branch = $noonFacts->get('rizhi');

    // 正文天心格：甲子年、七月（申月）、乙巳日、酉时、巳将。
    // 中午只作低成本预筛；最终必须以 17:00 候选盘自身 facts 重新核验全部条件。
    if ($noonFacts->get('niangan') === 0
        && $noonFacts->get('nianzhi') === 0
        && $noonFacts->get('yuezhi') === 8
        && $stem === 1
        && $branch === 5) {
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
    // 00:00 属当前公历日；23:00 的日柱按项目/tyme4php口径已进入次日，
    // 所以“戊子日晚子时”应从前一公历日（中午为丁亥）23:00 候选中寻找。
    $wuziCandidates = [];
    if ($stem === 4 && $branch === 0) {
        $wuziCandidates[] = $date->setTime(0, 0);
    }
    if ($stem === 3 && $branch === 11) {
        $wuziCandidates[] = $date->setTime(23, 0);
    }

    foreach ($wuziCandidates as $candidate) {
        $datetime = $candidate->format('Y-m-d H:i:s');
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
    // 使用 00:00 代表子时，可保证 12 个代表时辰都落在同一辛亥日内；每个候选仍重新核验日柱。
    if ($stem === 7 && $branch === 11) {
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
}

echo json_encode([
    'range' => [$startYear, $endYear],
    'timezone' => 'Asia/Shanghai',
    'days_scanned' => $daysScanned,
    'pan_calculations' => $panCalculations,
    'matches' => $found,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
