<?php

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
    'shehai jianji' => ['2000-01-11 11:00:00', 'shehai_jianji'],
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
    'fanyin wuqin uses the prescribed order' => [
        '2000-01-14 13:00:00',
        ['plate.fanyin', 'selection.fanyin_wuqin'],
    ],
]);

test('duzu is an additional structure match rather than an alternative to bazhuan', function () {
    $result = app(PanCalculator::class)->calculate('2000-05-01 13:00:00');
    $codes = array_map(
        fn ($match): string => $match->code,
        app(PanRuleEngine::class)->evaluate($result),
    );

    expect($codes)
        ->toContain('selection.bazhuan')
        ->toContain('structure.duzu');
});

test('remaining legacy lesson types have independent rule matches', function (string $datetime, int $legacyType, string $ruleCode, string $name) {
    $result = app(PanCalculator::class)->calculate($datetime);
    $matches = collect(app(PanRuleEngine::class)->evaluate($result))->keyBy('code');

    expect($result->get('jiuzongmen'))->toBe($legacyType)
        ->and($matches)->toHaveKey($ruleCode)
        ->and($matches[$ruleCode]->name)->toBe($name);
})->with([
    'fuyin buyu' => ['2000-07-06 13:00:00', 16, 'lesson.fuyin_buyu', '不虞课'],
    'fuyin ziren' => ['2000-07-05 13:00:00', 17, 'lesson.fuyin_ziren', '自任课'],
    'fuyin zixin' => ['2000-07-08 13:00:00', 18, 'lesson.fuyin_zixin', '自信课'],
    'fuyin duzhuan' => ['2000-07-13 13:00:00', 19, 'lesson.fuyin_duzhuan', '杜传课'],
    'fanyin wuyi' => ['2000-01-07 13:00:00', 20, 'lesson.fanyin_wuyi', '无依课'],
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

test('fanyin wuyi remains compatible with its actual initial selection rule', function () {
    $result = app(PanCalculator::class)->calculate('2000-01-07 13:00:00');
    $codes = array_map(
        fn ($match): string => $match->code,
        app(PanRuleEngine::class)->evaluate($result),
    );

    expect($codes)
        ->toContain('plate.fanyin')
        ->toContain('lesson.fanyin_wuyi')
        ->not->toContain('selection.shehai')
        ->toContain('sanchuan.chong');
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
