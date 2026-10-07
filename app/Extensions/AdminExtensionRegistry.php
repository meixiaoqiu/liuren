<?php

namespace App\Extensions;

use Filament\Pages\Page;
use InvalidArgumentException;

final class AdminExtensionRegistry
{
    /** @var array<class-string<Page>, class-string<Page>> */
    private array $pages = [];

    public function registerPage(string $page): void
    {
        if (! is_subclass_of($page, Page::class)) {
            throw new InvalidArgumentException('后台扩展必须是后台页面。');
        }
        $this->pages[$page] = $page;
    }

    /** @return list<class-string<Page>> */
    public function pages(): array
    {
        return array_values($this->pages);
    }
}
