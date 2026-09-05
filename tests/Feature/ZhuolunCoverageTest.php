<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZhuolunRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;

test('ZhuolunRule matches the expected cases across the 720 baseline', function () {
    $calculator = app(PanCalculator::class);
    $rule = new ZhuolunRule;
    $fixture = PanRegression::loadFixture();
    $hits = [];

    foreach ($fixture['cases'] as $caseId => $case) {
        $panResult = $calculator->calculate($case['input']);

        if ($rule->match(PanFacts::from($panResult)) !== null) {
            $hits[] = $caseId;
        }
    }

    // 口径（卯加申 或 卯加酉）命中 19 课。
    expect(count($hits))->toBe(19);
});
