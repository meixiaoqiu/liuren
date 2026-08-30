<?php

use App\Data\PanResult;
use App\Domain\Pan\Rules\PanRuleEngine;

test('the engine returns every matching rule instead of one category', function () {
    $pan = new PanResult([
        'tianpan' => [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5],
        'shehaiTrace' => [
            'relation' => '下贼上',
            'decision' => ['selected_branch' => 2],
        ],
        'calculationTrace' => [
            'plate_patterns' => ['fanyin'],
            'initial_transmission' => [
                'recorded' => true,
                'method' => 'shehai',
                'evidence' => [
                    'relation' => '下贼上',
                    'decision' => ['selected_branch' => 2],
                ],
            ],
            'middle_transmission' => [
                'recorded' => true,
                'method' => 'chong',
                'source' => 'initial_transmission',
            ],
            'final_transmission' => [
                'recorded' => true,
                'method' => 'chong',
                'source' => 'middle_transmission',
            ],
        ],
    ]);

    $matches = (new PanRuleEngine)->evaluate($pan);
    $codes = array_map(fn ($match): string => $match->code, $matches);

    expect($codes)
        ->toContain('plate.fanyin')
        ->toContain('selection.shehai')
        ->toContain('sanchuan.chong')
        ->and($matches)->toHaveCount(3);

    $matchesByCode = [];

    foreach ($matches as $match) {
        $matchesByCode[$match->code] = $match;
    }

    expect($matchesByCode['selection.shehai']->name)->toBe('涉害课')
        ->and($matchesByCode['selection.shehai']->marker)->toBe('课')
        ->and($matchesByCode['selection.shehai']->group)->toBe('初传取法')
        ->and($matchesByCode['sanchuan.chong']->description)->toContain('中传取初传之冲神');
});

test('the engine reports stages that have no new rule coverage', function () {
    $pan = new PanResult([
        'tianpan' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 0],
        'shehaiTrace' => null,
        'calculationTrace' => [
            'plate_patterns' => [],
            'initial_transmission' => [
                'recorded' => false,
                'method' => null,
                'evidence' => [],
            ],
            'middle_transmission' => [
                'recorded' => false,
                'method' => null,
                'source' => null,
            ],
            'final_transmission' => [
                'recorded' => false,
                'method' => null,
                'source' => null,
            ],
        ],
    ]);

    $engine = new PanRuleEngine;

    expect($engine->evaluate($pan))->toBe([])
        ->and($engine->coverageNotices($pan))->toBe([
            '初传取法规则尚未覆盖。',
            '中传取法规则尚未覆盖。',
            '末传取法规则尚未覆盖。',
        ]);
});

test('a recorded calculation method remains uncovered until a rule explains it', function () {
    $pan = new PanResult([
        'calculationTrace' => [
            'plate_patterns' => ['fanyin'],
            'initial_transmission' => [
                'recorded' => true,
                'method' => 'future_unimplemented_method',
                'evidence' => [],
            ],
            'middle_transmission' => [
                'recorded' => true,
                'method' => 'future_unimplemented_method',
                'source' => 'branch_upper',
            ],
            'final_transmission' => [
                'recorded' => true,
                'method' => 'future_unimplemented_method',
                'source' => 'stem_upper',
            ],
        ],
    ]);

    $engine = new PanRuleEngine;
    $codes = array_map(fn ($match): string => $match->code, $engine->evaluate($pan));

    expect($codes)->toBe(['plate.fanyin'])
        ->and($engine->coverageNotices($pan))->toBe([
            '初传取法规则尚未覆盖。',
            '中传取法规则尚未覆盖。',
            '末传取法规则尚未覆盖。',
        ]);
});

test('every implemented initial transmission method has an independent rule', function (string $method, string $code, string $name) {
    $pan = new PanResult([
        'calculationTrace' => [
            'plate_patterns' => [],
            'initial_transmission' => [
                'recorded' => true,
                'method' => $method,
                'evidence' => [],
            ],
            'middle_transmission' => ['recorded' => false, 'method' => null, 'source' => null],
            'final_transmission' => ['recorded' => false, 'method' => null, 'source' => null],
        ],
    ]);

    $matches = (new PanRuleEngine)->evaluate($pan);

    expect($matches)->toHaveCount(1)
        ->and($matches[0]->code)->toBe($code)
        ->and($matches[0]->name)->toBe($name);
})->with([
    'yuanshou' => ['yuanshou', 'selection.yuanshou', '元首课'],
    'chongshen' => ['chongshen', 'selection.chongshen', '重审课'],
    'biyong' => ['biyong', 'selection.biyong', '比用课'],
    'zhiyi' => ['zhiyi', 'selection.zhiyi', '知一课'],
    'shehai' => ['shehai', 'selection.shehai', '涉害课'],
    'shehai jianji' => ['shehai_jianji', 'selection.shehai_jianji', '见机格'],
    'shehai chawei' => ['shehai_chawei', 'selection.shehai_chawei', '察微格'],
    'shehai zhuixia' => ['shehai_zhuixia', 'selection.shehai_zhuixia', '缀瑕格'],
    'haoshi' => ['haoshi', 'selection.haoshi', '蒿矢课'],
    'tanshe' => ['tanshe', 'selection.tanshe', '弹射课'],
    'hushi' => ['hushi', 'selection.hushi', '虎视课'],
    'dongshe yanmu' => ['dongshe_yanmu', 'selection.dongshe_yanmu', '冬蛇掩目课'],
    'biezhe' => ['biezhe', 'selection.biezhe', '别责课'],
    'bazhuan' => ['bazhuan', 'selection.bazhuan', '八专课'],
]);
