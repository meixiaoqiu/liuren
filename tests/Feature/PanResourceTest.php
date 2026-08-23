<?php

use App\Filament\Resources\PanResource;
use App\Models\Pan;
use App\Support\PanCreationData;

function panCreationFixtures(): array
{
    return json_decode(
        file_get_contents(base_path('tests/Fixtures/pan_creation_hashes.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

test('pan creation data matches the pre-upgrade golden output', function () {
    foreach (panCreationFixtures() as $case) {
        PanResource::qipan($case['input']);

        $record = PanCreationData::fromCalculatedPan(session('pan'), $case['input']);
        expect($record['sizhu'], $case['input'])->toBe($case['sizhu'])
            ->and($record['jiuzongmen'], $case['input'])->toBe($case['jiuzongmen'])
            ->and(PanCreationData::stableHash($record), $case['input'])->toBe($case['record_hash']);
    }
});

test('pan creation data can be persisted with the same field values', function () {
    $case = panCreationFixtures()[9];

    PanResource::qipan($case['input']);
    $record = PanCreationData::fromCalculatedPan(session('pan'), $case['input']);

    Pan::create($record);

    $stored = Pan::query()->firstOrFail()->only(array_keys($record));

    expect($stored)
        ->toMatchArray($record)
        ->and(PanCreationData::stableHash($stored))->toBe($case['record_hash']);
});
