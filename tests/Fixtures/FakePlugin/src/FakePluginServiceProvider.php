<?php

namespace Tests\Fixtures\FakePlugin;

use Illuminate\Support\ServiceProvider;

class FakePluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('liuren.fake-plugin.loaded', static fn (): bool => true);
    }
}
