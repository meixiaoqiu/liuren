<?php

/** 文件作用：只读搜索指定年份范围内《灵觉经》庚午外战、己酉内战完整生产盘；只在目标干支日展开十二时辰。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use Illuminate\Contracts\Console\Kernel;

$startYear = (int) ($argv[1] ?? 1900);
$endYear = (int) ($argv[2] ?? 2100);
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];
$calculator = new PanCalculator;
$found = [];
$targetDays = 0;
$expanded = 0;

for ($date = new DateTimeImmutable("{$startYear}-01-01"), $end = new DateTimeImmutable(($endYear + 1).'-01-01'); $date < $end; $date = $date->modify('+1 day')) {
    $noon = PanFacts::from($calculator->calculate($date->format('Y-m-d').' 11:00:00'));
    $stem = $noon->get('rigan');
    $branch = $noon->get('rizhi');
    $target = match (true) {
        $stem === 6 && $branch === 6 => ['label' => '庚午外战', 'transmissions' => [10, 6, 2], 'generals' => [3, 11, 7]],
        $stem === 5 && $branch === 9 => ['label' => '己酉内战', 'transmissions' => [9, 1, 5], 'generals' => [3, 11, 7]],
        default => null,
    };
    if ($target === null) {
        continue;
    }
    $targetDays++;
    foreach ($hours as $hour) {
        $datetime = sprintf('%s %02d:00:00', $date->format('Y-m-d'), $hour);
        $facts = PanFacts::from($calculator->calculate($datetime));
        $expanded++;
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
        $generals = [$facts->get('sanchuan0tianjiang'), $facts->get('sanchuan1tianjiang'), $facts->get('sanchuan2tianjiang')];
        if ($transmissions === $target['transmissions'] && $generals === $target['generals']) {
            $found[$target['label']][] = $datetime;
        }
    }
}

echo json_encode(['range' => [$startYear, $endYear], 'target_days' => $targetDays, 'expanded_times' => $expanded, 'found' => $found], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
