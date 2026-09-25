<?php

use App\Domain\Pan\BiFa\Rules\QuanSheBuZhengRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCaseCatalog;

test('第八法六个真实程序案例锁定各自判断及强度', function () {
    $expected = [
        'bifa.08.generated-plain' => [
            'datetime' => '2031-01-04T05:00',
            'judgments' => [],
        ],
        'bifa.08.generated-tombed' => [
            'datetime' => '2031-01-01T09:00',
            'judgments' => [['label' => '禄受墓', 'effect' => 'reduce']],
        ],
        'bifa.08.generated-controlled' => [
            'datetime' => '2031-01-13T03:00',
            'judgments' => [['label' => '禄受支克', 'effect' => 'reduce']],
        ],
        'bifa.08.generated-drained' => [
            'datetime' => '2031-01-02T07:00',
            'judgments' => [['label' => '禄受支脱', 'effect' => 'reduce']],
        ],
        'bifa.08.generated-tombed-controlled' => [
            'datetime' => '2031-02-21T07:00',
            'judgments' => [
                ['label' => '禄受墓', 'effect' => 'reduce'],
                ['label' => '禄受支克', 'effect' => 'reduce'],
            ],
        ],
        'bifa.08.generated-tombed-drained' => [
            'datetime' => '2031-02-15T09:00',
            'judgments' => [
                ['label' => '禄受墓', 'effect' => 'reduce'],
                ['label' => '禄受支脱', 'effect' => 'reduce'],
            ],
        ],
    ];

    $calculator = new PanCalculator;
    $rule = new QuanSheBuZhengRule;
    $seen = [];

    foreach (BiFaCaseCatalog::casesForLaw('bifa.08') as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }

        expect($expected)->toHaveKey($case['case_id'])
            ->and($case['datetime'])->toBe($expected[$case['case_id']]['datetime'])
            ->and($case['routes'])->toBe(['lu_on_branch']);

        $pan = $calculator->calculate(str_replace('T', ' ', $case['datetime']));
        $match = $rule->match(PanFacts::from($pan));
        $judgments = array_map(
            fn (array $judgment): array => ['label' => $judgment['label'], 'effect' => $judgment['effect']],
            $match?->matchedJudgments ?? [],
        );

        expect($match)->not->toBeNull("case_id={$case['case_id']} 必须命中第八法")
            ->and($match->matchedRoutes)->toBe(['lu_on_branch'])
            ->and($judgments)->toBe(
                $expected[$case['case_id']]['judgments'],
                "case_id={$case['case_id']} 的判断及强度必须与真实排盘一致",
            );

        $seen[$case['case_id']] = true;
    }

    expect(array_keys($seen))->toBe(array_keys($expected));
});

test('六个可执行案例均能在排盘组件中自动命中 lu_on_branch', function () {
    $cases = array_filter(
        BiFaCaseCatalog::casesForLaw('bifa.08'),
        static fn (array $c): bool => $c['status'] === 'executable',
    );

    expect($cases)->not->toBeEmpty();

    foreach ($cases as $case) {
        $pan = (new PanCalculator)->calculate(str_replace('T', ' ', $case['datetime']));
        $match = (new QuanSheBuZhengRule)->match(PanFacts::from($pan));

        expect($match)->not->toBeNull("case_id={$case['case_id']} 必须命中")
            ->and($match->matchedRoutes)->toBe(['lu_on_branch'])
            ->and($match->evidence['day_lu'])->toBe($match->evidence['branch_upper']);
    }
});

test('排盘页 judgment description 与 definition 一致：失禄 / 以禄偿债', function () {
    // 防止以后只改 definition 忘记同步 match() 的 matchedJudgments.description。
    $calculator = new PanCalculator;
    $rule = new QuanSheBuZhengRule;

    $cases = [
        'bifa.08.generated-tombed' => [
            'datetime' => '2031-01-01T09:00',
            'label' => '禄受墓',
            'snippet' => '因宅而失禄',
        ],
        'bifa.08.generated-controlled' => [
            'datetime' => '2031-01-13T03:00',
            'label' => '禄受支克',
            'snippet' => '因宅而失禄',
        ],
        'bifa.08.generated-drained' => [
            'datetime' => '2031-01-02T07:00',
            'label' => '禄受支脱',
            'snippet' => '以禄偿债',
        ],
    ];

    foreach ($cases as $caseId => $expect) {
        $pan = $calculator->calculate(str_replace('T', ' ', $expect['datetime']));
        $match = $rule->match(PanFacts::from($pan));
        expect($match)->not->toBeNull();

        $labels = array_column($match->matchedJudgments, 'label');
        expect($labels)->toContain($expect['label']);

        $description = '';
        foreach ($match->matchedJudgments as $judgment) {
            if ($judgment['label'] === $expect['label']) {
                $description = $judgment['description'];
                break;
            }
        }

        expect($description)->toContain($expect['snippet']);

        // 同时 definition() 自身必须包含同样的 snippet，禁止 description 漂移。
        $definitionDescription = '';
        foreach ($rule->definition()['judgments'] ?? [] as $judgment) {
            if ($judgment['label'] === $expect['label']) {
                $definitionDescription = $judgment['description'];
                break;
            }
        }
        expect($definitionDescription)->toContain($expect['snippet']);
    }
});
