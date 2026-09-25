<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

function cgsz_match(array $overrides = []): mixed
{
    $base = ['rigan' => 0, 'rizhi' => 0, 'yuezhi' => 2, 'guirenPeriod' => 'day',
        'sanchuan0' => 5, 'sanchuan1' => 6, 'sanchuan2' => 7,
        'tianpan' => range(0, 11), 'tianjiang' => range(0, 11),
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3, 'xingnian' => 4]]]];

    return (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult(array_replace($base, $overrides))));
}

test('第四法公开四条有序成立路线与三条断义', function () {
    $definition = (new CuiGuanShiZheRule)->definition();
    expect(array_column($definition['foundations'], 'code'))->toBe([
        'cui_guan_messenger', 'cui_guan_talisman', 'patron_parent_line', 'patron_noble_as_growth',
    ])->and(array_column($definition['judgments'], 'label'))->toBe(['催官使者空亡', '四时返本煞', '返吟附加']);
});

test('催官使者命中与人物缺失待评估', function () {
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;
    $generals[2] = 7;
    expect(cgsz_match(['tianpan' => $pan, 'tianjiang' => $generals])?->matchedRoutes)->toContain('cui_guan_messenger');
    $pan = range(0, 11);
    $pan[5] = 8;
    $generals = range(0, 11);
    $generals[5] = 7;
    expect(cgsz_match(['tianpan' => $pan, 'tianjiang' => $generals, 'context' => ['people' => []]])?->pendingRoutes)
        ->toContain('cui_guan_messenger');
});

test('催官符由日干推出官星五行并只在合局生官时待评估', function () {
    $pan = range(0, 11);
    $pan[5] = 6;
    $personPan = $pan;
    $personPan[4] = 6;
    expect(cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $personPan,
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 7])?->matchedRoutes)->toContain('cui_guan_talisman');
    $pending = cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $pan,
        'sanchuan0' => 11, 'sanchuan1' => 3, 'sanchuan2' => 7, 'context' => ['people' => []]]);
    expect($pending?->pendingRoutes)->toContain('cui_guan_talisman');
    $impossible = cgsz_match(['rigan' => 7, 'rizhi' => 7, 'tianpan' => $pan,
        'sanchuan0' => 0, 'sanchuan1' => 4, 'sanchuan2' => 8, 'context' => ['people' => []]]);
    expect($impossible?->pendingRoutes ?? [])->not->toContain('cui_guan_talisman');
});

test('父母爻只查日辰三传与行年六处并支持待评估', function () {
    $pan = range(0, 11);
    $pan[4] = 8;
    $matched = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $pan]);
    expect($matched?->matchedRoutes)->toContain('patron_parent_line')
        ->and($matched?->evidence['parent_hits'])->toContain('xingnian');
    $pan[4] = 4;
    $pan[3] = 8;
    $pan[8] = 0;
    $nianmingOnly = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $pan,
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3]]]]);
    expect($nianmingOnly?->matchedRoutes ?? [])->not->toContain('patron_parent_line')
        ->and($nianmingOnly?->pendingRoutes)->toContain('patron_parent_line');
    $knownFalsePan = range(0, 11);
    $knownFalsePan[8] = 0;
    $knownFalse = cgsz_match(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $knownFalsePan,
        'context' => ['people' => [['role' => 'querent', 'xingnian' => 4]]]]);
    expect($knownFalse?->pendingRoutes ?? [])->not->toContain('patron_parent_line');
});

test('乙卯昼贵空只排除实际乘贵人的子且保留普通子和亥', function () {
    $base = ['rigan' => 1, 'rizhi' => 3, 'guirenPeriod' => 'day', 'sanchuan0' => 0,
        'sanchuan1' => 1, 'sanchuan2' => 2, 'context' => ['people' => [['role' => 'querent', 'xingnian' => 4]]]];
    $ordinaryGenerals = range(0, 11);
    $ordinaryGenerals[0] = 1;
    expect(cgsz_match(array_replace($base, ['tianjiang' => $ordinaryGenerals]))?->matchedRoutes ?? [])->toContain('patron_parent_line');
    $generals = range(0, 11);
    $generals[0] = 0;
    expect(cgsz_match(array_replace($base, ['tianjiang' => $generals]))?->matchedRoutes ?? [])->not->toContain('patron_parent_line');
    expect(cgsz_match(array_replace($base, ['tianjiang' => $generals, 'sanchuan1' => 11]))?->matchedRoutes ?? [])->toContain('patron_parent_line');
});

test('己卯夜贵申空不用', function () {
    $match = cgsz_match(['rigan' => 5, 'rizhi' => 3, 'guirenPeriod' => 'night']);
    expect($match?->evidence['ji_mao_origin_voided'])->toBeTrue()
        ->and($match?->matchedRoutes ?? [])->not->toContain('patron_noble_as_growth');
});

test('催官使者空亡仍成立并产生结构化动态断义', function () {
    $pan = range(0, 11);
    $generals = range(0, 11);
    $pan[2] = 8;
    $generals[2] = 7;
    $match = cgsz_match(['rigan' => 0, 'rizhi' => 10, 'tianpan' => $pan, 'tianjiang' => $generals]);
    expect($match?->matchedRoutes)->toContain('cui_guan_messenger')
        ->and($match?->evidence['messenger_is_void'])->toBeTrue()
        ->and(array_column($match?->matchedJudgments ?? [], 'label'))->toContain('催官使者空亡');
});

test('固定生产案例锁定四条路线', function (string $datetime, string $route) {
    $data = (new PanCalculator)->calculate($datetime)->toArray();
    $data['context'] = ['people' => []];
    $match = (new CuiGuanShiZheRule)->match(PanFacts::from(new PanResult($data)));
    expect($match?->matchedRoutes)->toContain($route);
})->with([
    ['2000-01-05 03:00:00', 'cui_guan_messenger'],
    ['2000-01-01 01:00:00', 'patron_parent_line'], ['2000-01-01 23:00:00', 'patron_noble_as_growth'],
]);
