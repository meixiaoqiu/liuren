<?php

namespace App\Extensions;

final class AbsolutePath
{
    public static function check(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
