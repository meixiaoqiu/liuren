<?php

use App\Extensions\PluginLoader;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->pluginFixtureRoot = storage_path('framework/testing/plugin-loader-'.uniqid());
});

afterEach(function () {
    File::deleteDirectory($this->pluginFixtureRoot);
});

function createPluginFixture(
    string $root,
    string $directory,
    string $id,
    array $providers,
    array $classes = [],
    bool $autoload = true,
    bool $manifest = true,
    mixed $manifestValue = null,
): string {
    $path = $root.DIRECTORY_SEPARATOR.$directory;
    File::ensureDirectoryExists($path);

    if ($autoload) {
        File::ensureDirectoryExists($path.DIRECTORY_SEPARATOR.'vendor');
        File::put(
            $path.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php',
            "<?php\n\nrequire_once ".var_export($path.DIRECTORY_SEPARATOR.'plugin-classes.php', true).";\n",
        );
        File::put(
            $path.DIRECTORY_SEPARATOR.'plugin-classes.php',
            "<?php\n\n".implode("\n\n", $classes)."\n",
        );
    }

    if ($manifest) {
        $value = $manifestValue ?? sprintf(
            <<<'PHP'
new class implements \App\Extensions\LiurenPlugin
{
    public function id(): string
    {
        return %s;
    }

    public function providers(): array
    {
        return %s;
    }
}
PHP,
            var_export($id, true),
            var_export($providers, true),
        );

        File::put(
            $path.DIRECTORY_SEPARATOR.'liuren-plugin.php',
            "<?php\n\nreturn {$value};\n",
        );
    }

    return $path;
}

function providerClass(string $class, string $binding): string
{
    return <<<PHP
class {$class} extends \\Illuminate\\Support\\ServiceProvider
{
    public function register(): void
    {
        \$this->app->singleton('{$binding}', static fn (): bool => true);
    }
}
PHP;
}

test('application starts without configured plugins', function () {
    expect(config('plugins.paths'))->toBe([])
        ->and(app(PluginLoader::class)->loadedPluginIds())->toBe([]);

    $this->get('/')->assertSuccessful();
});

test('configured plugin provider is loaded from its manifest', function () {
    $provider = 'PluginLoaderValidProvider';
    $path = createPluginFixture(
        $this->pluginFixtureRoot,
        'valid-plugin',
        'valid-plugin',
        [$provider],
        [providerClass($provider, 'liuren.valid-plugin.loaded')],
    );
    $loader = new PluginLoader(app());

    expect(class_exists($provider, false))->toBeFalse();

    $loader->load([$path]);

    expect(class_exists($provider, false))->toBeTrue()
        ->and($loader->loadedPluginIds())->toBe(['valid-plugin'])
        ->and(app('liuren.valid-plugin.loaded'))->toBeTrue();
});

test('missing configured plugin path fails with a diagnostic error', function () {
    $path = storage_path('framework/testing/missing-plugin-'.uniqid());
    $loader = new PluginLoader(app());
    config()->set('plugins.paths', [$path]);

    expect(fn () => $loader->loadConfigured())
        ->toThrow(RuntimeException::class, "插件路径不存在或不是目录：{$path}");
});

test('relative plugin paths are rejected', function () {
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load(['../liuren-expert']))
        ->toThrow(RuntimeException::class, '插件路径必须是绝对路径：../liuren-expert');
});

test('plugin without composer autoload fails clearly', function () {
    $path = createPluginFixture($this->pluginFixtureRoot, 'missing-autoload', 'missing-autoload', [], autoload: false);
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '插件缺少 Composer autoload');
});

test('plugin without manifest fails clearly', function () {
    $path = createPluginFixture($this->pluginFixtureRoot, 'missing-manifest', 'missing-manifest', [], manifest: false);
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '插件缺少入口清单');
});

test('manifest must return a LiurenPlugin instance', function () {
    $path = createPluginFixture($this->pluginFixtureRoot, 'invalid-manifest', 'invalid-manifest', [], manifestValue: '[]');
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '插件入口必须返回 LiurenPlugin 实例');
});

test('plugin id must not be empty', function () {
    $path = createPluginFixture($this->pluginFixtureRoot, 'empty-id', '   ', []);
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '插件标识不能为空');
});

test('duplicate plugin ids are rejected after the first plugin loads', function () {
    $first = createPluginFixture($this->pluginFixtureRoot, 'duplicate-a', 'duplicate', []);
    $second = createPluginFixture($this->pluginFixtureRoot, 'duplicate-b', 'duplicate', []);
    $loader = new PluginLoader(app());

    $loader->load([$first]);

    expect(fn () => $loader->load([$second]))
        ->toThrow(RuntimeException::class, '插件标识重复：duplicate')
        ->and($loader->loadedPluginIds())->toBe(['duplicate']);
});

test('provider class must exist', function () {
    $path = createPluginFixture($this->pluginFixtureRoot, 'missing-provider', 'missing-provider', ['Does\\Not\\Exist']);
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '声明了无法加载的服务提供者');
});

test('provider class must extend Laravel ServiceProvider', function () {
    $path = createPluginFixture(
        $this->pluginFixtureRoot,
        'invalid-provider-type',
        'invalid-provider-type',
        ['PluginLoaderInvalidProvider'],
        ['class PluginLoaderInvalidProvider {}'],
    );
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '不是 Laravel 服务提供者');
});

test('multiple plugins load in configured order', function () {
    $first = createPluginFixture(
        $this->pluginFixtureRoot,
        'plugin-a',
        'plugin-a',
        ['PluginLoaderProviderA'],
        [providerClass('PluginLoaderProviderA', 'liuren.plugin-a.loaded')],
    );
    $second = createPluginFixture(
        $this->pluginFixtureRoot,
        'plugin-b',
        'plugin-b',
        ['PluginLoaderProviderB'],
        [providerClass('PluginLoaderProviderB', 'liuren.plugin-b.loaded')],
    );
    $loader = new PluginLoader(app());

    $loader->load([$first, $second]);

    expect($loader->loadedPluginIds())->toBe(['plugin-a', 'plugin-b'])
        ->and(app('liuren.plugin-a.loaded'))->toBeTrue()
        ->and(app('liuren.plugin-b.loaded'))->toBeTrue();
});

test('all providers are validated before any provider is registered', function () {
    $path = createPluginFixture(
        $this->pluginFixtureRoot,
        'atomic-failure',
        'atomic-failure',
        ['PluginLoaderAtomicValidProvider', 'PluginLoaderAtomicInvalidProvider'],
        [
            providerClass('PluginLoaderAtomicValidProvider', 'liuren.atomic-provider.registered'),
            'class PluginLoaderAtomicInvalidProvider {}',
        ],
    );
    $loader = new PluginLoader(app());

    expect(fn () => $loader->load([$path]))
        ->toThrow(RuntimeException::class, '不是 Laravel 服务提供者')
        ->and(app()->bound('liuren.atomic-provider.registered'))->toBeFalse()
        ->and($loader->loadedPluginIds())->toBe([]);
});
