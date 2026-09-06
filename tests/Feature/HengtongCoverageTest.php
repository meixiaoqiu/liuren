<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\DiShengRule;
use App\Domain\Pan\Rules\HengtongRule;
use App\Domain\Pan\Rules\HuShengRule;
use App\Domain\Pan\Rules\HuWangRule;
use App\Domain\Pan\Rules\JuShengRule;
use App\Domain\Pan\Rules\JuWangRule;
use App\Services\PanCalculator;
use App\Support\PanRegression;

test('Hengtong lesson and its five grids match the exact expected cases across the 720 baseline', function () {
    $calculator = app(PanCalculator::class);
    $fixture = PanRegression::loadFixture();

    $grids = [
        'diSheng' => new DiShengRule,
        'juSheng' => new JuShengRule,
        'huSheng' => new HuShengRule,
        'juWang' => new JuWangRule,
        'huWang' => new HuWangRule,
    ];

    $groups = ['diSheng' => [], 'juSheng' => [], 'huSheng' => [], 'juWang' => [], 'huWang' => []];
    $yongshenShengRi = [];
    $lessonHits = [];

    foreach ($fixture['cases'] as $caseId => $case) {
        $facts = PanFacts::from($calculator->calculate($case['input']));

        foreach ($grids as $name => $rule) {
            if ($rule->match($facts) !== null) {
                $groups[$name][] = $caseId;
            }
        }

        // 用神生日：初传生日干，为总定义中独立于五格的成课条件。
        $initial = $facts->get('sanchuan0');
        $rigan = $facts->get('rigan');
        $initialElement = is_int($initial) ? $facts->branchElement($initial) : null;
        $stemElement = is_int($rigan) ? $facts->stemElement($rigan) : null;
        if ($initialElement !== null && $stemElement !== null && $stemElement === ($initialElement + 1) % 5) {
            $yongshenShengRi[] = $caseId;
        }

        if ((new HengtongRule)->match($facts) !== null) {
            $lessonHits[] = $caseId;
        }
    }

    sort($groups['diSheng']);
    sort($groups['juSheng']);
    sort($groups['huSheng']);
    sort($groups['juWang']);
    sort($groups['huWang']);
    sort($yongshenShengRi);
    sort($lessonHits);

    // 五格精确课号集合（一格命中多格时允许跨组重叠，但每组集合必须精确一致）。
    expect($groups['diSheng'])->toBe([
        'pointer-02_day-19',
        'pointer-02_day-37',
        'pointer-03_day-02',
        'pointer-03_day-03',
        'pointer-03_day-04',
        'pointer-03_day-12',
        'pointer-03_day-22',
        'pointer-03_day-32',
        'pointer-03_day-34',
        'pointer-03_day-42',
        'pointer-03_day-52',
        'pointer-03_day-53',
        'pointer-04_day-00',
        'pointer-04_day-09',
        'pointer-04_day-19',
        'pointer-04_day-20',
        'pointer-04_day-29',
        'pointer-04_day-39',
        'pointer-04_day-49',
        'pointer-04_day-57',
        'pointer-04_day-59',
        'pointer-07_day-08',
        'pointer-07_day-18',
        'pointer-07_day-28',
        'pointer-07_day-38',
        'pointer-07_day-48',
        'pointer-07_day-58',
        'pointer-08_day-09',
        'pointer-08_day-20',
        'pointer-08_day-29',
        'pointer-08_day-40',
        'pointer-08_day-49',
        'pointer-09_day-35',
        'pointer-09_day-45',
        'pointer-10_day-01',
        'pointer-10_day-51',
    ])->and($groups['juSheng'])->toBe([
        'pointer-01_day-04',
        'pointer-02_day-16',
        'pointer-02_day-56',
        'pointer-05_day-56',
        'pointer-07_day-09',
        'pointer-07_day-21',
        'pointer-07_day-33',
        'pointer-08_day-03',
        'pointer-08_day-06',
        'pointer-08_day-36',
        'pointer-08_day-39',
        'pointer-08_day-46',
        'pointer-08_day-51',
        'pointer-08_day-56',
        'pointer-09_day-00',
        'pointer-09_day-02',
        'pointer-09_day-12',
        'pointer-09_day-17',
        'pointer-09_day-18',
        'pointer-09_day-27',
        'pointer-09_day-30',
        'pointer-09_day-38',
        'pointer-09_day-42',
        'pointer-09_day-47',
        'pointer-09_day-48',
        'pointer-09_day-50',
        'pointer-10_day-02',
        'pointer-10_day-05',
        'pointer-10_day-35',
        'pointer-10_day-38',
        'pointer-10_day-45',
        'pointer-10_day-50',
        'pointer-10_day-55',
        'pointer-11_day-55',
        'pointer-11_day-56',
    ])->and($groups['huSheng'])->toBe([
        'pointer-00_day-09',
        'pointer-01_day-04',
        'pointer-01_day-27',
        'pointer-01_day-36',
        'pointer-02_day-56',
        'pointer-03_day-12',
        'pointer-03_day-18',
        'pointer-03_day-21',
        'pointer-05_day-17',
        'pointer-05_day-56',
        'pointer-06_day-33',
        'pointer-07_day-06',
        'pointer-08_day-51',
        'pointer-08_day-56',
        'pointer-09_day-16',
        'pointer-09_day-42',
        'pointer-09_day-45',
        'pointer-09_day-46',
        'pointer-09_day-48',
        'pointer-09_day-50',
        'pointer-10_day-50',
        'pointer-10_day-55',
        'pointer-11_day-47',
        'pointer-11_day-55',
        'pointer-11_day-56',
    ])->and($groups['juWang'])->toBe([
        'pointer-01_day-02',
        'pointer-01_day-08',
        'pointer-01_day-14',
        'pointer-01_day-20',
        'pointer-01_day-26',
        'pointer-01_day-32',
        'pointer-01_day-38',
        'pointer-01_day-44',
        'pointer-01_day-50',
        'pointer-01_day-56',
        'pointer-11_day-07',
        'pointer-11_day-19',
        'pointer-11_day-31',
        'pointer-11_day-43',
        'pointer-11_day-55',
    ])->and($groups['huWang'])->toBe([
        'pointer-01_day-50',
        'pointer-01_day-56',
        'pointer-02_day-01',
        'pointer-05_day-19',
        'pointer-07_day-20',
        'pointer-07_day-26',
        'pointer-08_day-37',
        'pointer-11_day-43',
        'pointer-11_day-55',
    ]);

    // 课命中集合恰为五格并集与“用神生日”的并集，去重后 189 课，防止漏课或误增。
    $merged = array_unique([...array_merge(...array_values($groups)), ...$yongshenShengRi]);
    sort($merged);

    expect($merged)->toHaveCount(189)
        ->and($lessonHits)->toBe($merged);
});
