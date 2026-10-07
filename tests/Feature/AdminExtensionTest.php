<?php

use App\Extensions\AdminExtensionRegistry;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Panel;

class AdminExtensionPageA extends Page {}
class AdminExtensionPageB extends Page {}
class AdminExtensionPageC extends Page {}

it('提供单例且没有插件时为空', function () {
    expect(config('plugins.paths'))->toBe([])
        ->and(app(AdminExtensionRegistry::class))->toBe(app(AdminExtensionRegistry::class))
        ->and(app(AdminExtensionRegistry::class)->pages())->toBe([]);
});

it('按贡献顺序保存页面并由后台面板消费', function () {
    $registry = app(AdminExtensionRegistry::class);
    foreach ([AdminExtensionPageA::class, AdminExtensionPageB::class, AdminExtensionPageC::class] as $page) {
        $registry->registerPage($page);
    }
    expect($registry->pages())->toBe([AdminExtensionPageA::class, AdminExtensionPageB::class, AdminExtensionPageC::class]);
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());
    expect($panel->getPages())->toContain(Dashboard::class, AdminExtensionPageA::class, AdminExtensionPageB::class, AdminExtensionPageC::class);
});

it('拒绝重复页面包括不同大小写类名', function () {
    $registry = new AdminExtensionRegistry;
    $registry->registerPage(AdminExtensionPageA::class);
    expect(fn () => $registry->registerPage(strtolower(AdminExtensionPageA::class)))->toThrow(LogicException::class)
        ->and($registry->pages())->toBe([AdminExtensionPageA::class]);
});

it('拒绝非法页面且不改变集合', function (string $page) {
    $registry = new AdminExtensionRegistry;
    expect(fn () => $registry->registerPage($page))->toThrow(LogicException::class)
        ->and($registry->pages())->toBe([]);
})->with(['', '   ', stdClass::class, 'MissingAdminExtensionPage', Page::class]);

it('无插件时保留后台登录与认证边界', function () {
    expect(app(AdminExtensionRegistry::class)->pages())->toBe([]);
    $this->get('/admin/login')->assertOk()->assertDontSee('AdminExtensionRegistry');
    $this->get('/admin')->assertRedirect('/admin/login');
});
