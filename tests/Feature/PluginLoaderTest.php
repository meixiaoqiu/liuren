<?php

use App\Extensions\PluginLoader;

test('application starts without configured plugins', function () {
    expect(config('plugins.paths'))->toBe([])
        ->and(app(PluginLoader::class)->loadedPluginIds())->toBe([]);

    $this->get('/')->assertSuccessful();
});

test('configured plugin provider is loaded from its manifest', function () {
    $loader = new PluginLoader(app());
    config()->set('plugins.paths', [base_path('tests/Fixtures/FakePlugin')]);

    $loader->loadConfigured();

    expect($loader->loadedPluginIds())->toBe(['fake-plugin'])
        ->and(app('liuren.fake-plugin.loaded'))->toBeTrue();
});

test('missing configured plugin path fails with a diagnostic error', function () {
    $path = base_path('tests/Fixtures/missing-plugin');
    $loader = new PluginLoader(app());
    config()->set('plugins.paths', [$path]);

    expect(fn () => $loader->loadConfigured())
        ->toThrow(RuntimeException::class, "插件路径不存在或不是目录：{$path}");
});
