<?php

namespace App\Support;

/**
 * 文件作用：把 KeJingCatalog 的规则/课例数据整理成课经页面所需的编号、URL、文档来源与分组信息。
 *
 * 课序优先读取 docs/课经/NN-课名.md 的 NN，并按该编号排序；因此页面顺序跟随《六壬大全》研究文档的正式课序，
 * 不依赖 Blade 中的循环位置。KeJingCatalog 仍是课名、卦名、课例与旁证的唯一目录数据源。
 */
final class KeJingPageCatalog
{
    /** @return list<array<string, mixed>> */
    public static function lessons(): array
    {
        static $pages = null;
        if ($pages !== null) {
            return $pages;
        }

        $documents = self::researchDocuments();
        $pages = [];

        foreach (array_values(KeJingCatalog::lessons()) as $index => $lesson) {
            $document = $documents[$lesson['name']] ?? null;
            $number = $document['number'] ?? (11 + $index);
            $filename = $document['filename'] ?? sprintf('%02d-%s.md', $number, $lesson['name']);
            $researchPath = $document['path'] ?? 'docs/课经/'.$filename;
            $cases = $lesson['cases'] ?? [];
            $sourceExamples = $lesson['source_examples'] ?? [];

            $pages[] = [
                ...$lesson,
                'number' => $number,
                'slug' => self::slugFromCode($lesson['code']),
                'researchFilename' => $filename,
                'researchPath' => $researchPath,
                'researchUrl' => 'https://github.com/meixiaoqiu/liuren/blob/master/docs/%E8%AF%BE%E7%BB%8F/'.rawurlencode($filename),
                'daquanCases' => array_values(array_filter($cases, self::isDaquanCase(...))),
                'otherCases' => array_values(array_filter($cases, static fn (array $case): bool => ! self::isDaquanCase($case))),
                'daquanExamples' => array_values(array_filter($sourceExamples, self::isDaquanSource(...))),
                'otherExamples' => array_values(array_filter($sourceExamples, static fn (array $example): bool => ! self::isDaquanSource($example))),
            ];
        }

        usort(
            $pages,
            static fn (array $left, array $right): int => [$left['number'], $left['name']] <=> [$right['number'], $right['name']],
        );

        return $pages;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::lessons() as $lesson) {
            if ($lesson['slug'] === $slug) {
                return $lesson;
            }
        }

        return null;
    }

    public static function findByCode(string $code): ?array
    {
        foreach (self::lessons() as $lesson) {
            if ($lesson['code'] === $code) {
                return $lesson;
            }
        }

        return null;
    }

    public static function slugFromCode(string $code): string
    {
        $raw = str_starts_with($code, 'lesson.') ? substr($code, strlen('lesson.')) : $code;

        return str_replace('_', '-', $raw);
    }

    private static function isDaquanCase(array $case): bool
    {
        $text = ($case['label'] ?? '').' '.($case['reason'] ?? '');

        if (str_contains($text, '非正文')) {
            return false;
        }

        return str_contains($text, '《六壬大全》') || str_contains($text, '正文');
    }

    private static function isDaquanSource(array $example): bool
    {
        return str_contains((string) ($example['source'] ?? ''), '六壬大全');
    }

    /** @return array<string, array{number: int, filename: string, path: string}> */
    private static function researchDocuments(): array
    {
        $documents = [];

        foreach (glob(base_path('docs/课经/*.md')) ?: [] as $path) {
            $filename = basename($path);
            if (preg_match('/^(\d+)-(.+)\.md$/u', $filename, $matches) !== 1) {
                continue;
            }

            $documents[$matches[2]] = [
                'number' => (int) $matches[1],
                'filename' => $filename,
                'path' => 'docs/课经/'.$filename,
            ];
        }

        return $documents;
    }
}
