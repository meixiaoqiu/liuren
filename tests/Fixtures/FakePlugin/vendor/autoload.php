<?php

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\Fixtures\\FakePlugin\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = dirname(__DIR__).'/src/'.str_replace('\\', '/', $relativeClass).'.php';

    if (is_file($path)) {
        require $path;
    }
});
