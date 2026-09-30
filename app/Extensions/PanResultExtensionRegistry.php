<?php

namespace App\Extensions;

use LogicException;

/**
 * 保存插件在 register 阶段贡献的排盘结果扩展视图。
 *
 * 本注册表只存储可序列化的标量配置，不扫描目录，也不感知任何具体插件。
 */
final class PanResultExtensionRegistry
{
    /** @var array<string, array{key: string, view: string, order: int, sequence: int}> */
    private array $extensions = [];

    private int $nextSequence = 0;

    public function register(string $key, string $view, int $order = 100): void
    {
        $key = trim($key);
        $view = trim($view);

        if ($key === '') {
            throw new LogicException('Pan result extension key must not be empty.');
        }

        if ($view === '') {
            throw new LogicException('Pan result extension view must not be empty: '.$key);
        }

        if (array_key_exists($key, $this->extensions)) {
            throw new LogicException('Duplicate pan result extension key: '.$key);
        }

        $this->extensions[$key] = [
            'key' => $key,
            'view' => $view,
            'order' => $order,
            'sequence' => $this->nextSequence++,
        ];
    }

    /** @return list<array{key: string, view: string, order: int}> */
    public function all(): array
    {
        $extensions = array_values($this->extensions);

        usort(
            $extensions,
            static fn (array $left, array $right): int => [$left['order'], $left['sequence']] <=> [$right['order'], $right['sequence']],
        );

        return array_map(
            static fn (array $extension): array => [
                'key' => $extension['key'],
                'view' => $extension['view'],
                'order' => $extension['order'],
            ],
            $extensions,
        );
    }
}
