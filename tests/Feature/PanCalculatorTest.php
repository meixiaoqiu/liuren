<?php

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

test('fuyin zixin breaks the zi mao punishment loop with wu', function (string $datetime) {
    $pan = app(PanCalculator::class)->calculate($datetime)->toArray();

    expect([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])
        ->toBe([3, 0, 6]);
})->with([
    'pointer-00_day-03_day' => '2000-07-08 13:00:00',
    'pointer-00_day-03_night' => '2000-01-10 01:00:00',
    'pointer-00_day-15_day' => '2000-05-21 15:00:00',
    'pointer-00_day-15_night' => '2000-01-22 00:00:00',
    'pointer-00_day-27_day' => '2000-06-02 15:00:00',
    'pointer-00_day-27_night' => '2000-02-03 00:00:00',
]);
