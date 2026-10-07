<?php

use App\Extensions\AdminExtensionRegistry;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class AdminExtensionPageA extends Page {}
class AdminExtensionPageB extends Page {}
class AdminExtensionPageC extends Page {}
abstract class AbstractAdminExtensionPage extends Page {}
class NonInstantiableAdminExtensionPage extends Page
{
    private function __construct() {}
}
class AdminConflictPageA extends Page
{
    protected static ?string $slug = 'synthetic/conflict';
}
class AdminConflictPageB extends Page
{
    protected static ?string $slug = 'synthetic/conflict';
}
class AdminNameConflictPage extends Page
{
    protected static ?string $slug = 'synthetic.conflict';
}
class AdminDashboardPathConflictPage extends Page
{
    public static function getRoutePath(Panel $panel): string
    {
        return Dashboard::getRoutePath($panel);
    }
}
class AdminDashboardNameConflictPage extends Page
{
    public static function getRelativeRouteName(Panel $panel): string
    {
        return Dashboard::getRelativeRouteName($panel);
    }
}
class SyntheticAdminCluster extends Cluster
{
    protected static ?string $slug = 'synthetic';
}
class SyntheticTrailingAdminCluster extends Cluster
{
    protected static ?string $slug = 'synthetic/';
}
class ClusterAdminConflictPage extends Page
{
    protected static ?string $cluster = SyntheticAdminCluster::class;

    protected static ?string $slug = 'conflict';
}
class TrailingClusterAdminConflictPage extends ClusterAdminConflictPage
{
    protected static ?string $cluster = SyntheticTrailingAdminCluster::class;
}
class FlatAdminConflictPage extends Page
{
    protected static ?string $slug = 'synthetic/conflict';

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'flat-conflict';
    }
}
class SyntheticNameAdminCluster extends Cluster
{
    protected static ?string $slug = 'pages/synthetic';
}
class ClusterAdminFinalNamePage extends ClusterAdminConflictPage
{
    protected static ?string $cluster = SyntheticNameAdminCluster::class;
}
class FlatAdminNameConflictPage extends Page
{
    protected static ?string $slug = 'flat-name-conflict';

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'synthetic.pages.conflict';
    }
}

function actualAdminPageRoute(string $page, Panel $panel): array
{
    $router = app('router');
    $original = $router->getRoutes();
    $router->setRoutes(new RouteCollection);
    try {
        Route::name($panel->generateRouteName(''))->group(fn () => $page::registerRoutes($panel));
        $router->getRoutes()->refreshNameLookups();
        $route = $router->getRoutes()->getByName($page::getRouteName($panel));
        expect($route)->not->toBeNull();

        return ['uri' => $route->uri(), 'name' => $route->getName()];
    } finally {
        $router->setRoutes($original);
    }
}

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
})->with(['', '   ', stdClass::class, 'MissingAdminExtensionPage', Page::class, AbstractAdminExtensionPage::class, NonInstantiableAdminExtensionPage::class]);

it('拒绝同一个页面类再次注册且保留原集合', function () {
    $registry = new AdminExtensionRegistry;
    $registry->registerPage(AdminExtensionPageA::class);
    expect(fn () => $registry->registerPage(AdminExtensionPageA::class))->toThrow(LogicException::class)
        ->and($registry->pages())->toBe([AdminExtensionPageA::class]);
});

it('面板消费时拒绝路径或名称冲突', function (array $pages) {
    foreach ($pages as $page) {
        app(AdminExtensionRegistry::class)->registerPage($page);
    }
    expect(fn () => (new AdminPanelProvider(app()))->panel(Panel::make()))->toThrow(LogicException::class, 'admin_extension_route_conflict');
})->with([
    '同路径' => [[AdminConflictPageA::class, AdminConflictPageB::class]],
    '不同路径同名称' => [[AdminConflictPageA::class, AdminNameConflictPage::class]],
    '宿主首页路径' => [[AdminDashboardPathConflictPage::class]],
    '宿主首页名称' => [[AdminDashboardNameConflictPage::class]],
    '重复宿主页面' => [[Dashboard::class]],
]);

