<?php

namespace App\Providers;

use App\Extensions\PluginLoader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
