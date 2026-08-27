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

    $adapted = PanResource::qipan($datetime);

    expect($adapted)->toBe($calculated)
        ->and(session('pan'))->toBe($calculated);
});
