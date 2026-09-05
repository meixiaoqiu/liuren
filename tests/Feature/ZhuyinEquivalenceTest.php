<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ZhuyinRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;

test('ZhuyinRule matches 46 cases across the 720 baseline', function () {
    $calculator = app(PanCalculator::class);
    $rule = new ZhuyinRule;
    $fixture = PanRegression::loadFixture();
    $ruleHits = [];

    foreach ($fixture['cases'] as $caseId => $case) {
        $panResult = $calculator->calculate($case['input']);

        if ($rule->match(PanFacts::from($panResult)) !== null) {
            $ruleHits[] = $caseId;
        }
    }

    expect(count($ruleHits))->toBe(46);
});
