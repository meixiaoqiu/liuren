<?php

namespace App\Providers\Filament;

use App\Extensions\AdminExtensionRegistry;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LogicException;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        $hostPages = $panel->getPages();
        $extensionPages = app(AdminExtensionRegistry::class)->pages();
        foreach ($extensionPages as $page) {
            // 缓存列表已经合并过插件页面，不将其再次视为宿主贡献。
            if (! $panel->hasCachedComponents() && in_array($page, $hostPages, true)) {
                throw new LogicException('admin_extension_route_conflict');
            }
        }
        $panel->pages($extensionPages);

        $paths = $names = [];
        foreach ($panel->getPages() as $page) {
            $path = $this->pageRouteIdentity($page, $panel);
            $name = $page::getRouteName($panel);
            if (isset($paths[$path]) || isset($names[$name])) {
                throw new LogicException('admin_extension_route_conflict');
            }
            $paths[$path] = $names[$name] = true;
        }

        return $panel;
    }

    /** 按路由分组语义拼接前缀和路径，统一首尾及重复分隔符。 */
    private function pageRouteIdentity(string $page, Panel $panel): string
    {
        $prefix = trim($page::prependClusterSlug($panel, ''), '/');
        $path = trim($page::getRoutePath($panel), '/');

        $identity = trim(preg_replace('~/+~', '/', $prefix.'/'.$path), '/');

        return $identity === '' ? '/' : $identity;
    }
}
