<?php

namespace App\Support;

use App\Services\PanCalculator;
use RuntimeException;

class PanRegression
{
    public const CASE_COUNT = 720;

    public const FIXTURE_PATH = 'tests/Fixtures/pan_regression_720.json';

    public const EXCLUDED_FIELDS = [
        'shichen',
        'sizhu',
        'yuejiang',
        'niangan',
        'nianzhi',
        'yuegan',
        'yuezhi',
        'rigan',
        'rizhi',
        'shigan',
        'shizhi',
        'sanchuan0tianjiang',
        'sanchuan1tianjiang',
        'sanchuan2tianjiang',
        'tianjiang0',
        'tianjiang1',
        'tianjiang2',
        'tianjiang3',
        'tianjiang4',
        'tianjiang5',
        'tianjiang6',
        'tianjiang7',
        'tianjiang8',
        'tianjiang9',
        'tianjiang10',
        'tianjiang11',
    ];

    public static function caseId(array $pan): string
    {
        $dayIndex = array_search(
            [$pan['rigan'], $pan['rizhi']],
            PanCalculator::$jiazi2Ganzhi,
            true,
        );

        if ($dayIndex === false) {
            throw new RuntimeException('Unable to resolve the sexagenary day index.');
        }

        return sprintf('pointer-%02d_day-%02d', $pan['tianpan'][0], $dayIndex);
    }

    public static function normalize(array $record): array
    {
        $normalized = array_diff_key($record, array_flip(self::EXCLUDED_FIELDS));
        ksort($normalized);

        return $normalized;
    }

    public static function fixturePath(): string
    {
        return base_path(self::FIXTURE_PATH);
    }

    public static function loadFixture(): array
    {
        $path = self::fixturePath();

        if (! is_file($path)) {
            throw new RuntimeException("Regression fixture does not exist: {$path}");
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function fieldDifferences(array $expected, array $actual): array
    {
        $differences = [];

        foreach (array_unique([...array_keys($expected), ...array_keys($actual)]) as $field) {
            $before = $expected[$field] ?? null;
            $after = $actual[$field] ?? null;

            if ($before !== $after) {
                $differences[$field] = ['before' => $before, 'after' => $after];
            }
        }

        ksort($differences);

        return $differences;
    }
}
