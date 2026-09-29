<?php

namespace App\Support;

use App\Extensions\KeJingExtensionRegistry;

/**
 * 文件作用：把公开课经身份和扩展贡献整理成页面所需的编号、文档来源与分组信息。
 *
 * 是否属于《六壬大全》正文课例的判断一律读取扩展贡献写入的结构化 source_type 字段，
 * 不再通过 label / reason / source 等文案临时推断。
 */
final class KeJingPageCatalog
{
    /** @var list<array<string, mixed>> */
    private const ?array NONE = null;

    /** @return list<array<string, mixed>> */
    public static function lessons(): array
    {
        $pages = [];

        foreach (KeJingCatalog::lessons() as $lesson) {
            $number = $lesson['number'];
            $extensionDocument = self::extensionRegistry()?->researchDocument($number);
            $filename = $extensionDocument['filename'] ?? sprintf('%02d-%s.md', $number, $lesson['name']);
            $researchPath = $extensionDocument['path'] ?? '';
            $researchUrl = $extensionDocument['research_url'] ?? '';
            $cases = $lesson['cases'] ?? [];
            $sourceExamples = $lesson['source_examples'] ?? [];

            $pages[] = [
                ...$lesson,
                'number' => $number,
                'slug' => self::slugFromCode($lesson['code']),
                'researchFilename' => $filename,
                'researchPath' => $researchPath,
                'researchUrl' => $researchUrl,
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
     * source_examples 只读取 KeJingCatalog 已结构化的 source_type；不做文案兼容推断。
     */
    private static function isDaquanSource(array $example): bool
    {
        return ($example['source_type'] ?? 'other') === 'daquan';
    }

    private static function extensionRegistry(): ?KeJingExtensionRegistry
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $registry = app(KeJingExtensionRegistry::class);
        } catch (\Throwable) {
            return null;
        }

        return $registry instanceof KeJingExtensionRegistry ? $registry : null;
    }
}
