<?php

namespace App\Providers;

use App\Extensions\BiFaExtensionRegistry;
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
        // 能够拿到同一个 BiFaExtensionRegistry 实例并向其中注入第十法等扩展。
        $this->app->singleton(BiFaExtensionRegistry::class);

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
