<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Filament\Resources\PanResource;
use App\Services\PanCalculator;

test('calculator returns a pan without depending on the filament session adapter', function () {
    $datetime = '2024-08-11 14:00:28';

    session()->forget('pan');

    $calculated = app(PanCalculator::class)->calculate($datetime)->toArray();

    expect($calculated)
        ->toHaveKeys([
            'sizhu',
            'yuejiang',
            'sike',
            'sanchuan0',
            'sanchuan1',
            'sanchuan2',
            'tianpan',
            'tianjiang',
            'jiuzongmen',
        ])
        ->and(session()->has('pan'))->toBeFalse();
});

test('nobleman switches at the actual Beijing sunrise and sunset using fixed UTC plus eight', function () {
    $calculator = app(PanCalculator::class);
    $beforeSunset = $calculator->calculate('2013-09-04 18:41:56')->toArray();
    $atSunset = $calculator->calculate('2013-09-04 18:41:57')->toArray();

    expect($beforeSunset['sunrise'])->toBe('2013-09-04 05:44:55')
        ->and($beforeSunset['sunset'])->toBe('2013-09-04 18:41:57')
        ->and($beforeSunset['guirenPeriod'])->toBe('day')
        ->and($atSunset['guirenPeriod'])->toBe('night');
});

test('filament adapter stores the calculator result in the session', function () {
    $datetime = '2024-08-11 14:00:28';
    $calculated = app(PanCalculator::class)->calculate($datetime)->toArray();

    $adapted = PanResource::qipan($datetime);

    expect($adapted)->toBe($calculated)
        ->and(session('pan'))->toBe($calculated);
});

test('calculator explains each transmission method without changing the pan', function () {
    $pan = app(PanCalculator::class)
        ->calculate('2000-01-07 13:00:00')
        ->toArray();

    expect($pan['calculationTrace']['plate_patterns'])->toBe(['fanyin'])
        ->and($pan['calculationTrace']['initial_transmission']['method'])->toBe('shehai')
        ->and($pan['calculationTrace']['initial_transmission']['evidence']['decision']['selected_branch'])->toBe($pan['sanchuan0'])
        ->and($pan['calculationTrace']['middle_transmission'])->toBe([
            'recorded' => true,
            'method' => 'chong',
            'source' => 'initial_transmission',
        ])
        ->and($pan['calculationTrace']['final_transmission'])->toBe([
            'recorded' => true,
            'method' => 'chong',
            'source' => 'middle_transmission',
        ]);
});

test('calculator records the initial method at the branch that selected it', function (string $datetime, string $method) {
    $result = app(PanCalculator::class)->calculate($datetime);
    $pan = $result->toArray();
    $codes = array_map(
        fn ($match): string => $match->code,
        app(PanRuleEngine::class)->evaluate($result),
    );
    $expectedRuleCode = in_array($method, ['biyong', 'zhiyi'], true)
        ? 'selection.zhiyi'
        : 'selection.'.$method;

    expect($pan['calculationTrace']['initial_transmission']['recorded'])->toBeTrue()
        ->and($pan['calculationTrace']['initial_transmission']['method'])->toBe($method)
        ->and($codes)->toContain($expectedRuleCode);
})->with([
    'yuanshou' => ['2000-05-07 15:00:00', 'yuanshou'],
    'chongshen' => ['2000-05-06 15:00:00', 'chongshen'],
    'biyong' => ['2000-05-16 15:00:00', 'biyong'],
    'zhiyi' => ['2000-05-18 15:00:00', 'zhiyi'],
    'shehai' => ['2000-05-09 15:00:00', 'shehai'],
    'shehai zhuixia day' => ['2000-01-11 11:00:00', 'shehai_zhuixia'],
    'shehai zhuixia night' => ['2000-05-10 03:00:00', 'shehai_zhuixia'],
    'shehai chawei' => ['2000-03-13 13:00:00', 'shehai_chawei'],
    'haoshi' => ['2000-05-22 13:00:00', 'haoshi'],
    'tanshe' => ['2000-06-07 13:00:00', 'tanshe'],
    'hushi' => ['2000-05-12 15:00:00', 'hushi'],
    'dongshe yanmu' => ['2000-05-11 15:00:00', 'dongshe_yanmu'],
    'biezhe' => ['2000-05-10 15:00:00', 'biezhe'],
    'bazhuan' => ['2000-05-01 15:00:00', 'bazhuan'],
]);

