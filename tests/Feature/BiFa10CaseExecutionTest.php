<?php

use App\Domain\Pan\BiFa\Rules\XiuMuNanDiaoRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCaseCatalog;

test('第十法两个程序验证案例均由生产排盘自然复现', function () {
    $calculator = new PanCalculator;
    $rule = new XiuMuNanDiaoRule;
    $cases = array_values(array_filter(
        BiFaCaseCatalog::casesForLaw('bifa.10'),
        static fn (array $case): bool => $case['status'] === 'executable',
    ));

    expect($cases)->toHaveCount(2);

    foreach ($cases as $case) {
        $pan = $calculator->calculate(str_replace('T', ' ', $case['datetime']));
        $facts = PanFacts::from($pan);
        $match = $rule->match($facts);

        expect($match)->not->toBeNull("case_id={$case['case_id']} 必须命中")
            ->and($match->matchedRoutes)->toBe($case['routes'], "case_id={$case['case_id']} 路径必须与目录一致")
            ->and($facts->get('sanchuan0'))->toBe(3)
            ->and($match->evidence['mao_ground'])->toBeIn([8, 9, 10]);

        if ($case['routes'] === ['rotten_wood']) {
            expect($facts->isBranchXunVoid(3))->toBeTrue()
                ->and($match->evidence['mao_ground'])->toBe(10);
        } else {
            $ground = $match->evidence['mao_ground'];
            expect($ground)->toBeIn([8, 9])
                ->and($facts->isBranchXunVoid(3))->toBeFalse()
                ->and($facts->isBranchXunVoid($ground))->toBeTrue();
        }
    }
});
