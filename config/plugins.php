<?php

/*
 * 插件是受信任的本地 PHP 代码，与宿主 Laravel 应用拥有相同的执行权限，并非安全沙箱。
 * 插件路径只能由部署或环境配置提供，不得由 HTTP 请求、用户输入或数据库动态决定。
 */
$paths = array_map(
    static fn (string $path): string => trim($path),
    explode(PATH_SEPARATOR, (string) env('LIUREN_PLUGIN_PATHS', '')),
);

return [
    'paths' => array_values(array_filter(
        $paths,
        static fn (string $path): bool => $path !== '',
    )),
];
