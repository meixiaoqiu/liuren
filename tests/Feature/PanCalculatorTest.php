<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\FuguiRule;
use App\Domain\Pan\Rules\GuanjueRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\ShitaiRule;
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

test('sanguang requires day branch initial transmission to be prosperous and all three places to ride auspicious generals', function () {
    $result = app(PanCalculator::class)->calculate('2000-02-18 11:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.sanguang')
        ->and($matches['lesson.sanguang']->name)->toBe('三光课')
        ->and($matches['lesson.sanguang']->gua)->toBe('贲')
        ->and($matches['lesson.sanguang']->guaSymbol)->toBe('䷕')
        ->and($matches['lesson.sanguang']->evidence)->toMatchArray([
            'month_branch' => 2,
            'day_stem' => 2,
            'day_branch' => 6,
            'initial_transmission' => 6,
            'generals' => [
                'day_upper' => 0,
                'branch_upper' => 11,
                'initial' => 5,
            ],
        ]);
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

test('sanyang requires forward nobleman day and branch riding its five front generals and prosperous initial transmission', function () {
    $result = app(PanCalculator::class)->calculate('2004-04-16 18:00:00');
    $pan = $result->toArray();
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($pan['guirenPeriod'])->toBe('day')
        ->and($pan['sunset'])->toBe('2004-04-16 18:53:26')
        ->and($matches)->toHaveKey('lesson.sanyang')
        ->and($matches['lesson.sanyang']->name)->toBe('三阳课')
        ->and($matches['lesson.sanyang']->gua)->toBe('晋')
        ->and($matches['lesson.sanyang']->guaSymbol)->toBe('䷢')
        ->and($matches['lesson.sanyang']->evidence)->toMatchArray([
            'month_branch' => 4,
            'nobleman_forward' => true,
            'nobleman_ground' => 11,
            'day_stem' => ['stem' => 1, 'lodging_branch' => 4, 'front_general_rank' => 5, 'general' => 5],
            'day_branch' => ['branch' => 1, 'front_general_rank' => 2, 'general' => 2],
            'initial_transmission' => ['branch' => 2, 'element' => 0, 'strength' => '旺'],
        ]);
});

test('sanyang excludes the sixth general behind the nobleman front five', function () {
    $result = app(PanCalculator::class)->calculate('2026-01-12 12:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->not->toHaveKey('lesson.sanyang');
});

test('sanqi matches the daquan standard example and records hai zi chou as linked wonders', function () {
    $result = app(PanCalculator::class)->calculate('2000-05-27 14:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.sanqi')
        ->not->toHaveKey('structure.sanqi_lianzhu')
        ->and($matches['lesson.sanqi']->name)->toBe('三奇课')
        ->and($matches['lesson.sanqi']->gua)->toBe('豫')
        ->and($matches['lesson.sanqi']->guaSymbol)->toBe('䷏')
        ->and($matches['lesson.sanqi']->evidence)->toMatchArray([
            'xun_index' => 2,
            'xun_wonder' => 0,
            'day_wonder' => 5,
            'transmissions' => [11, 0, 1],
            'xun_wonder_positions' => [1],
            'day_wonder_positions' => [],
            'both_wonders_present' => false,
            'three_wonders_linked' => true,
        ]);
});

test('sanqi is not formed by a day wonder without the xun wonder', function () {
    $result = app(PanCalculator::class)->calculate('2000-01-02 13:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->not->toHaveKey('lesson.sanqi');
});

test('liuyi matches the daquan standard example when the xun head branch enters transmissions', function () {
    $result = app(PanCalculator::class)->calculate('2000-06-27 03:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.liuyi')
        ->and($matches['lesson.liuyi']->name)->toBe('六仪课')
        ->and($matches['lesson.liuyi']->gua)->toBe('兑')
        ->and($matches['lesson.liuyi']->guaSymbol)->toBe('䷹')
        ->and($matches['lesson.liuyi']->evidence)->toMatchArray([
            'xun_index' => 5,
            'xun_instrument' => 2,
            'branch_instrument' => 2,
            'transmissions' => [2, 7, 0],
            'xun_instrument_positions' => [0],
            'branch_instrument_positions' => [0],
            'both_instruments_present' => true,
        ]);
});

test('liuyi is not formed by a branch instrument without the xun instrument', function () {
    $result = app(PanCalculator::class)->calculate('2000-01-05 13:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->not->toHaveKey('lesson.liuyi');
});

test('shitai matches the daquan standard example with year month dragon union and calendar wealth virtue', function () {
    $result = app(PanCalculator::class)->calculate('1900-11-01 20:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.shitai')
        ->and($matches['lesson.shitai']->name)->toBe('时泰课')
        ->and($matches['lesson.shitai']->gua)->toBe('泰')
        ->and($matches['lesson.shitai']->guaSymbol)->toBe('䷊')
        ->and($matches['lesson.shitai']->evidence)->toMatchArray([
            'year_branch' => 0,
            'month_branch' => 10,
            'day_stem' => 4,
            'day_virtue' => 5,
            'transmissions' => [0, 5, 10],
            'transmission_generals' => [5, 10, 3],
            'year_positions' => [0],
            'month_positions' => [2],
            'year_is_day_wealth' => true,
            'month_is_day_wealth' => false,
            'year_is_day_virtue' => false,
            'month_is_day_virtue' => false,
            'year_is_initial' => true,
            'month_is_initial' => false,
            'qualifying_calendar_gods' => ['year'],
            'dragon_union_at_edges' => true,
            'dragon_positions' => [0],
            'union_positions' => [2],
        ]);
});

test('shitai allows the year branch to enter after the initial transmission', function () {
    $facts = PanFacts::from(new PanResult([
        'nianzhi' => 0,
        'yuezhi' => 10,
        'rigan' => 4,
        'sanchuan0' => 10,
        'sanchuan1' => 0,
        'sanchuan2' => 5,
        'tianpan' => range(0, 11),
        'tianjiang' => [10, 0, 1, 2, 4, 3, 6, 7, 8, 9, 5, 11],
    ]));

    $match = (new ShitaiRule)->match($facts);

    expect($match)->not->toBeNull()
        ->and($match->evidence)->toMatchArray([
            'transmissions' => [10, 0, 5],
            'transmission_generals' => [5, 10, 3],
            'year_positions' => [1],
            'month_positions' => [0],
            'year_is_initial' => false,
            'month_is_initial' => true,
            'qualifying_calendar_gods' => ['year'],
            'dragon_union_at_edges' => true,
        ]);
});

test('shitai requires the complete combination rather than only a favorable transmission', function () {
    $result = app(PanCalculator::class)->calculate('1900-11-02 20:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->not->toHaveKey('lesson.shitai');
});

test('guanjue matches the daquan standard structure when a calendar horse starts transmissions and kui chang enter', function () {
    $facts = PanFacts::from(new PanResult([
        'nianzhi' => 7,
        'yuezhi' => 3,
        'rizhi' => 11,
        'nianming' => 11,
        'xingnian' => 6,
        'sanchuan0' => 5,
        'sanchuan1' => 10,
        'sanchuan2' => 3,
        'tianpan' => range(0, 11),
        'tianjiang' => [0, 1, 2, 8, 4, 5, 6, 7, 9, 10, 3, 11],
    ]));

    $match = (new GuanjueRule)->match($facts);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('官爵课')
        ->and($match->gua)->toBe('益')
        ->and($match->guaSymbol)->toBe('䷩')
        ->and($match->evidence)->toMatchArray([
            'source_branches' => ['year' => 7, 'month' => 3, 'birth_year' => 11, 'annual_fate' => 6],
            'source_horses' => ['year' => 5, 'month' => 5, 'birth_year' => 5, 'annual_fate' => 8],
            'day_branch' => 11,
            'day_horse' => 5,
            'day_horse_matches_initial' => true,
            'initial_transmission' => 5,
            'matching_horse_sources' => ['year', 'month', 'birth_year'],
            'transmissions' => [5, 10, 3],
            'transmission_generals' => [5, 3, 8],
            'tiankui_positions' => [1],
            'taichang_positions' => [2],
        ]);
});

test('guanjue requires the horse to issue rather than merely enter a later transmission', function () {
    $facts = PanFacts::from(new PanResult([
        'nianzhi' => 7,
        'yuezhi' => 3,
        'rizhi' => 11,
        'sanchuan0' => 10,
        'sanchuan1' => 5,
        'sanchuan2' => 3,
        'tianpan' => range(0, 11),
        'tianjiang' => [0, 1, 2, 8, 4, 5, 6, 7, 9, 10, 3, 11],
    ]));

    expect((new GuanjueRule)->match($facts))->toBeNull();
});

test('guanjue does not allow the day horse alone to form the lesson', function () {
    $facts = PanFacts::from(new PanResult([
        'nianzhi' => 0,
        'yuezhi' => 0,
        'rizhi' => 11,
        'nianming' => 0,
        'xingnian' => 0,
        'sanchuan0' => 5,
        'sanchuan1' => 10,
        'sanchuan2' => 3,
        'tianpan' => range(0, 11),
        'tianjiang' => [0, 1, 2, 8, 4, 5, 6, 7, 9, 10, 3, 11],
    ]));

    expect((new GuanjueRule)->match($facts))->toBeNull();
});

test('guanjue is reproducible from a real calendar input', function () {
    $result = app(PanCalculator::class)->calculate('1986-09-28 03:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.guanjue')
        ->and($matches['lesson.guanjue']->evidence)->toMatchArray([
            'source_branches' => ['year' => 2, 'month' => 9],
            'source_horses' => ['year' => 8, 'month' => 11],
            'day_branch' => 11,
            'day_horse' => 5,
            'day_horse_matches_initial' => false,
            'initial_transmission' => 8,
            'matching_horse_sources' => ['year'],
            'transmissions' => [8, 10, 0],
            'transmission_generals' => [0, 10, 8],
            'tiankui_positions' => [1],
            'taichang_positions' => [2],
        ]);
});

test('derived fate stays fixed across months in the same current year branch', function () {
    $calculator = app(PanCalculator::class);
    $fateCalculator = new FateCalculator;
    $birthBranch = $calculator->calculate('1986-08-01 00:00:00')->get('nianzhi');
    $springBranch = $calculator->calculate('2026-03-01 12:00:00')->get('nianzhi');
    $winterBranch = $calculator->calculate('2026-12-01 12:00:00')->get('nianzhi');

    expect($springBranch)->toBe($winterBranch)
        ->and($fateCalculator->calculate($birthBranch, $springBranch, 'male'))
        ->toBe($fateCalculator->calculate($birthBranch, $winterBranch, 'male'))
        ->toBe(['nianming' => 2, 'xingnian' => 6]);
});

test('fugui requires nobleman to ride the vigorous generating initial over day fate ground', function () {
    $calculator = app(PanCalculator::class);
    $calculated = $calculator->calculate('2025-01-10 08:00:00');
    $birthBranch = $calculator->calculate('1986-08-01 00:00:00')->get('nianzhi');
    $fate = (new FateCalculator)->calculate($birthBranch, $calculated->get('nianzhi'), 'male');
    $facts = PanFacts::from(new PanResult([...$calculated->toArray(), ...$fate]));
    $match = (new FuguiRule)->match($facts);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('富贵课')
        ->and($match->gua)->toBe('大有')
        ->and($match->evidence)->toMatchArray([
            'initial_transmission' => 0,
            'initial_general' => 0,
            'initial_element' => 4,
            'initial_strength' => '旺',
            'ground_branch' => 3,
            'ground_element' => 0,
            'generating_direction' => 'upper_generates_lower',
            'matching_targets' => ['day_branch'],
        ]);
});

test('fugui records horse riding dragon as an enhancement', function () {
    $data = [
        'rigan' => 0,
        'rizhi' => 3,
        'yuezhi' => 11,
        'nianzhi' => 0,
        'sanchuan0' => 0,
        'sanchuan1' => 9,
        'sanchuan2' => 6,
        'tianpan' => [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8],
        'tianjiang' => [1, 2, 3, 0, 4, 5, 6, 7, 8, 9, 10, 11],
    ];
    $match = (new FuguiRule)->match(PanFacts::from(new PanResult($data)));

    expect($match)->not->toBeNull()
        ->and(array_column($match->evidence['modifiers'], 'code'))->toContain('horse_riding_dragon')
        ->and($match->evidence['current_state']['key'])->toBe('auspicious_enhanced');
});

test('fugui keeps nobleman imprisonment before its classical exception', function () {
    $calculator = app(PanCalculator::class);
    $calculated = $calculator->calculate('2026-03-08 06:40:00');
    $birthBranch = $calculator->calculate('1986-08-01 00:00:00')->get('nianzhi');
    $fate = (new FateCalculator)->calculate($birthBranch, $calculated->get('nianzhi'), 'male');
    $match = (new FuguiRule)->match(PanFacts::from(new PanResult([...$calculated->toArray(), ...$fate])));

    expect($match)->not->toBeNull()
        ->and(array_column($match->evidence['modifiers'], 'code'))->toBe([
            'taichang_ribbon',
            'nobleman_in_prison',
            'prison_exception',
        ])
        ->and($match->evidence['current_state']['key'])->toBe('auspicious_enhanced');
});

test('near-current guanjue examples use only automatically derived fate', function (
    string $datetime,
    array $expectedSources,
    array $expectedTransmissions,
) {
    $calculator = app(PanCalculator::class);
    $fateCalculator = new FateCalculator;
    $birthBranch = $calculator->calculate('1986-08-01 00:00:00')->get('nianzhi');
    $calculated = $calculator->calculate($datetime);
    $fate = $fateCalculator->calculate($birthBranch, $calculated->get('nianzhi'), 'male');
    $facts = PanFacts::from(new PanResult([...$calculated->toArray(), ...$fate]));
    $match = (new GuanjueRule)->match($facts);

    expect($fate)->toBe(['nianming' => 2, 'xingnian' => 6])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['matching_horse_sources'])->toBe($expectedSources)
        ->and($match->evidence['transmissions'])->toBe($expectedTransmissions);
})->with([
    'year birth and annual fate horses' => ['2026-09-28 03:00:00', ['year', 'birth_year', 'annual_fate'], [8, 10, 0]],
    'month horse' => ['2026-10-04 07:00:00', ['month'], [11, 10, 7]],
    'all four permitted horses' => ['2026-10-18 03:00:00', ['year', 'month', 'birth_year', 'annual_fate'], [8, 10, 0]],
]);

test('longde requires the year branch to ride nobleman as initial with month general in transmissions', function () {
    $result = app(PanCalculator::class)->calculate('2026-01-19 10:00:00');
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($matches)->toHaveKey('lesson.longde')
        ->and($matches['lesson.longde']->name)->toBe('龙德课')
        ->and($matches['lesson.longde']->gua)->toBe('萃')
        ->and($matches['lesson.longde']->guaSymbol)->toBe('䷬')
        ->and($matches['lesson.longde']->evidence)->toMatchArray([
            'year_branch' => 5,
            'month_general' => 1,
            'transmissions' => [5, 1, 9],
            'initial_general' => 0,
            'month_general_positions' => [1],
            'year_and_month_general_coincide' => false,
        ]);
});

test('longde restores the daquan example by using daylight before the actual Beijing sunset', function () {
    $result = app(PanCalculator::class)->calculate('2013-09-04 18:00:00');
    $pan = $result->toArray();
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([5, 1, 9])
        ->and($pan['guirenPeriod'])->toBe('day')
        ->and($pan['sunrise'])->toBe('2013-09-04 05:44:55')
        ->and($pan['sunset'])->toBe('2013-09-04 18:41:57')
        ->and($pan['sanchuan0tianjiang'])->toBe(0)
        ->and($matches)->toHaveKey('lesson.longde');
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
