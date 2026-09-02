<?php

use App\Services\PanCalculator;

test('ten stems select day and night noblemen and distribute all generals independently', function () {
    // 固定规则表：甲戊庚丑未、乙己子申、丙丁亥酉、辛午寅、壬癸巳卯。
    // 2000-01-07为甲子日，随后九天依次覆盖十干；不在换日边界取样。
    $noblemen = [[1, 7], [0, 8], [11, 9], [11, 9], [1, 7], [0, 8], [1, 7], [6, 2], [5, 3], [5, 3]];
    // 一月北京07:00尚未日出，17:59已日落，其他样本远离边界。
    $samples = [
        ['00:00:00', 0, 'night'], ['01:00:00', 1, 'night'],
        ['03:00:00', 2, 'night'], ['05:00:00', 3, 'night'],
        ['07:00:00', 4, 'night'], ['09:00:00', 5, 'day'],
        ['11:00:00', 6, 'day'], ['13:00:00', 7, 'day'],
        ['15:00:00', 8, 'day'], ['17:59:00', 9, 'night'],
        ['19:00:00', 10, 'night'], ['21:00:00', 11, 'night'],
    ];
    $forwardFromNobleman = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    $reverseFromNobleman = [0, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1];
    $forwardGrounds = [0, 1, 2, 3, 4, 11];
    $seenGrounds = [];
    $seenStemPeriods = [];

    foreach ($noblemen as $stem => [$dayNobleman, $nightNobleman]) {
        foreach ($samples as [$time, $hourBranch, $period]) {
            $input = sprintf('2000-01-%02d %s', 7 + $stem, $time);
            $pan = app(PanCalculator::class)->calculate($input)->toArray();
            $nobleman = $period === 'day' ? $dayNobleman : $nightNobleman;
            // 此十日月将固定为丑；由月将加时建立独立的天地盘对应表。
            $expectedHeaven = array_fill(0, 12, 0);
            foreach (range(0, 11) as $step) {
                $expectedHeaven[($hourBranch + $step) % 12] = (1 + $step) % 12;
            }
            $ground = array_search($nobleman, $expectedHeaven, true);
            $sequence = in_array($ground, $forwardGrounds, true) ? $forwardFromNobleman : $reverseFromNobleman;
            $expectedGenerals = array_fill(0, 12, 0);
            foreach ($sequence as $offset => $general) {
                $expectedGenerals[($ground + $offset) % 12] = $general;
            }

            expect($pan['rigan'])->toBe($stem, $input)
                ->and($pan['guirenPeriod'])->toBe($period, $input)
                ->and($pan['tianpan'])->toBe($expectedHeaven, $input)
                ->and($pan['tianjiang'])->toBe($expectedGenerals, $input);

            foreach (range(0, 2) as $position) {
                $transmissionGround = array_search($pan['sanchuan'.$position], $expectedHeaven, true);
                expect($transmissionGround)->not->toBeFalse();
                expect($pan['sanchuan'.$position.'tianjiang'])
                    ->toBe($expectedGenerals[$transmissionGround], $input);
            }

            $seenGrounds[$ground] = true;
            $seenStemPeriods[$stem.'_'.$period] = true;
        }
    }

    $grounds = array_keys($seenGrounds);
    sort($grounds);
    expect($grounds)->toBe(range(0, 11))
        ->and($seenStemPeriods)->toHaveCount(20);
});

test('Beijing daylight boundaries select the correct nobleman to the second', function (string $time, string $period, int $nobleman) {
    // 已有标准课例的固定时间，不使用date_sun_info或待测结果生成边界。
    $pan = app(PanCalculator::class)->calculate('2013-09-04 '.$time)->toArray();
    $noblemanGround = array_search(0, $pan['tianjiang'], true);

    expect($pan['rigan'])->toBe(9)
        ->and($pan['sunrise'])->toBe('2013-09-04 05:44:55')
        ->and($pan['sunset'])->toBe('2013-09-04 18:41:57')
        ->and($pan['guirenPeriod'])->toBe($period)
        ->and($noblemanGround)->not->toBeFalse()
        ->and($pan['tianpan'][$noblemanGround])->toBe($nobleman);
})->with([
    'before sunrise' => ['05:44:54', 'night', 3],
    'at sunrise' => ['05:44:55', 'day', 5],
    'after sunrise' => ['05:44:56', 'day', 5],
    'before sunset' => ['18:41:56', 'day', 5],
    'at sunset' => ['18:41:57', 'night', 3],
    'after sunset' => ['18:41:58', 'night', 3],
]);