test('second batch rules explain their actual middle and final transmission methods', function (string $datetime, array $expectedCodes) {
    $result = app(PanCalculator::class)->calculate($datetime);
    $engine = app(PanRuleEngine::class);
    $codes = array_map(fn ($match): string => $match->code, $engine->evaluate($result));

    expect($codes)->toContain(...$expectedCodes)
        ->and($engine->coverageNotices($result))->toBe([]);

    if (str_starts_with($expectedCodes[1], 'sanchuan.')) {
        expect(collect($engine->evaluate($result))->firstWhere('code', $expectedCodes[1])->marker)->toBe('传');
    }
})->with([
    'haoshi follows the heaven plate' => [
        '2000-05-22 13:00:00',
        ['selection.haoshi', 'sanchuan.tianpan_shunchuan'],
    ],
    'tanshe follows the heaven plate' => [
        '2000-06-07 13:00:00',
        ['selection.tanshe', 'sanchuan.tianpan_shunchuan'],
    ],
    'hushi uses its prescribed order' => [
        '2000-05-12 15:00:00',
        ['selection.hushi', 'sanchuan.hushi'],
    ],
    'dongsheyanmu uses its prescribed order' => [
        '2000-05-11 15:00:00',
        ['selection.dongshe_yanmu', 'sanchuan.dongshe_yanmu'],
    ],
    'biezhe repeats the stem upper' => [
        '2000-05-10 15:00:00',
        ['selection.biezhe', 'sanchuan.gan_shangshen'],
    ],
    'bazhuan repeats the stem upper' => [
        '2000-05-01 15:00:00',
        ['selection.bazhuan', 'sanchuan.gan_shangshen'],
    ],
    'fanyin jinglan uses the prescribed order' => [
        '2000-01-14 13:00:00',
        ['plate.fanyin', 'structure.jinglan'],
    ],
]);

