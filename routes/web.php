<?php

use App\Support\KeJingCatalog;
use App\Support\QuickReferenceCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/pan/create', 'pan.create')->name('pan.create');

$kejingPages = static function (): array {
    return collect(KeJingCatalog::lessons())
        ->values()
        ->map(static function (array $lesson, int $index): array {
            $lessonNumber = 11 + $index;
            $slug = str_replace('_', '-', substr($lesson['code'], strlen('lesson.')));
            $researchFilename = sprintf('%02d-%s.md', $lessonNumber, $lesson['name']);

            return [
                ...$lesson,
                'slug' => $slug,
                'number' => $lessonNumber,
                'researchUrl' => 'https://github.com/meixiaoqiu/liuren/blob/master/docs/%E8%AF%BE%E7%BB%8F/'.rawurlencode($researchFilename),
            ];
        })
        ->all();
};

Route::get('/kejing', function () use ($kejingPages) {
    return view('kejing.index', ['lessons' => $kejingPages()]);
})->name('kejing');

Route::get('/kejing/{lesson}', function (string $lesson) use ($kejingPages) {
    $lessons = $kejingPages();
    $lessonIndex = collect($lessons)->search(
        static fn (array $candidate): bool => $candidate['slug'] === $lesson,
    );

    abort_if($lessonIndex === false, 404);

    return view('kejing.show', [
        'lesson' => $lessons[$lessonIndex],
        'previousLesson' => $lessonIndex > 0 ? $lessons[$lessonIndex - 1] : null,
        'nextLesson' => $lessonIndex < count($lessons) - 1 ? $lessons[$lessonIndex + 1] : null,
    ]);
})->where('lesson', '[a-z0-9-]+')->name('kejing.show');

Route::get('/reference', function () {
    return view('reference.index', ['reference' => QuickReferenceCatalog::class]);
})->name('reference');
