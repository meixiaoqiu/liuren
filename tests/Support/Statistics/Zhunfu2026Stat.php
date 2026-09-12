<?php

/**
 * 文件作用：只读统计迍福课在720核心输入与2026全年十二代表时辰中的命中数量和结构数。
 * 规则判断只调用生产 ZhunfuRule；人物上下文固定采用目录课例默认出生资料。
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\ZhunfuRule;
use App\Services\PanCalculator;

$calculator = new PanCalculator;
$fateCalculator = new FateCalculator;
$rule = new ZhunfuRule;
$birthDatetime = '1986-08-01 00:00:00';
$birthYearIndex = $calculator->calculate($birthDatetime)->get('nian_index');
$hours = [23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

$withQuerent = static function (PanResult $pan) use ($fateCalculator, $birthYearIndex, $birthDatetime): PanResult {
    $fate = $fateCalculator->calculate($birthYearIndex, $pan->get('nian_index'), 'male');
    $data = $pan->toArray();
    $data['context'] = ['people' => [[
        'role' => 'querent',
        'birth_datetime' => $birthDatetime,
        'gender' => 'male',
        ...$fate,
    ]]];

    return new PanResult($data);
};

$countRange = static function (array $datetimes) use ($calculator, $rule, $withQuerent): array {
    $matches = 0;
    $structures = [];
    $examples = [];

    foreach ($datetimes as $datetime) {
        $pan = $withQuerent($calculator->calculate($datetime));
        $match = $rule->match(PanFacts::from($pan));
        if ($match === null) {
            continue;
        }

        $matches++;
        $key = implode(':', [
            $pan->get('rigan'),
            $pan->get('rizhi'),
            $pan->get('tianpan')[0] ?? '?',
            $pan->get('sanchuan0'),
            $pan->get('sanchuan1'),
            $pan->get('sanchuan2'),
            $pan->get('guirenPeriod') ?? '?',
            PanFacts::from($pan)->seasonalPeriod()['key'] ?? '?',
        ]);
        $structures[$key] = ($structures[$key] ?? 0) + 1;
        $examples[$key] ??= $datetime;
    }

    return [
        'total' => count($datetimes),
        'matches' => $matches,
        'unique_structures' => count($structures),
        'structures' => $structures,
        'first_examples' => $examples,
    ];
};

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$core = array_map(fn (array $case): string => $case['input'], array_values($fixture['cases']));

$year = [];
for ($date = new DateTimeImmutable('2026-01-01'); $date <= new DateTimeImmutable('2026-12-31'); $date = $date->modify('+1 day')) {
    foreach ($hours as $hour) {
        $year[] = $date->setTime($hour, 0)->format('Y-m-d H:i:s');
    }
}

echo json_encode([
    'context' => ['birth_datetime' => $birthDatetime, 'gender' => 'male'],
    'core_720' => $countRange($core),
    'year_2026' => $countRange($year),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
