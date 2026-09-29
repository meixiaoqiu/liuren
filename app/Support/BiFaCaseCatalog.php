<?php

namespace App\Support;

use App\Extensions\BiFaExtensionRegistry;

/** 文件作用：向页面与排盘适配层提供外部毕法插件贡献的案例。 */
final class BiFaCaseCatalog
{
    /** @return list<array<string, mixed>> */
    public static function cases(): array
    {
        return self::extensionRegistry()?->allCases() ?? [];
    }

    /** @return list<array<string, mixed>> */
    public static function casesForLaw(string $lawCode): array
    {
        return self::extensionRegistry()?->casesForLaw($lawCode) ?? [];
    }

    /** @return array<string, mixed>|null */
    public static function findById(string $caseId): ?array
    {
        foreach (self::cases() as $case) {
            if (($case['case_id'] ?? null) === $caseId) {
                return $case;
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

        return $registry instanceof BiFaExtensionRegistry ? $registry : null;
    }
}
