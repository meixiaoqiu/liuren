<?php

namespace App\Support;

/**
 * 将毕法规则与案例的内部数据转换为用户可见数据。
 *
 * 模板只消费本类输出的中文名称、说明、状态和链接，不直接渲染规则 code、
 * 案例 ID、内部状态或数据字段名。
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
        $routeNames = [];
        foreach ($match['sub_matches'] ?? [] as $subMatch) {
            $routeNames[(string) ($subMatch['code'] ?? '')] = (string) ($subMatch['title'] ?? '');
        }

        return [
            'number' => (int) ($match['number'] ?? 0),
            'name' => (string) ($match['name'] ?? ''),
            'summary' => (string) ($match['summary'] ?? ''),
            'matched' => (bool) ($match['matched'] ?? false),
            'matched_count' => count($match['matched_routes'] ?? []),
            'sub_matches' => array_values(array_map(
                static fn (array $subMatch): array => [
                    'title' => (string) ($subMatch['title'] ?? ''),
                    'description' => (string) ($subMatch['description'] ?? ''),
                    'detail' => $subMatch['detail'] ?? null,
                    'matched' => (bool) ($subMatch['matched'] ?? false),
                    'needs_people' => ! empty($subMatch['people_missing'])
                        && ! empty($subMatch['requires_people']),
                ],
                $match['sub_matches'] ?? [],
            )),
            'related_cases' => self::cases($match['related_cases'] ?? [], $routeNames),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cases
     * @param  array<string, string>  $routeNames
     * @return list<array<string, mixed>>
     */
    public static function cases(array $cases, array $routeNames): array
    {
        return array_values(array_map(
            static fn (array $case): array => self::case($case, $routeNames),
            $cases,
        ));
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  array<string, string>  $routeNames
     * @return array<string, mixed>
     */
    private static function case(array $case, array $routeNames): array
    {
        $isExecutable = ($case['status'] ?? '') === 'executable';
        $isGenerated = ($case['source_type'] ?? '') === 'generated';
        $names = array_values(array_filter(array_map(
            static fn (string $route): string => $routeNames[$route] ?? '',
            array_map('strval', $case['routes'] ?? []),
        )));

        $description = $isGenerated
            ? ($names === []
                ? '本案例用于说明本法不成立且无需继续评估的情形。'
                : '本案例用于说明“'.implode('、', $names).'”的成立或边界情形。')
            : (string) ($case['reason'] ?? '');

        $url = null;
        if ($isExecutable) {
            $params = $case['link_params'] ?? [
                'datetime' => $case['datetime'] ?? null,
                'birth' => $case['birth'] ?? null,
                'gender' => $case['gender'] ?? null,
            ];
            if (! empty($case['people'])) {
                $params['people'] = $case['people'];
            }
            $url = route('pan.create', array_filter($params, static fn ($value): bool => $value !== null && $value !== ''));
        }

        return [
            'label' => (string) ($case['label'] ?? ''),
            'description' => $description,
            'source_label' => $isGenerated ? '现代程序验证案例' : (string) ($case['source'] ?? '《六壬大全》正文案例'),
            'status_label' => $isExecutable ? '可查看排盘' : '原文参考盘 · 尚未完整复现',
            'status_tone' => $isExecutable ? 'success' : 'warning',
            'url' => $url,
        ];
    }
}
