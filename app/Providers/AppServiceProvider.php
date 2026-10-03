<?php

namespace App\Providers;

use App\Extensions\BiFaExtensionRegistry;
use App\Extensions\KeJingExtensionRegistry;
use App\Extensions\PanResultExtensionRegistry;
use App\Extensions\PanSidebarExtensionRegistry;
use App\Extensions\PluginLoader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 单例必须在 PluginLoader 加载插件之前实例化——
        // ServiceProvider::register() 的执行顺序保证后续插件的 ServiceProvider
        // 在自己的 register() 阶段即可拿到同一个 BiFaExtensionRegistry 实例并 contribute()；
        // loadConfigured() 返回时所有插件的 register() 已全部跑完，无需 boot() 延后注入。
        $this->app->singleton(BiFaExtensionRegistry::class);
        $this->app->singleton(KeJingExtensionRegistry::class);
        $this->app->singleton(PanResultExtensionRegistry::class);
        $this->app->singleton(PanSidebarExtensionRegistry::class);

        $loader = new PluginLoader($this->app);

        $this->app->instance(PluginLoader::class, $loader);

        $loader->loadConfigured();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
