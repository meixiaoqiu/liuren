<?php

namespace App\Support;

use App\Support\Knowledge\BiFaKnowledgeCardFactory;

/**
 * @deprecated 旧展示入口；新代码应直接使用 BiFaKnowledgeCardFactory。
 *
 * 本类保留已有静态调用兼容性，内部委托统一知识卡片工厂完成转换。
 */
final class BiFaPresenter
{
    /**
     * @param  array<string, mixed>  $definition
     * @return array{description: string, foundations: list<array{title: string, description: string}>}
     */
    public static function definition(array $definition): array
    {
        return [
            'description' => (string) ($definition['description'] ?? ''),
            'foundations' => array_values(array_map(
                static fn (array $foundation): array => [
                    'title' => (string) ($foundation['title'] ?? ''),
                    'description' => (string) ($foundation['description'] ?? ''),
                ],
                $definition['foundations'] ?? [],
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $match
     * @return array<string, mixed>
     */
    public static function match(array $match): array
    {
        return app(BiFaKnowledgeCardFactory::class)->fromLegacyMatch($match)->toArray();
    }

    /**
     * @param  list<array<string, mixed>>  $cases
     * @param  array<string, string>  $routeNames
     * @return list<array<string, mixed>>
     */
    public static function cases(array $cases, array $routeNames): array
    {
        return app(BiFaKnowledgeCardFactory::class)->examples($cases, $routeNames);
    }
}
