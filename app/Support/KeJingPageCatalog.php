<?php

namespace App\Support;

/**
 * 文件作用：把 KeJingCatalog 的规则/课例数据整理成课经页面所需的编号、URL、文档来源与分组信息。
 *
 * 课序优先读取 docs/课经/NN-课名.md 的 NN，并按该编号排序；因此页面顺序跟随《六壬大全》研究文档的正式课序，
 * 不依赖 Blade 中的循环位置。KeJingCatalog 仍是课名、卦名、课例与旁证的唯一目录数据源。
 *
 * 是否属于《六壬大全》正文课例的判断一律读取 KeJingCatalog 已写入的结构化 source_type 字段，
 * 不再通过 label / reason / source 等文案临时推断。
 */
final class KeJingPageCatalog
{
    /** @var list<array<string, mixed>> */
    private const ?array NONE = null;

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

    /**
     * 直接读取 KeJingCatalog 已写入的结构化 source_type 字段。
     *
     * 允许值：
     *   - 'daquan'：原文属于《六壬大全》正文课例。
     *   - 其他     ：非正文课例，包括程序验证样本、现代生产盘、古籍旁证等。
     *
     * 该判断在 catalog 层一次性写入，运行期不再通过 label / reason 文案临时推断。
     */
    private static function isDaquanCase(array $case): bool
    {
        return ($case['source_type'] ?? 'other') === 'daquan';
    }

    /**
     * source_examples 的来源字段同样采用结构化判断；保持与 case 的 source_type 命名一致。
     */
    private static function isDaquanSource(array $example): bool
    {
        if (isset($example['source_type'])) {
            return $example['source_type'] === 'daquan';
        }

        // 旧版 source_examples 仅通过 'source' 文案标注；为保留向后兼容，
        // 未带新字段时回退到包含“《六壬大全》”的判断（仅一次性迁移期使用）。
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
