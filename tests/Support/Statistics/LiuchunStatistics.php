<?php

/** 文件作用：只读扫描生产盘，统计六阳、六阴及其交集。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LiuchunRule;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$year = (int) ($argv[1] ?? 2031);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$timezone = new DateTimeZone('Asia/Shanghai');
$calculator = new PanCalculator;
$rule = new LiuchunRule;
$counts = ['liuyang' => 0, 'liuyin' => 0, 'liuchun_total' => 0, 'overlap' => 0];
$denominator = 0;
$samples = [];
$start = new DateTimeImmutable("{$year}-01-01 00:00:00", $timezone);
$end = $start->modify('+1 year');
for ($date = $start; $date < $end; $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $datetime = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
        $denominator++;
        $facts = PanFacts::from($calculator->calculate($datetime));
        $sike = $facts->get('sike');
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
        $upper = is_array($sike) && count($sike) >= 8 ? [$sike[1], $sike[3], $sike[5], $sike[7]] : [];
        $initialFromSike = count($upper) === 4 && is_int($transmissions[0]) && in_array($transmissions[0], $upper, true);
        $liuyang = $initialFromSike
            && count(array_filter($upper, fn ($branch): bool => is_int($branch) && $branch % 2 === 0)) === 4
            && is_int($transmissions[1]) && $transmissions[1] % 2 === 0
            && is_int($transmissions[2]) && $transmissions[2] % 2 === 0;
        $liuyin = $initialFromSike
            && count(array_filter($upper, fn ($branch): bool => is_int($branch) && $branch % 2 === 1)) === 4
            && is_int($transmissions[1]) && $transmissions[1] % 2 === 1
            && is_int($transmissions[2]) && $transmissions[2] % 2 === 1;
        if ($liuyang && $liuyin) {
            $counts['overlap']++;
        }

        $match = $rule->match($facts);
        if (($match !== null) !== ($liuyang || $liuyin)) {
            throw new RuntimeException("{$datetime} 的统计判定与正式 matcher 不一致。");
        }
        if ($match === null) {
            continue;
        }
        $counts['liuchun_total']++;
        $type = $match->evidence['type'];
        $counts[$type]++;
        if (count($samples) < 20) {
            $samples[] = ['datetime' => $datetime, 'type' => $type, 'sike_upper_branches' => $match->evidence['sike_upper_branches'], 'transmissions' => $match->evidence['transmissions']];
        }
    }
}
if ($counts['liuchun_total'] !== $counts['liuyang'] + $counts['liuyin'] - $counts['overlap']) {
    throw new RuntimeException('六纯交集统计不闭合。');
}
echo json_encode(['year' => $year, 'timezone' => 'Asia/Shanghai', 'representative_hours' => $hours, 'denominator' => $denominator, 'counts' => $counts, 'samples' => $samples], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
