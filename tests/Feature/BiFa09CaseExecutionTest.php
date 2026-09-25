<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\BiNanTaoShengRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;
use App\Support\BiFaCaseCatalog;

test('第九法七个程序验证案例均由生产排盘自然复现', function () {
    $calculator = new PanCalculator;
    $rule = new BiNanTaoShengRule;
    $cases = array_values(array_filter(
        BiFaCaseCatalog::casesForLaw('bifa.09'),
        static fn (array $case): bool => $case['status'] === 'executable',
    ));

    expect($cases)->toHaveCount(7);

    foreach ($cases as $case) {
        $pan = $calculator->calculate(str_replace('T', ' ', $case['datetime']));

        if ($case['case_id'] === 'bifa.09.generated-fate-ding') {
            $birthPan = $calculator->calculate(str_replace('T', ' ', $case['birth']));
            $fate = (new FateCalculator)->calculate($birthPan->get('nian_index'), $pan->get('nian_index'), $case['gender']);
            expect($fate['nianming'])->toBe(9);
            $pan = new PanResult([
                ...$pan->toArray(),
                'context' => ['people' => [[
                    'role' => 'querent', 'birth_datetime' => $case['birth'], 'gender' => $case['gender'], ...$fate,
                ]]],
            ]);
        }

        $match = $rule->match(PanFacts::from($pan));
        expect($match)->not->toBeNull("case_id={$case['case_id']} 必须命中")
            ->and($match->matchedRoutes)->toBe($case['routes'], "case_id={$case['case_id']} 路径必须与目录一致");
    }
});
