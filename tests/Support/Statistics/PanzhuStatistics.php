<?php

/** 文件作用：按真实年月日时扫描盘珠课、天心格、回还格命中规模，并输出三者包含关系与独立命中规模。 */

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
$timezone = new DateTimeZone('Asia/Shanghai');
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
    'tianxin_four_lessons_only',
    'tianxin_transmissions_only',
    'tianxin_both_routes',
    'huihuan',
    'huihuan_without_panzhu',
    'tianxin_without_panzhu',
    'panzhu_without_tianxin',
    'panzhu_without_huihuan',
], 0);
$firstPanzhuMatches = [];
$firstTianxinOnlyMatches = [];
$firstHuihuanOnlyMatches = [];

$start = new DateTimeImmutable("{$year}-01-01 00:00:00", $timezone);
$end = $start->modify('+1 year');

for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $facts = PanFacts::from($calculator->calculate($datetime));
        $counts['total']++;

        $panzhuMatch = $panzhu->match($facts);
        $tianxinMatch = $tianxin->match($facts);
        $huihuanMatch = $huihuan->match($facts);

        if ($panzhuMatch !== null) {
            $counts['panzhu']++;
            if ($tianxinMatch === null) {
                $counts['panzhu_without_tianxin']++;
            }
            if ($huihuanMatch === null) {
                $counts['panzhu_without_huihuan']++;
            }
            if (count($firstPanzhuMatches) < 10) {
                $firstPanzhuMatches[] = [
                    'datetime' => $datetime,
                    'four_establishments' => $panzhuMatch->evidence['four_establishments'],
                    'lesson_branches' => $panzhuMatch->evidence['lesson_branches'],
                    'transmissions' => $panzhuMatch->evidence['transmissions'],
                ];
            }
        }

        if ($tianxinMatch !== null) {
            $counts['tianxin']++;
            $inLessons = ($tianxinMatch->evidence['four_establishments_in_lessons'] ?? false) === true;
            $inTransmissions = ($tianxinMatch->evidence['four_establishments_in_transmissions'] ?? false) === true;

            if ($inLessons) {
                $counts['tianxin_four_lessons']++;
            }
            if ($inTransmissions) {
                $counts['tianxin_transmissions']++;
            }
            if ($inLessons && $inTransmissions) {
                $counts['tianxin_both_routes']++;
            } elseif ($inLessons) {
                $counts['tianxin_four_lessons_only']++;
            } elseif ($inTransmissions) {
                $counts['tianxin_transmissions_only']++;
            }

            if ($panzhuMatch === null) {
                $counts['tianxin_without_panzhu']++;
                if (count($firstTianxinOnlyMatches) < 10) {
                    $firstTianxinOnlyMatches[] = [
                        'datetime' => $datetime,
                        'routes' => $tianxinMatch->evidence['matched_routes'] ?? [],
                        'four_establishments' => $tianxinMatch->evidence['four_establishments'],
                        'lesson_branches' => $tianxinMatch->evidence['lesson_branches'],
                        'transmissions' => $tianxinMatch->evidence['transmissions'],
                    ];
                }
            }
        }

        if ($huihuanMatch !== null) {
            $counts['huihuan']++;
            if ($panzhuMatch === null) {
                $counts['huihuan_without_panzhu']++;
                if (count($firstHuihuanOnlyMatches) < 10) {
                    $firstHuihuanOnlyMatches[] = [
                        'datetime' => $datetime,
                        'lesson_branches' => $huihuanMatch->evidence['lesson_branches'],
                        'transmissions' => $huihuanMatch->evidence['transmissions'],
                    ];
                }
            }
        }
    }
}

$ratio = static fn (int $count): float => $counts['total'] === 0 ? 0.0 : $count / $counts['total'];

$result = [
    'year' => $year,
    'timezone' => 'Asia/Shanghai',
    'representative_hours' => $hours,
    'denominator' => $counts['total'],
    'counts' => $counts,
    'ratios' => [
        'panzhu' => $ratio($counts['panzhu']),
        'tianxin' => $ratio($counts['tianxin']),
        'huihuan' => $ratio($counts['huihuan']),
        'tianxin_without_panzhu' => $ratio($counts['tianxin_without_panzhu']),
        'huihuan_without_panzhu' => $ratio($counts['huihuan_without_panzhu']),
    ],
    'relations' => [
        'panzhu_implies_tianxin' => $counts['panzhu_without_tianxin'] === 0,
        'panzhu_implies_huihuan' => $counts['panzhu_without_huihuan'] === 0,
    ],
    'first_panzhu_matches' => $firstPanzhuMatches,
    'first_tianxin_without_panzhu' => $firstTianxinOnlyMatches,
    'first_huihuan_without_panzhu' => $firstHuihuanOnlyMatches,
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;

if (! $result['relations']['panzhu_implies_tianxin'] || ! $result['relations']['panzhu_implies_huihuan']) {
    fwrite(STDERR, "Invariant failed: every Panzhu match must also match Tianxin and Huihuan.\n");
    exit(1);
}
