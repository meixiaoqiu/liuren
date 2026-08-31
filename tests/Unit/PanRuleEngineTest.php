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
        ->toContain('sanchuan.chong')
        ->not->toContain('selection.shehai')
        ->and($matches)->toHaveCount(2);

    $matchesByCode = [];

    foreach ($matches as $match) {
        $matchesByCode[$match->code] = $match;
    }

    expect($matchesByCode['sanchuan.chong']->description)->toContain('中传取初传之冲神');
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
    $matchesByCode = collect($matches)->keyBy('code');
    $match = $matchesByCode[$code];
    $expectedCount = str_starts_with($method, 'shehai_') || in_array($method, ['haoshi', 'tanshe', 'hushi', 'dongshe_yanmu'], true) ? 2 : 1;

    expect($matches)->toHaveCount($expectedCount)
        ->and($matchesByCode)->toHaveKey($code)
        ->and($match->name)->toBe($name);

    if ($code === 'selection.yuanshou') {
        expect($match->gua)->toBe('乾')
            ->and($match->guaSymbol)->toBe('䷀')
            ->and($match->xiang)->toStartWith('天地得位，品物咸新。')
            ->and($match->xiang)->toEndWith('门庭喜溢，利见大人。');
    }

    if ($code === 'selection.chongshen') {
        expect($match->gua)->toBe('坤')
            ->and($match->guaSymbol)->toBe('䷁');
    }

    if ($code === 'selection.zhiyi') {
        expect($match->gua)->toBe('比')
            ->and($match->guaSymbol)->toBe('䷇');
    }

    if ($code === 'selection.shehai') {
        expect($match->gua)->toBe('坎')
            ->and($match->guaSymbol)->toBe('䷜')
            ->and($match->xiang)->toBe('风波险恶，度涉艰难。谋为利名，多费机关。婚姻有阻，疾病难安。胎孕迟滞，行人未还。');
    }

    if (str_starts_with($code, 'selection.shehai_')) {
        expect($match->marker)->toBe('格')
            ->and($matchesByCode)->toHaveKey('selection.shehai');
    }

    if (in_array($code, ['selection.haoshi', 'selection.tanshe'], true)) {
        expect($match->marker)->toBe('格')
            ->and($matchesByCode)->toHaveKey('selection.yaoke')
            ->and($matchesByCode['selection.yaoke']->gua)->toBe('睽')
            ->and($matchesByCode['selection.yaoke']->guaSymbol)->toBe('䷥');
    }

    if (in_array($code, ['selection.hushi', 'selection.dongshe_yanmu'], true)) {
        expect($match->marker)->toBe('格')
            ->and($matchesByCode)->toHaveKey('selection.maoxing')
            ->and($matchesByCode['selection.maoxing']->gua)->toBe('履')
            ->and($matchesByCode['selection.maoxing']->guaSymbol)->toBe('䷉');
    }

    if ($code === 'selection.biezhe') {
        expect($match->gua)->toBeNull()
            ->and($match->guaSymbol)->toBeNull();
    }

    if ($code === 'selection.bazhuan') {
        expect($match->gua)->toBe('同人')
            ->and($match->guaSymbol)->toBe('䷌');
    }
})->with([
    'yuanshou' => ['yuanshou', 'selection.yuanshou', '元首课'],
    'chongshen' => ['chongshen', 'selection.chongshen', '重审课'],
    'biyong belongs to zhiyi lesson' => ['biyong', 'selection.zhiyi', '知一课'],
    'zhiyi' => ['zhiyi', 'selection.zhiyi', '知一课'],
    'shehai' => ['shehai', 'selection.shehai', '涉害课'],
    'shehai jianji' => ['shehai_jianji', 'selection.shehai_jianji', '见机格'],
    'shehai chawei' => ['shehai_chawei', 'selection.shehai_chawei', '察微格'],
    'shehai zhuixia' => ['shehai_zhuixia', 'selection.shehai_zhuixia', '缀瑕格'],
    'haoshi' => ['haoshi', 'selection.haoshi', '蒿矢格'],
    'tanshe' => ['tanshe', 'selection.tanshe', '弹射格'],
    'hushi' => ['hushi', 'selection.hushi', '虎视格'],
    'dongshe yanmu' => ['dongshe_yanmu', 'selection.dongshe_yanmu', '冬蛇掩目格'],
    'biezhe' => ['biezhe', 'selection.biezhe', '别责课'],
    'bazhuan' => ['bazhuan', 'selection.bazhuan', '八专课'],
]);
