<?php

use App\Support\KeJingCaseInterpreter;
use App\Support\KeJingPageCatalog;
use App\Support\KeJingResearchDocument;
use App\Support\QuickReferenceCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/pan/create', 'pan.create')->name('pan.create');

Route::get('/kejing', function () {
    return view('kejing.index', ['lessons' => KeJingPageCatalog::lessons()]);
})->name('kejing');

Route::get('/kejing/{lesson}', function (
    string $lesson,
    KeJingCaseInterpreter $interpreter,
    KeJingResearchDocument $research,
) {
    $lessons = KeJingPageCatalog::lessons();
    $lessonIndex = collect($lessons)->search(
        static fn (array $candidate): bool => $candidate['slug'] === $lesson,
    );

    abort_if($lessonIndex === false, 404);

    $page = $lessons[$lessonIndex];

    return view('kejing.show', [
        'lesson' => $page,
        'detail' => $interpreter->build($page),
        'original' => $research->original($page),
        'previousLesson' => $lessonIndex > 0 ? $lessons[$lessonIndex - 1] : null,
        'nextLesson' => $lessonIndex < count($lessons) - 1 ? $lessons[$lessonIndex + 1] : null,
    ]);
})->where('lesson', '[a-z0-9-]+')->name('kejing.show');

Route::get('/reference', function () {
    return view('reference.index', ['reference' => QuickReferenceCatalog::class]);
})->name('reference');
