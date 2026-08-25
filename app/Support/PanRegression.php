<?php

namespace App\Support;

use App\Filament\Resources\PanResource;
use RuntimeException;

class PanRegression
{
    public const CASE_COUNT = 1440;

    public const FIXTURE_PATH = 'tests/Fixtures/pan_regression_1440.json';

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
    ];

    public static function caseId(array $pan): string
    {
        $dayIndex = array_search(
            [$pan['rigan'], $pan['rizhi']],
            PanResource::$jiazi2Ganzhi,
            true,
        );

        if ($dayIndex === false) {
            throw new RuntimeException('Unable to resolve the sexagenary day index.');
        }

        $period = in_array($pan['shizhi'], [3, 4, 5, 6, 7, 8], true) ? 'day' : 'night';

        return sprintf('pointer-%02d_day-%02d_%s', $pan['tianpan'][0], $dayIndex, $period);
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
