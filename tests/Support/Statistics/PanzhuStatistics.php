<?php

/** 文件作用：按真实年月日时扫描盘珠课、天心格、回还格命中规模；本课依赖四建，不使用脱离真实时间的720静态盘作主体统计。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HuihuanRule;
use App\Domain\Pan\Rules\PanzhuRule;
use App\Domain\Pan\Rules\TianxinRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$panzhu = new PanzhuRule;
$tianxin = new TianxinRule;
$huihuan = new HuihuanRule;

$counts = array_fill_keys([
    'total',
    'panzhu',
    'tianxin',
    'tianxin_four_lessons',
    'tianxin_transmissions',
    'huihuan',
    'huihuan_without_panzhu',
    'tianxin_without_panzhu',
], 0);
$firstMatches = [];

for ($date = new DateTimeImmutable("{$year}-01-01"), $end = $date->modify('+1 year'); $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $facts = PanFacts::from($calculator->calculate($datetime));
        $counts['total']++;

        $panzhuMatch = $panzhu->match($facts);
        $tianxinMatch = $tianxin->match($facts);
        $huihuanMatch = $huihuan->match($facts);

        if ($panzhuMatch !== null) {
            $counts['panzhu']++;
            if (count($firstMatches) < 10) {
                $firstMatches[] = [
                    'datetime' => $datetime,
                    'four_establishments' => $panzhuMatch->evidence['four_establishments'],
                    'lesson_branches' => $panzhuMatch->evidence['lesson_branches'],
                    'transmissions' => $panzhuMatch->evidence['transmissions'],
                ];
            }
        }

        if ($tianxinMatch !== null) {
            $counts['tianxin']++;
            if (($tianxinMatch->evidence['four_establishments_in_lessons'] ?? false) === true) {
                $counts['tianxin_four_lessons']++;
            }
            if (($tianxinMatch->evidence['four_establishments_in_transmissions'] ?? false) === true) {
                $counts['tianxin_transmissions']++;
            }
            if ($panzhuMatch === null) {
                $counts['tianxin_without_panzhu']++;
            }
        }

        if ($huihuanMatch !== null) {
            $counts['huihuan']++;
            if ($panzhuMatch === null) {
                $counts['huihuan_without_panzhu']++;
            }
        }
    }
}

echo json_encode([
    'year' => $year,
    'timezone' => 'Asia/Shanghai / 项目生产排盘口径',
    'hours' => $hours,
    'counts' => $counts,
    'first_panzhu_matches' => $firstMatches,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
