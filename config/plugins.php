<?php

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
