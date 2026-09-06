<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\YinCongRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;

test('YinCongRule matches the exact expected cases across the 720 baseline', function () {
    $calculator = app(PanCalculator::class);
    $rule = new YinCongRule;
    $fixture = PanRegression::loadFixture();

    $gongGan = [];
    $gongZhi = [];
    $fuyin = [];

    foreach ($fixture['cases'] as $caseId => $case) {
        $panResult = $calculator->calculate($case['input']);
        $match = $rule->match(PanFacts::from($panResult));

        if ($match === null) {
            continue;
        }

        $titles = array_column($match->evidence['foundations'], 'title');

        if (in_array('拱天干', $titles, true)) {
            $gongGan[] = $caseId;
        }
        if (in_array('拱地支', $titles, true)) {
            $gongZhi[] = $caseId;
        }
        if (in_array('干支拱日禄', $titles, true)
            || in_array('干支拱夜贵', $titles, true)
            || in_array('干支拱昼贵', $titles, true)) {
            $fuyin[] = $caseId;
        }
    }

    sort($gongGan);
    sort($gongZhi);
    sort($fuyin);

    // 口径：初末引从天干 5 课 + 初末引从地支 7 课 + 伏吟夹拱（日禄/夜贵/昼贵）6 课 = 18 课。
    // 贵临干支拱年命因 720 基线 nianming 恒为 0，无对应课例。
    expect($gongGan)->toBe([
        'pointer-05_day-09',
        'pointer-05_day-16',
        'pointer-05_day-48',
        'pointer-11_day-39',
        'pointer-11_day-47',
    ])->and($gongZhi)->toBe([
        'pointer-05_day-23',
        'pointer-05_day-30',
        'pointer-05_day-35',
        'pointer-05_day-58',
        'pointer-11_day-00',
        'pointer-11_day-06',
        'pointer-11_day-58',
    ])->and($fuyin)->toBe([
        'pointer-00_day-00',
        'pointer-00_day-05',
        'pointer-00_day-06',
        'pointer-00_day-45',
        'pointer-00_day-53',
        'pointer-00_day-59',
    ]);

    // 三类命中两两不相交，总命中课数恰为 18。
    expect(array_merge($gongGan, $gongZhi, $fuyin))->toHaveCount(18)
        ->and(array_unique(array_merge($gongGan, $gongZhi, $fuyin)))->toHaveCount(18);
});
