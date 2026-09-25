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
 * 验证每条案例声明的 routes 都能被 LiuYinXiangJiRule 命中。
 *
 * 该测试只评估第六法自身的判定逻辑，不构造完整 Livewire 排盘组件，
 * 不读取其它已注册毕法规则的命中结果。
 */
test('each executable bifa.06 case reproduces all of its declared routes through LiuYinXiangJiRule', function () {
    $calculator = new PanCalculator;
    $rule = new LiuYinXiangJiRule;
    $cases = BiFaCaseCatalog::casesForLaw('bifa.06');
    $failures = [];
    $verified = [];

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

        foreach ($case['routes'] as $route) {
            if ($route === '') {
                continue;
            }
            if (! in_array($route, $match->matchedRoutes, true)) {
                $failures[] = "case_id={$case['case_id']}: datetime={$datetime} 声明 route={$route} 必须在 matchedRoutes 中（实际：".implode(',', $match->matchedRoutes).'）';
            }
        }

        $verified[$case['case_id']] = [
            'declared' => $case['routes'],
            'matched' => $match->matchedRoutes,
        ];
    }

    expect($verified)->toHaveCount(5, '第六法当前应有 5 条 executable 案例')
        ->and($failures)->toBe([], '每条 executable 案例声明的 routes 必须真实命中：'.implode("\n", $failures));
});
