<?php

use App\Domain\Pan\BiFa\Rules\WangLuLinShenRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCaseCatalog;

test('第七法五个真实程序案例锁定各自判断及强度', function () {
    $expected = [
        'bifa.07.generated-ordinary' => [
            'datetime' => '2031-01-07T03:00',
            'judgments' => [['label' => '宜守旺禄', 'effect' => 'neutral']],
        ],
        'bifa.07.generated-void' => [
            'datetime' => '2031-01-05T03:00',
            'judgments' => [['label' => '旺禄旬空', 'effect' => 'resolve']],
        ],
        'bifa.07.generated-closed-mouth' => [
            'datetime' => '2031-01-31T01:00',
            'judgments' => [
                ['label' => '闭口禄', 'effect' => 'resolve'],
                ['label' => '旺禄乘白虎', 'effect' => 'reduce'],
            ],
        ],
        'bifa.07.generated-xuanwu' => [
            'datetime' => '2031-01-03T03:00',
            'judgments' => [['label' => '禄被玄武夺', 'effect' => 'resolve']],
        ],
        'bifa.07.generated-baihu' => [
            'datetime' => '2031-01-01T03:00',
            'judgments' => [
                ['label' => '旺禄乘白虎', 'effect' => 'reduce'],
                ['label' => '宜守旺禄', 'effect' => 'neutral'],
            ],
        ],
    ];

    $calculator = new PanCalculator;
    $rule = new WangLuLinShenRule;
    $seen = [];

    foreach (BiFaCaseCatalog::casesForLaw('bifa.07') as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }

        expect($expected)->toHaveKey($case['case_id'])
            ->and($case['datetime'])->toBe($expected[$case['case_id']]['datetime'])
            ->and($case['routes'])->toBe(['wang_lu_on_stem']);

        $pan = $calculator->calculate(str_replace('T', ' ', $case['datetime']));
        $match = $rule->match(PanFacts::from($pan));
        $judgments = array_map(
            fn (array $judgment): array => ['label' => $judgment['label'], 'effect' => $judgment['effect']],
            $match?->matchedJudgments ?? [],
        );

        expect($match)->not->toBeNull("case_id={$case['case_id']} 必须命中第七法")
            ->and($match->matchedRoutes)->toBe(['wang_lu_on_stem'])
            ->and($judgments)->toBe(
                $expected[$case['case_id']]['judgments'],
                "case_id={$case['case_id']} 的判断及强度必须与真实排盘一致",
            );

        $seen[$case['case_id']] = true;
    }

    expect(array_keys($seen))->toBe(array_keys($expected));
});

test('白虎单独乘禄不产生任何硬解除因素', function () {
    $pan = (new PanCalculator)->calculate('2031-01-01 03:00');
    $match = (new WangLuLinShenRule)->match(PanFacts::from($pan));
    $labels = array_column($match?->matchedJudgments ?? [], 'label');
    $effects = array_column($match?->matchedJudgments ?? [], 'effect', 'label');

    expect($labels)->toBe(['旺禄乘白虎', '宜守旺禄'])
        ->and($labels)->not->toContain('旺禄旬空', '闭口禄', '禄被玄武夺')
        ->and($effects['旺禄乘白虎'])->toBe('reduce')
        ->and($effects['宜守旺禄'])->toBe('neutral');
});
