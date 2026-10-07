<?php

namespace App\Extensions;

use LogicException;

/**
 * 保存插件在 register 阶段贡献的起课区侧栏扩展视图。
 *
 * 视图在排盘页左侧 aside 内部按 order 升序 + sequence 升序遍历。
 *
 * 与 PanResultExtensionRegistry（排盘结果页右侧）平行：
 *  - 本注册表只覆盖"起课区侧栏"挂载点；
 *  - 插件可以在 register() 阶段同时注册到两个注册表；
 *  - 宿主不感知任何具体插件名，只读取已经注册的可序列化标量配置。
 */
final class PanSidebarExtensionRegistry
{
    /** @var array<string, array{key: string, view: string, order: int, sequence: int}> */
    private array $extensions = [];

    private int $nextSequence = 0;

    public function register(string $key, string $view, int $order = 100): void
    {
        $key = trim($key);
        $view = trim($view);

        if ($key === '') {
            throw new LogicException('Pan sidebar extension key must not be empty.');
        }

        if ($view === '') {
            throw new LogicException('Pan sidebar extension view must not be empty: '.$key);
        }

        if (array_key_exists($key, $this->extensions)) {
            throw new LogicException('Duplicate pan sidebar extension key: '.$key);
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
