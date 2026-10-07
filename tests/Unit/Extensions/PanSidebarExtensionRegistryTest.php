<?php

use App\Extensions\PanSidebarExtensionRegistry;

test('sidebar registrations validate identity and preserve stable ordering', function () {
    $registry = new PanSidebarExtensionRegistry;
    expect($registry->all())->toBe([]);
    $registry->register(' later ', 'sample::later', 200);
    $registry->register('first', ' sample::first ', 100);
    $registry->register('second', 'sample::second', 100);
    expect($registry->all())->toBe([
        ['key' => 'first', 'view' => 'sample::first', 'order' => 100],
        ['key' => 'second', 'view' => 'sample::second', 'order' => 100],
        ['key' => 'later', 'view' => 'sample::later', 'order' => 200],
    ]);
    expect(fn () => $registry->register('first', 'sample::different'))->toThrow(LogicException::class);
});

test('sidebar rejects blank key or view', function (string $key, string $view) {
    expect(fn () => (new PanSidebarExtensionRegistry)->register($key, $view))->toThrow(LogicException::class);
})->with([[' ', 'sample::view'], ['key', ' ']]);
