<?php

use App\Extensions\AbsolutePath;

test('absolute paths are recognized without consulting the filesystem', function (string $path) {
    expect(AbsolutePath::check($path))->toBeTrue();
})->with([
    'unix path' => '/opt/foo',
    'unix root' => '/',
    'windows backslash path' => 'C:\\foo',
    'windows forward slash path' => 'C:/foo',
    'windows d drive path' => 'D:\\bar',
    'windows z drive path' => 'Z:/plugins/liuren',
]);

test('relative and empty paths are rejected without consulting the filesystem', function (string $path) {
    expect(AbsolutePath::check($path))->toBeFalse();
})->with([
    'windows drive relative path' => 'C:foo',
    'windows parent relative path' => '..\\foo',
    'unix parent relative path' => '../foo',
    'current directory relative path' => './foo',
    'plain relative path' => 'foo',
    'empty path' => '',
]);