it('实际宿主发现页面也参与冲突校验', function () {
    $originalAppPath = app_path();
    $root = storage_path('app/private/dev/admin-discovery-'.uniqid());
    $class = 'DiscoveredAdminFixture'.str_replace('.', '', uniqid('', true));
    $directory = $root.'/Filament/Pages';
    File::ensureDirectoryExists($directory);
    $file = $directory.'/'.$class.'.php';
    File::put($file, "<?php\nnamespace App\\Filament\\Pages;\nclass {$class} extends \\Filament\\Pages\\Page { protected static ?string \$slug = 'synthetic/conflict'; }\n");
    require $file;
    app(AdminExtensionRegistry::class)->registerPage(AdminConflictPageB::class);
    $panel = Panel::make();
    try {
        app()->useAppPath($root);
        expect(fn () => (new AdminPanelProvider(app()))->panel($panel))->toThrow(LogicException::class, 'admin_extension_route_conflict');
        expect($panel->getPages())->toContain('App\\Filament\\Pages\\'.$class);
    } finally {
        app()->useAppPath($originalAppPath);
        File::deleteDirectory($root);
    }
});

it('插件页面使用面板真实路由并保留认证保护', function () {
    app(AdminExtensionRegistry::class)->registerPage(AdminExtensionPageA::class);
    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());
    app(PanelRegistry::class)->register($panel);
    expect(Filament::getPanel('admin')->getPages())->toContain(AdminExtensionPageA::class);
    require base_path('vendor/filament/filament/routes/web.php');
    app('router')->getRoutes()->refreshNameLookups();
    expect(array_keys(app('router')->getRoutes()->getRoutesByName()))->toContain('filament.admin.pages.'.AdminExtensionPageA::getRelativeRouteName($panel));
    $route = app('router')->getRoutes()->getByName('filament.admin.pages.'.AdminExtensionPageA::getRelativeRouteName($panel));
    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain(Authenticate::class);
    $this->get('/'.$route->uri())->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk()->assertDontSee('AdminExtensionRegistry');
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('无插件时保留后台登录与认证边界', function () {
    expect(app(AdminExtensionRegistry::class)->pages())->toBe([]);
    $this->get('/admin/login')->assertOk()->assertDontSee('AdminExtensionRegistry');
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('集群页面与普通页面的真实路径相同且最终名称不同时仍拒绝', function () {
    $panel = Panel::make()->id('admin');
    $actual = [];
    foreach ([ClusterAdminConflictPage::class, FlatAdminConflictPage::class] as $page) {
        $actual[] = actualAdminPageRoute($page, $panel);
        app(AdminExtensionRegistry::class)->registerPage($page);
    }
    expect($actual[0]['uri'])->toBe('synthetic/conflict')
        ->and($actual[1]['uri'])->toBe('synthetic/conflict')
        ->and($actual[0]['name'])->not->toBe($actual[1]['name']);
    expect(fn () => (new AdminPanelProvider(app()))->panel(Panel::make()))->toThrow(LogicException::class, 'admin_extension_route_conflict');
});

it('集群页面真实路径不同而最终路由名称相同时拒绝', function () {
    $panel = Panel::make()->id('admin');
    $actual = [];
    foreach ([ClusterAdminFinalNamePage::class, FlatAdminNameConflictPage::class] as $page) {
        $actual[] = actualAdminPageRoute($page, $panel);
        app(AdminExtensionRegistry::class)->registerPage($page);
    }
    expect($actual[0]['uri'])->not->toBe($actual[1]['uri'])
        ->and($actual[0]['name'])->toBe($actual[1]['name'])
        ->and($actual[0]['name'])->toBe('filament.admin.pages.synthetic.pages.conflict');
    expect(fn () => (new AdminPanelProvider(app()))->panel(Panel::make()))->toThrow(LogicException::class, 'admin_extension_route_conflict');
});

it('页面路径身份与真实路由分组规范化一致', function (string $page, string $expected) {
    $panel = Panel::make()->id('admin');
    $method = new ReflectionMethod(AdminPanelProvider::class, 'pageRouteIdentity');
    expect($method->invoke(new AdminPanelProvider(app()), $page, $panel))->toBe($expected);
    $actual = actualAdminPageRoute($page, $panel);
    expect($actual['uri'])->toBe($expected);
})->with([
    '集群前缀' => [ClusterAdminConflictPage::class, 'synthetic/conflict'],
    '集群前缀尾分隔符' => [TrailingClusterAdminConflictPage::class, 'synthetic/conflict'],
    '普通页面' => [FlatAdminConflictPage::class, 'synthetic/conflict'],
    '根页面' => [Dashboard::class, '/'],
]);
