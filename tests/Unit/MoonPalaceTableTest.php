<?php

use App\Domain\Astronomy\MoonPalaceTable;

function moonPalaceDateFromMilliseconds(int $milliseconds): DateTimeImmutable
{
    $seconds = intdiv($milliseconds, 1000);
    $remainder = $milliseconds % 1000;
    if ($remainder < 0) {
        $seconds--;
        $remainder += 1000;
    }

    return DateTimeImmutable::createFromFormat('!U.u', sprintf('%d.%06d', $seconds, $remainder * 1000))
        ->setTimezone(new DateTimeZone('UTC'));
}

test('PHP 使用全部520个冻结 JS 样本恢复相同宫位', function () {
    $fixture = json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/moon_palace_table_samples.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $table = new MoonPalaceTable;

    foreach ($fixture['samples'] as [$timestamp, $expected]) {
        expect($table->palaceAt(moonPalaceDateFromMilliseconds($timestamp)))
            ->toBe($expected, "Unix毫秒 {$timestamp} 的月宿宫位不一致");
    }
});

test('PHP 查询覆盖全部世纪起止样本', function () {
    $table = new MoonPalaceTable;

    foreach (range(1600, 2400, 100) as $year) {
        expect($table->palaceAt(new DateTimeImmutable("{$year}-01-01T00:00:00.000Z")))->toBeInt();
        expect($table->palaceAt(new DateTimeImmutable(($year + 99).'-12-31T23:59:59.999Z')))->toBeInt();
    }
});

test('范围第一毫秒有效且结束时刻排除', function () {
    $table = new MoonPalaceTable;

    expect($table->palaceAt(new DateTimeImmutable('1600-01-01T00:00:00.000Z')))->toBeInt()
        ->and($table->palaceAt(new DateTimeImmutable('2499-12-31T23:59:59.999Z')))->toBeInt();

    expect(fn () => $table->palaceAt(new DateTimeImmutable('1599-12-31T23:59:59.999Z')))
        ->toThrow(OutOfRangeException::class, '月宿交宫表仅支持')
        ->and(fn () => $table->palaceAt(new DateTimeImmutable('2500-01-01T00:00:00.000Z')))
        ->toThrow(OutOfRangeException::class, '月宿交宫表仅支持');
});

test('相同瞬间不受传入时区影响', function () {
    $table = new MoonPalaceTable;
    $utc = new DateTimeImmutable('2026-09-13T06:33:09.000Z');
    $shanghai = $utc->setTimezone(new DateTimeZone('Asia/Shanghai'));

    expect($table->palaceAt($utc))->toBe($table->palaceAt($shanghai));
});

test('交宫瞬间遵守前一毫秒旧宫和当毫秒新宫', function () {
    $data = json_decode(
        file_get_contents(dirname(__DIR__, 2).'/resources/astronomy/moon-palace/2000.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $target = (new DateTimeImmutable('2026-09-13T06:33:09.128Z'))->format('Uv');
    $boundary = collect($data['boundaries'])->sortBy(fn (array $item): int => abs($item[0] - (int) $target))->first();
    $table = new MoonPalaceTable;

    expect(abs($boundary[0] - (int) $target))->toBeLessThanOrEqual(100)
        ->and($table->palaceAt(moonPalaceDateFromMilliseconds($boundary[0] - 1)))->toBe(5)
        ->and($table->palaceAt(moonPalaceDateFromMilliseconds($boundary[0])))->toBe(4);
});

test('PHP 拒绝不支持的 manifest schema', function () {
    $directory = sys_get_temp_dir().'/moon-palace-schema-'.bin2hex(random_bytes(8));
    mkdir($directory);
    file_put_contents($directory.'/manifest.json', json_encode([
        'schema_version' => 2,
        'start_utc' => '1600-01-01T00:00:00Z',
        'end_utc_exclusive' => '2500-01-01T00:00:00Z',
        'shards' => [],
    ], JSON_THROW_ON_ERROR));

    try {
        expect(fn () => (new MoonPalaceTable($directory))->palaceAt(new DateTimeImmutable('2026-01-01T00:00:00Z')))
            ->toThrow(RuntimeException::class, 'schema_version 1');
    } finally {
        unlink($directory.'/manifest.json');
        rmdir($directory);
    }
});
