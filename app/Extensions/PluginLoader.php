<?php

namespace App\Extensions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class PluginLoader
{
    /** @var list<string> */
    private array $loadedPluginIds = [];

    public function __construct(private readonly Application $app) {}

    public function loadConfigured(): void
    {
        $paths = config('plugins.paths', []);

        if (! is_array($paths)) {
            throw new RuntimeException('插件路径配置必须是数组。');
        }

        $this->load($paths);
    }

    /**
     * @param  array<int, mixed>  $paths
     */
    public function load(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || trim($path) === '') {
                throw new RuntimeException('插件路径必须是非空字符串。');
            }

            if (! $this->isAbsolutePath($path)) {
                throw new RuntimeException("插件路径必须是绝对路径：{$path}");
            }

            $this->loadPath($path);
        }
    }

    /**
     * @return list<string>
     */
    public function loadedPluginIds(): array
    {
        return $this->loadedPluginIds;
    }

    private function loadPath(string $path): void
    {
        $resolvedPath = realpath($path);

        if ($resolvedPath === false || ! is_dir($resolvedPath)) {
            throw new RuntimeException("插件路径不存在或不是目录：{$path}");
        }

        $autoloadPath = $resolvedPath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        $manifestPath = $resolvedPath.DIRECTORY_SEPARATOR.'liuren-plugin.php';

        if (! is_file($autoloadPath)) {
            throw new RuntimeException("插件缺少 Composer autoload：{$autoloadPath}");
        }

        if (! is_file($manifestPath)) {
            throw new RuntimeException("插件缺少入口清单：{$manifestPath}");
        }

        require_once $autoloadPath;

        $plugin = require $manifestPath;

        if (! $plugin instanceof LiurenPlugin) {
            throw new RuntimeException("插件入口必须返回 LiurenPlugin 实例：{$manifestPath}");
        }

        $pluginId = trim($plugin->id());

        if ($pluginId === '') {
            throw new RuntimeException("插件标识不能为空：{$manifestPath}");
        }

        if (in_array($pluginId, $this->loadedPluginIds, true)) {
            throw new RuntimeException("插件标识重复：{$pluginId}");
        }

        $providers = $plugin->providers();

        foreach ($providers as $provider) {
            if (! is_string($provider) || ! class_exists($provider)) {
                throw new RuntimeException("插件 {$pluginId} 声明了无法加载的服务提供者。");
            }

            if (! is_subclass_of($provider, ServiceProvider::class)) {
                throw new RuntimeException("插件 {$pluginId} 声明的类不是 Laravel 服务提供者：{$provider}");
            }
        }

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        $this->loadedPluginIds[] = $pluginId;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
