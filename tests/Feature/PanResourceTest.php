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

test('month general over hour has exactly twelve plate arrangements', function () {
    $arrangements = [];

    foreach (range(0, 11) as $yuejiang) {
        foreach (range(0, 22, 2) as $hour) {
            $datetime = sprintf('2026-01-01 %02d:00:00', $hour);
            $tianpan = [];

            foreach (range(0, 11) as $index) {
                $tianpan[] = PanResource::yuejiangJiashi($yuejiang, $datetime, $index);
            }

            $pointer = ($yuejiang - PanResource::$hour2Shichen[$hour] + 12) % 12;

            expect($tianpan[0])->toBe($pointer)
                ->and($tianpan)->toBe(
                    array_map(fn (int $index): int => ($pointer + $index) % 12, range(0, 11)),
                );

            $arrangements[$pointer] = $tianpan;
        }
    }

    ksort($arrangements);

    expect(array_keys($arrangements))->toBe(range(0, 11))
        ->and($arrangements)->toHaveCount(12);
});

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
