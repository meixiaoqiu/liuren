<?php

use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCaseCatalog;

/**
 * 第六法「六阴相继尽昏迷」案例目录定向执行测试。
 *
 * 目标：独立于全量 BiFaCaseExecutionTest，直接逐条执行第六法
 * BiFaCaseCatalog 中声明的 executable datetime，
 * 验证每条案例声明的 routes 与 LiuYinXiangJiRule::match() 返回的
 * matchedRoutes **完整且顺序一致**。
 *
 * 同时维护一份与 BiFaCaseCatalog 互相独立、由本测试硬编码的
 * expected routes 表——即使目录与测试同时被改坏，两者也无法互相
 * 「证明正确」。
 */
test('each executable bifa.06 case declares exactly the routes it actually matches', function () {
    $calculator = new PanCalculator;
    $rule = new LiuYinXiangJiRule;

    // 与 BiFaCaseCatalog 互相独立的硬编码 expected routes 表。
    // 任何修改此处或 catalog 时，须保证两者仍然完全一致。
    $expected = [
        'bifa.06.generated-ji-mao-six-yin' => ['six_yin'],
        'bifa.06.generated-gui-mao-source-exhausted' => [
            'six_yin',
            'source_exhausted_root_severed',
        ],
        'bifa.06.generated-gui-wei-source-exhausted' => [
            'six_yin',
            'source_exhausted_root_severed',
        ],
        'bifa.06.generated-xin-mao-source-exhausted' => ['source_exhausted_root_severed'],
        'bifa.06.generated-gui-si-source-exhausted' => [
            'six_yin',
            'source_exhausted_root_severed',
        ],
    ];

    $cases = BiFaCaseCatalog::casesForLaw('bifa.06');
    $failures = [];
    $seen = [];

    expect($cases)->not->toBeEmpty('第六法案例目录必须至少存在一条登记');

    foreach ($cases as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }

        $datetime = (string) ($case['datetime'] ?? '');
        expect($datetime)->not->toBeEmpty("case_id={$case['case_id']} executable 案例必须含 datetime");

        $pan = $calculator->calculate(str_replace('T', ' ', $datetime));
        $match = $rule->match(PanFacts::from($pan));

        if ($match === null) {
            $failures[] = "case_id={$case['case_id']}: datetime={$datetime} 起盘后 LiuYinXiangJiRule 必须返回非空 match";

            continue;
        }

        $seen[$case['case_id']] = true;

        // 第一重保护：catalog 声明的 routes 必须等于本测试硬编码的 expected map。
        // catalog 与测试同时写错时不会互相「证明正确」。
        expect(array_key_exists($case['case_id'], $expected))->toBeTrue(
            "case_id={$case['case_id']} 不在硬编码 expected map 中",
        );
        expect($case['routes'])->toBe(
            $expected[$case['case_id']],
            "case_id={$case['case_id']} catalog.routes 与硬编码 expected 不一致",
        );

        // 第二重保护：matchedRoutes 必须完整、顺序一致地等于 expected。
        expect($match->matchedRoutes)->toBe(
            $expected[$case['case_id']],
            "case_id={$case['case_id']}: 第六法 routes 必须完整且顺序一致（声明："
                .implode(',', $case['routes'])
                .'；实际：'.implode(',', $match->matchedRoutes).'）',
        );
    }

    expect($seen)->toHaveCount(5, '第六法当前应有 5 条 executable 案例')
        ->and($failures)->toBe([], '每条 executable 案例的 routes 都必须与硬编码 expected 完全一致：'.implode("\n", $failures));
});

/**
 * 三个癸日案例的双 route 回归断言，加上己卯与辛卯的单 route 断言，
 * 防止某条执行记录被悄悄删掉一条 route，或某条新增被错误合并为单 route。
 */
test('three gui-* cases match six_yin + source_exhausted_root_severed while ji-mao and xin-mao stay single route', function () {
    $calculator = new PanCalculator;
    $rule = new LiuYinXiangJiRule;

    $expectations = [
        '2031-01-03T21:00' => ['six_yin', 'source_exhausted_root_severed'],
        '2031-02-12T19:00' => ['six_yin', 'source_exhausted_root_severed'],
        '2031-02-22T17:00' => ['six_yin', 'source_exhausted_root_severed'],
        '2031-02-08T19:00' => ['six_yin'],
        '2031-02-20T17:00' => ['source_exhausted_root_severed'],
    ];

    foreach ($expectations as $datetime => $expectedRoutes) {
        $pan = $calculator->calculate(str_replace('T', ' ', $datetime));
        $match = $rule->match(PanFacts::from($pan));

        expect($match)->not->toBeNull("datetime={$datetime} 必须命中 LiuYinXiangJiRule")
            ->and($match->matchedRoutes)->toBe(
                $expectedRoutes,
                "datetime={$datetime} matchedRoutes 必须完整且顺序一致（实际：".implode(',', $match->matchedRoutes).'）',
            );
    }
});
