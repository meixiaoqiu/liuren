<?php

namespace App\Extensions;

use Filament\Pages\Page;
use LogicException;

/** 保存受信任插件按注册顺序贡献的后台页面。 */
final class AdminExtensionRegistry
{
    /** @var list<class-string<Page>> */
    private array $pages = [];

    public function registerPage(string $page): void
    {
        if (trim($page) === '' || ! class_exists($page) || ! is_subclass_of($page, Page::class)) {
            throw new LogicException('后台扩展必须注册有效的页面类。');
        }

        // 类名不区分大小写；别名也必须视为同一个页面。
        $reflection = new \ReflectionClass($page);
        if (! $reflection->isInstantiable()) {
            throw new LogicException('后台扩展必须注册可实例化的页面类。');
        }

        $page = $reflection->getName();

        if (in_array($page, $this->pages, true)) {
            throw new LogicException('后台扩展页面不得重复注册。');
        }

        $this->pages[] = $page;
    }

    /** @return list<class-string<Page>> */
    public function pages(): array
    {
        return $this->pages;
    }
}
