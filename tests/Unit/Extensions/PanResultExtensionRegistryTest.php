<?php

use App\Extensions\PanResultExtensionRegistry;

test('pan result extension registry starts empty', function () {
    expect((new PanResultExtensionRegistry)->all())->toBe([]);
});

test('pan result extension registry stores registrations', function () {
    $registry = new PanResultExtensionRegistry;
    $registry->register('example.report', 'example::report.section', 80);

    expect($registry->all())->toBe([[
        'key' => 'example.report',
        'view' => 'example::report.section',
        'order' => 80,
    ]]);
});

test('pan result extension registry rejects duplicate keys', function () {
    $registry = new PanResultExtensionRegistry;
    $registry->register('example.report', 'example::first');

    expect(fn () => $registry->register('example.report', 'example::second'))
        ->toThrow(LogicException::class, 'Duplicate pan result extension key');
});

test('pan result extension registry rejects an empty view', function () {
    expect(fn () => (new PanResultExtensionRegistry)->register('example.report', '  '))
        ->toThrow(LogicException::class, 'view must not be empty');
});

test('pan result extension registry keeps registration order when priorities match', function () {
    $registry = new PanResultExtensionRegistry;
    $registry->register('third', 'example::third', 200);
    $registry->register('first', 'example::first', 100);
    $registry->register('second', 'example::second', 100);

    expect(array_column($registry->all(), 'key'))->toBe(['first', 'second', 'third']);
});
