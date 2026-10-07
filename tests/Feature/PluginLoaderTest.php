<?php

use App\Extensions\AdminExtensionRegistry;
use App\Extensions\BiFaExtensionRegistry;
use App\Extensions\KeJingExtensionRegistry;
use App\Extensions\PluginLoader;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
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
    $hostBasePath = base_path();

    expect(getenv('APP_BASE_PATH'))->toBe($hostBasePath)
        ->and($_ENV['APP_BASE_PATH'])->toBe($hostBasePath)
        ->and($_SERVER['APP_BASE_PATH'])->toBe($hostBasePath)
        ->and(class_exists($provider, false))->toBeFalse();

    $loader->load([$path]);

    expect(class_exists($provider, false))->toBeTrue()
        ->and(Application::inferBasePath())->toBe($hostBasePath)
        ->and(getenv('APP_BASE_PATH'))->toBe($hostBasePath)
        ->and($_ENV['APP_BASE_PATH'])->toBe($hostBasePath)
        ->and($_SERVER['APP_BASE_PATH'])->toBe($hostBasePath)
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

    expect(fn () => $loader->load(['../example-plugin']))
        ->toThrow(RuntimeException::class, '插件路径必须是绝对路径：../example-plugin');
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

test('register 阶段贡献：插件 provider 在 register() 中写入 extension registry，loadConfigured 返回时立即可见', function () {
    $providerClass = 'PluginLoaderRegisterPhaseContributor';
    $bindingKey = 'liuren.register-phase-contributor.loaded';
    $extensionCode = 'bifa.test-register-phase';

    $providerClassDefinition = <<<PHP
class {$providerClass} extends \\Illuminate\\Support\\ServiceProvider
{
    public function register(): void
    {
        \$this->app->singleton('{$bindingKey}', static fn (): bool => true);
        \$registry = \$this->app->make(\\App\\Extensions\\BiFaExtensionRegistry::class);
        \$registry->registerSummary('{$extensionCode}', 'register 阶段立即可见的简介');
        \$registry->registerRule(new class implements \\App\\Domain\\Pan\\BiFa\\BiFaRule {
            public function code(): string { return '{$extensionCode}'; }
            public function law(): array { return ['number'=>99,'name'=>'fake','code'=>'{$extensionCode}','slug'=>'fake-rule','summary'=>'']; }
            public function definition(): array { return ['description'=>'','foundations'=>[],'judgments'=>[],'sections'=>[]]; }
            public function match(\\App\\Domain\\Pan\\Facts\\PanFacts \$f): ?\\App\\Domain\\Pan\\BiFa\\BiFaRuleMatch { return null; }
        });
    }
}
PHP;

    $path = createPluginFixture(
        $this->pluginFixtureRoot,
        'register-phase',
        'register-phase-plugin',
        [$providerClass],
        [$providerClassDefinition],
    );

    $loader = new PluginLoader(app());
    $loader->load([$path]);

    expect($loader->loadedPluginIds())->toContain('register-phase-plugin')
        ->and(app($bindingKey))->toBeTrue()
        ->and(app(BiFaExtensionRegistry::class)->summaryFor($extensionCode))
        ->toBe('register 阶段立即可见的简介')
        ->and(collect(app(BiFaExtensionRegistry::class)->rules())
            ->map(static fn ($rule): string => $rule->code())
            ->all())
        ->toContain($extensionCode);
});

test('register 阶段的虚构课经贡献在 plugin load 返回时立即可见', function () {
    $providerClass = 'PluginLoaderKeJingRegisterPhaseContributor';
    $bindingKey = 'liuren.kejing-register-phase.loaded';
    $extensionCode = 'lesson.fake_plugin';

    $providerClassDefinition = <<<PHP
class {$providerClass} extends \\Illuminate\\Support\\ServiceProvider
{
    public function register(): void
    {
        \$this->app->singleton('{$bindingKey}', static fn (): bool => true);
        \$registry = \$this->app->make(\\App\\Extensions\\KeJingExtensionRegistry::class);
        \$registry->registerSummary('{$extensionCode}', '虚构课经插件摘要');
        \$registry->registerRule(new class implements \\App\\Domain\\Pan\\Rules\\PanRule {
            public function code(): string { return '{$extensionCode}'; }
            public function definition(): array { return ['description'=>'虚构','xiang'=>null,'foundations'=>[],'judgments'=>[]]; }
            public function match(\\App\\Domain\\Pan\\Facts\\PanFacts \$facts): ?\\App\\Domain\\Pan\\Rules\\RuleMatch { return null; }
        });
    }
}
PHP;

    $path = createPluginFixture(
        $this->pluginFixtureRoot,
        'kejing-register-phase',
        'kejing-register-phase-plugin',
        [$providerClass],
        [$providerClassDefinition],
    );

    $loader = new PluginLoader(app());
    $loader->load([$path]);

    expect($loader->loadedPluginIds())->toContain('kejing-register-phase-plugin')
        ->and(app($bindingKey))->toBeTrue()
        ->and(app(KeJingExtensionRegistry::class)->summaryFor($extensionCode))->toBe('虚构课经插件摘要')
        ->and(array_map(
            static fn ($rule): string => $rule->code(),
            app(KeJingExtensionRegistry::class)->rules(),
        ))->toContain($extensionCode);
});

test('后台页面沿宿主注册到插件注册再到面板消费的真实链路贡献且不依赖启动阶段', function () {
    $paths = [];
    $providers = [];
    $pages = [];
    foreach (['A', 'B'] as $suffix) {
        $provider = 'AdminLifecycleProvider'.$suffix;
        $page = 'AdminLifecyclePage'.$suffix;
        $providers[] = $provider;
        $pages[] = $page;
        $definition = <<<PHP
class {$page} extends \\Filament\\Pages\\Page {}
class {$provider} extends \\Illuminate\\Support\\ServiceProvider
{
    public static bool \$bootCalled = false;
    public function register(): void
    {
        \$this->app->make(\\App\\Extensions\\AdminExtensionRegistry::class)->registerPage({$page}::class);
    }
    public function boot(): void
    {
        self::\$bootCalled = true;
        throw new \\LogicException('fixture_boot_must_not_be_needed');
    }
}
PHP;
        $paths[] = createPluginFixture($this->pluginFixtureRoot, 'admin-'.strtolower($suffix), 'admin-'.strtolower($suffix), [$provider], [$definition]);
    }

    $host = app();
    try {
        $isolated = new Application($host->basePath());
        $isolated->instance('config', new Repository(['plugins' => ['paths' => $paths]]));
        $isolated->instance('files', $host->make('files'));
        $isolated->register(AppServiceProvider::class);
        $registry = $isolated->make(AdminExtensionRegistry::class);
        expect($isolated->isBooted())->toBeFalse()
            ->and($isolated->make(PluginLoader::class)->loadedPluginIds())->toBe(['admin-a', 'admin-b'])
            ->and($registry->pages())->toBe($pages)
            ->and($providers[0]::$bootCalled)->toBeFalse()
            ->and($providers[1]::$bootCalled)->toBeFalse();
        $panel = (new AdminPanelProvider($isolated))->panel(Panel::make());
        $contributed = array_values(array_filter($panel->getPages(), fn ($page) => in_array($page, $pages, true)));
        expect($contributed)->toBe($pages)
            ->and($isolated->isBooted())->toBeFalse();
    } finally {
        Container::setInstance($host);
    }
});
