<?php

namespace App\Support;

use App\Extensions\BiFaExtensionRegistry;

/**
 * 文件作用：在 BiFaCatalog 目录之上补充毕法详情页所需的排盘辅助数据。
 *
 * BiFaCatalog 仅维护 100 法的最小目录项（法号、code、slug、name、summary）；
 * BiFaPageCatalog 负责把每一法的：
 *
 *  - markdown 研究文档路径与 GitHub URL
 *  - 已研究 / 未研究标记
 *  - 前后法导航
 *  - 案例按 executable / reference_only / generated 分组
 *
 * 等页面层所需数据一并整理出来。BiFaPageCatalog 仍然不依赖 KeJingCatalog，
 * 不复用课经任何 helper。
 *
 * 研究文档可由 BiFaExtensionRegistry 注入：当插件贡献研究文档时，path 可指向仓库外绝对路径；
 * 公开宿主只读取其 path，按 AbsolutePath 规则决定是否补 base_path 前缀。
 */
final class BiFaPageCatalog
{
    /**
     * @return list<array{
        number: int,
        name: string,
        code: string,
        slug: string,
        summary: string,
        researchFilename: string,
        researchPath: string,
        researchUrl: string,
        researched: bool,
        daquanCases: list<array<string, mixed>>,
        generatedCases: list<array<string, mixed>>,
        referenceOnlyCases: list<array<string, mixed>>
     * }>
     */
    public static function laws(): array
    {
        $pages = [];

        foreach (BiFaCatalog::laws() as $law) {
            $filename = sprintf('%02d-%s.md', $law['number'], $law['name']);
            $document = self::extensionRegistry()?->researchDocument($law['number']);
            $resolvedFilename = $document['filename'] ?? $filename;
            $resolvedPath = $document['path'] ?? '';
            $resolvedUrl = $document['research_url'] ?? null;

            $cases = BiFaCaseCatalog::casesForLaw($law['code']);

            $pages[] = [
                ...$law,
                'researchFilename' => $resolvedFilename,
                'researchPath' => $resolvedPath,
                'researchUrl' => $resolvedUrl ?? '',
                'researched' => $document !== null,
                'daquanCases' => array_values(array_filter(
                    $cases,
                    static fn (array $case): bool => $case['source_type'] === 'daquan'
                        && $case['status'] === 'executable',
                )),
                'referenceOnlyCases' => array_values(array_filter(
                    $cases,
                    static fn (array $case): bool => $case['source_type'] === 'daquan'
                        && $case['status'] === 'reference_only',
                )),
                'generatedCases' => array_values(array_filter(
                    $cases,
                    static fn (array $case): bool => $case['source_type'] === 'generated',
                )),
            ];
        }

        return $pages;
    }

    /**
     * @return array{
     *     number: int,
     *     name: string,
     *     code: string,
     *     slug: string,
     *     summary: string,
     *     researchFilename: string,
     *     researchPath: string,
     *     researchUrl: string,
     *     researched: bool,
     *     daquanCases: list<array<string, mixed>>,
     *     generatedCases: list<array<string, mixed>>,
     *     referenceOnlyCases: list<array<string, mixed>>
     * }|null
     */
    public static function findBySlug(string $slug): ?array
    {
        foreach (self::laws() as $law) {
            if ($law['slug'] === $slug) {
                return $law;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     number: int,
     *     name: string,
     *     code: string,
     *     slug: string,
     *     summary: string,
     *     researchFilename: string,
     *     researchPath: string,
     *     researchUrl: string,
     *     researched: bool,
     *     daquanCases: list<array<string, mixed>>,
     *     generatedCases: list<array<string, mixed>>,
     *     referenceOnlyCases: list<array<string, mixed>>
     * }|null
     */
    public static function findByCode(string $code): ?array
    {
        foreach (self::laws() as $law) {
            if ($law['code'] === $code) {
                return $law;
            }
        }

        return null;
    }

    private static function extensionRegistry(): ?BiFaExtensionRegistry
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            $registry = app(BiFaExtensionRegistry::class);
        } catch (\Throwable) {
            return null;
        }

        if (! $registry instanceof BiFaExtensionRegistry) {
            return null;
        }

        return $registry;
    }
}
