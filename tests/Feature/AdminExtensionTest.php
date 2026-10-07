<?php

use App\Extensions\AdminExtensionRegistry;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Panel;

final class SampleAdminPage extends Page {}

test('generic admin pages are opt in and deduplicated', function () {
    $registry = new AdminExtensionRegistry;
    expect($registry->pages())->toBe([]);
    $registry->registerPage(Dashboard::class);
    $registry->registerPage(Dashboard::class);
    expect($registry->pages())->toBe([Dashboard::class]);
    expect(fn () => $registry->registerPage(stdClass::class))->toThrow(InvalidArgumentException::class);
    $registry->registerPage(SampleAdminPage::class);
    app()->instance(AdminExtensionRegistry::class, $registry);
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());
    expect($panel->getPages())->toContain(SampleAdminPage::class);
});

test('standalone admin has no plugin pages or domain dependencies', function () {
    expect(app(AdminExtensionRegistry::class)->pages())->toBe([]);
    $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertDontSee('专家系统');
    foreach (['app/Extensions/AdminExtensionRegistry.php', 'app/Providers/Filament/AdminPanelProvider.php'] as $path) {
        $source = file_get_contents(base_path($path));
        foreach (['Prediction', 'Evaluation', 'Artifact', 'Report', 'expert', 'Meixiaoqiu'] as $symbol) {
            expect($source)->not->toContain($symbol);
        }
    }
    foreach (app('router')->getRoutes() as $route) {
        expect($route->uri())->not->toContain('expert');
    }
});
