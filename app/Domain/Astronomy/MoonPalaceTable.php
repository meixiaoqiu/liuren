<?php

namespace App\Domain\Astronomy;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonException;
use OutOfRangeException;
use RuntimeException;

/**
 * 查询离线生成的月宿十二宫交宫事件表。
 *
 * 本类只负责 UTC 时间、世纪分片和二分查找，不包含天文算法，也不访问进程或网络。
 */
final class MoonPalaceTable implements MoonPalaceLookup
{
    /** @var array<string, mixed>|null */
    private ?array $manifest = null;

    /** @var array<string, array{start_ms: int, end_ms_exclusive: int, start_palace_index: int, boundaries: list<array{0: int, 1: int}>}> */
    private array $shards = [];

    public function __construct(private readonly ?string $dataDirectory = null)
    {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException('月宿交宫表需要64位 PHP 整数支持 Unix 毫秒。');
        }
    }

    public function palaceAt(DateTimeInterface $time): int
    {
        $manifest = $this->manifest();
        $utc = DateTimeImmutable::createFromInterface($time)->setTimezone(new DateTimeZone('UTC'));
        $timestamp = ((int) $utc->format('U') * 1000) + intdiv((int) $utc->format('u'), 1000);
        $start = $this->isoTimestampMs($manifest['start_utc']);
        $end = $this->isoTimestampMs($manifest['end_utc_exclusive']);

        if ($timestamp < $start || $timestamp >= $end) {
            throw new OutOfRangeException("月宿交宫表仅支持 [{$manifest['start_utc']}, {$manifest['end_utc_exclusive']})。中间值按 UTC 判断。");
        }

        $year = (int) $utc->format('Y');
        $filename = intdiv($year, 100) * 100 .'.json';
        $shard = $this->shard($filename);
        $palace = $shard['start_palace_index'];
        $low = 0;
        $high = count($shard['boundaries']) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            if ($shard['boundaries'][$middle][0] <= $timestamp) {
                $palace = $shard['boundaries'][$middle][1];
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return $palace;
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifest = $this->decodeJson($this->directory().DIRECTORY_SEPARATOR.'manifest.json');
        if (($manifest['schema_version'] ?? null) !== 1) {
            throw new RuntimeException('月宿交宫表仅支持 schema_version 1。');
        }
        foreach (['start_utc', 'end_utc_exclusive', 'shards'] as $field) {
            if (! array_key_exists($field, $manifest)) {
                throw new RuntimeException("月宿交宫表 manifest 缺少字段：{$field}");
            }
        }

        return $this->manifest = $manifest;
    }

    /** @return array{start_ms: int, end_ms_exclusive: int, start_palace_index: int, boundaries: list<array{0: int, 1: int}>} */
    private function shard(string $filename): array
    {
        if (isset($this->shards[$filename])) {
            return $this->shards[$filename];
        }
        if (! in_array($filename, $this->manifest()['shards'], true)) {
            throw new RuntimeException("月宿交宫表 manifest 未登记分片：{$filename}");
        }

        $shard = $this->decodeJson($this->directory().DIRECTORY_SEPARATOR.$filename);
        foreach (['start_ms', 'end_ms_exclusive', 'start_palace_index', 'boundaries'] as $field) {
            if (! array_key_exists($field, $shard)) {
                throw new RuntimeException("月宿交宫表分片 {$filename} 缺少字段：{$field}");
            }
        }

        return $this->shards[$filename] = $shard;
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $path): array
    {
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("无法读取月宿交宫表：{$path}");
        }

        try {
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("月宿交宫表 JSON 无效：{$path}", previous: $exception);
        }
        if (! is_array($decoded)) {
            throw new RuntimeException("月宿交宫表根节点必须是对象：{$path}");
        }

        return $decoded;
    }

    private function directory(): string
    {
        return $this->dataDirectory ?? dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'astronomy'.DIRECTORY_SEPARATOR.'moon-palace';
    }

    private function isoTimestampMs(mixed $value): int
    {
        if (! is_string($value)) {
            throw new RuntimeException('月宿交宫表范围字段必须是 ISO 时间字符串。');
        }
        try {
            $time = new DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw new RuntimeException("月宿交宫表范围时间无效：{$value}", previous: $exception);
        }

        return ((int) $time->format('U') * 1000) + intdiv((int) $time->format('u'), 1000);
    }
}
