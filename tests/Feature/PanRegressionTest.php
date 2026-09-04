<?php

use App\Services\PanCalculator;
use App\Support\PanCreationData;
use App\Support\PanRegression;

test('all 720 normalized core pan cases match their approved baseline', function () {
    $calculator = app(PanCalculator::class);
    $fixture = PanRegression::loadFixture();
    $expectedCaseIds = [];

    foreach (range(0, 11) as $pointer) {
        foreach (range(0, 59) as $dayIndex) {
            $expectedCaseIds[] = sprintf('pointer-%02d_day-%02d', $pointer, $dayIndex);
        }
    }

    sort($expectedCaseIds);
    $actualCaseIds = array_keys($fixture['cases']);
    sort($actualCaseIds);

    expect($fixture['version'])->toBe(2)
        ->and($fixture['case_count'])->toBe(PanRegression::CASE_COUNT)
        ->and($fixture['excluded_fields'])->toBe(PanRegression::EXCLUDED_FIELDS)
        ->and($actualCaseIds)->toBe($expectedCaseIds)
        ->and(array_column($fixture['cases'], 'input'))->each->toBeString();

    $changedCases = [];

    foreach ($fixture['cases'] as $caseId => $case) {
        $pan = $calculator->calculate($case['input'])->toArray();
        $record = PanCreationData::fromCalculatedPan($pan, $case['input']);
        $actual = PanRegression::normalize($record);
        $differences = PanRegression::fieldDifferences($case['expected'], $actual);

        if (PanRegression::caseId($pan) !== $caseId) {
            $changedCases[$caseId]['case_id'] = [
                'before' => $caseId,
                'after' => PanRegression::caseId($pan),
            ];
        }

        if ($differences !== []) {
            $changedCases[$caseId] = [
                ...($changedCases[$caseId] ?? []),
                ...$differences,
            ];
        }
    }

    if ($changedCases !== []) {
        $lines = [sprintf('%d unapproved pan cases changed:', count($changedCases))];

        foreach (array_slice($changedCases, 0, 50, true) as $caseId => $differences) {
            $lines[] = $caseId;

            foreach ($differences as $field => $change) {
                $lines[] = sprintf(
                    '  %s: %s -> %s',
                    $field,
                    json_encode($change['before'], JSON_UNESCAPED_UNICODE),
                    json_encode($change['after'], JSON_UNESCAPED_UNICODE),
                );
            }
        }

        if (count($changedCases) > 50) {
            $lines[] = sprintf('... and %d more changed cases.', count($changedCases) - 50);
        }

        throw new RuntimeException(implode(PHP_EOL, $lines));
    }

    expect($changedCases)->toBeEmpty();
});