test('duzu and weibu buxiu are additional grid matches rather than alternatives to bazhuan', function () {
    $result = app(PanCalculator::class)->calculate('2000-05-01 13:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKeys(['selection.bazhuan', 'structure.duzu', 'structure.weibu_buxiu'])
        ->and($matches['structure.duzu']->marker)->toBe('格')
        ->and($matches['structure.weibu_buxiu']->marker)->toBe('格')
        ->and($matches['structure.weibu_buxiu']->evidence['matched_generals'])->toBe([
            ['transmission' => 0, 'general' => 3, 'name' => '六合'],
            ['transmission' => 1, 'general' => 3, 'name' => '六合'],
            ['transmission' => 2, 'general' => 3, 'name' => '六合'],
        ]);
});

test('remaining legacy lesson types have independent rule matches', function (string $datetime, int $legacyType, string $ruleCode, string $name) {
    $result = app(PanCalculator::class)->calculate($datetime);
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($result->get('jiuzongmen'))->toBe($legacyType)
        ->and($matches)->toHaveKey($ruleCode)
        ->and($matches[$ruleCode]->name)->toBe($name);
})->with([
    'fuyin with overcoming' => ['2000-07-06 13:00:00', 16, 'plate.fuyin', '伏吟课'],
    'fuyin ziren' => ['2000-07-05 13:00:00', 17, 'lesson.fuyin_ziren', '自任格'],
    'fuyin zixin' => ['2000-07-08 13:00:00', 18, 'lesson.fuyin_zixin', '自信格'],
    'fuyin duzhuan' => ['2000-07-13 13:00:00', 19, 'lesson.fuyin_duzhuan', '杜传格'],
    'ordinary fanyin' => ['2000-01-07 13:00:00', 20, 'plate.fanyin', '返吟课'],
]);

test('every fuyin lesson rule explains all three transmission stages', function (string $datetime, string $pattern) {
    $result = app(PanCalculator::class)->calculate($datetime);
    $trace = $result->get('calculationTrace');

    expect($trace['lesson_patterns'])->toBe([$pattern])
        ->and($trace['initial_transmission']['recorded'])->toBeTrue()
        ->and($trace['middle_transmission']['recorded'])->toBeTrue()
        ->and($trace['final_transmission']['recorded'])->toBeTrue()
        ->and(app(PanRuleEngine::class)->coverageNotices($result))->toBe([]);
})->with([
    'buyu' => ['2000-07-06 13:00:00', 'fuyin_buyu'],
    'ziren' => ['2000-07-05 13:00:00', 'fuyin_ziren'],
    'zixin' => ['2000-07-08 13:00:00', 'fuyin_zixin'],
    'duzhuan' => ['2000-07-13 13:00:00', 'fuyin_duzhuan'],
]);

test('ordinary fuyin with overcoming is not presented as an additional buyu classification', function () {
    $result = app(PanCalculator::class)->calculate('2000-07-06 13:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result));

    expect($matches->pluck('code')->all())->toContain('plate.fuyin')
        ->and($matches->pluck('code')->all())->not->toContain('lesson.fuyin_buyu');
});

test('ordinary fanyin is not presented as an additional wuyi classification', function () {
    $result = app(PanCalculator::class)->calculate('2000-01-07 13:00:00');
    $codes = array_map(
        fn ($match): string => $match->code,
        app(PanRuleEngine::class)->evaluate($result),
    );

    expect($codes)
        ->toContain('plate.fanyin')
        ->not->toContain('lesson.fanyin_wuyi')
        ->not->toContain('selection.shehai')
        ->toContain('sanchuan.chong');
});

test('fanyin without overcoming is additionally classified as jinglan grid', function () {
    $result = app(PanCalculator::class)->calculate('2000-01-14 13:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKeys(['plate.fanyin', 'structure.jinglan'])
        ->and($matches['structure.jinglan']->marker)->toBe('格')
        ->and($matches['structure.jinglan']->name)->toBe('井栏格（无亲格）');
});

test('seasonal strength changes to earth eighteen exact days before each four-li term', function (string $before, string $boundary, string $fourLi) {
    $calculator = app(PanCalculator::class);
    $beforePeriod = PanFacts::from($calculator->calculate($before))->seasonalPeriod();
    $boundaryPeriod = PanFacts::from($calculator->calculate($boundary))->seasonalPeriod();

    expect($beforePeriod['key'])->not->toBe('soil')
        ->and($boundaryPeriod)->toMatchArray([
            'key' => 'soil',
            'name' => '四季土旺',
            'wang' => 2,
            'xiang' => 3,
            'starts_at' => $boundary,
            'ends_at' => $fourLi,
        ]);
})->with([
    'before 2000 start of spring' => ['2000-01-17 20:40:23', '2000-01-17 20:40:24', '2000-02-04 20:40:24'],
    'before 2000 start of summer' => ['2000-04-17 12:50:09', '2000-04-17 12:50:10', '2000-05-05 12:50:10'],
    'before 2000 start of autumn' => ['2000-07-20 13:02:58', '2000-07-20 13:02:59', '2000-08-07 13:02:59'],
    'before 2000 start of winter' => ['2000-10-20 10:48:03', '2000-10-20 10:48:04', '2000-11-07 10:48:04'],
]);

test('complete seasonal states cover all five states in spring and the earth-prosperous period', function () {
    $calculator = app(PanCalculator::class);
    $spring = PanFacts::from($calculator->calculate('2026-02-28 11:00:00'));
    $soil = PanFacts::from($calculator->calculate('2000-01-17 20:40:24'));

    expect([
        $spring->branchSeasonalState(2), // 寅木
        $spring->branchSeasonalState(5), // 巳火
        $spring->branchSeasonalState(0), // 子水
        $spring->branchSeasonalState(8), // 申金
        $spring->branchSeasonalState(4), // 辰土
    ])->toBe(['旺', '相', '休', '囚', '死'])
        ->and([
            $spring->stemSeasonalState(0),
            $spring->stemSeasonalState(2),
            $spring->stemSeasonalState(8),
            $spring->stemSeasonalState(6),
            $spring->stemSeasonalState(4),
        ])->toBe(['旺', '相', '休', '囚', '死'])
        ->and([
            $soil->branchSeasonalState(4), // 土旺
            $soil->branchSeasonalState(8), // 金相
            $soil->branchSeasonalState(5), // 火休
            $soil->branchSeasonalState(2), // 木囚
            $soil->branchSeasonalState(0), // 水死
        ])->toBe(['旺', '相', '休', '囚', '死']);
});

test('derived fate stays fixed across months in the same current year branch', function () {
    $calculator = app(PanCalculator::class);
    $fateCalculator = new FateCalculator;
    $birthIndex = $calculator->calculate('1986-08-01 00:00:00')->get('nian_index');
    $springIndex = $calculator->calculate('2026-03-01 12:00:00')->get('nian_index');
    $winterIndex = $calculator->calculate('2026-12-01 12:00:00')->get('nian_index');

    expect($springIndex)->toBe($winterIndex)
        ->and($fateCalculator->calculate($birthIndex, $springIndex, 'male'))
        ->toBe($fateCalculator->calculate($birthIndex, $winterIndex, 'male'))
        ->toBe(['nianming' => 2, 'xingnian' => 6, 'xingnian_gan' => 2]);
});

test('fuyin zixin breaks the zi mao punishment loop with wu', function (string $datetime) {
    $pan = app(PanCalculator::class)->calculate($datetime)->toArray();

    expect([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])
        ->toBe([3, 0, 6])
        ->and($pan['calculationTrace']['initial_transmission']['recorded'])->toBeTrue()
        ->and($pan['calculationTrace']['lesson_patterns'])->toBe(['fuyin_zixin']);
})->with([
    'pointer-00_day-03_day' => '2000-07-08 13:00:00',
    'pointer-00_day-03_night' => '2000-01-10 01:00:00',
    'pointer-00_day-15_day' => '2000-05-21 15:00:00',
    'pointer-00_day-15_night' => '2000-01-22 00:00:00',
    'pointer-00_day-27_day' => '2000-06-02 15:00:00',
    'pointer-00_day-27_night' => '2000-02-03 00:00:00',
]);
