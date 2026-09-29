<?php

use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Support\BiFaPageCatalog;
use App\Support\BiFaResearchDocument;
use App\Support\KeJingCaseInterpreter;
use App\Support\KeJingPageCatalog;
use App\Support\KeJingResearchDocument;
use App\Support\Knowledge\BiFaKnowledgeCardFactory;
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

Route::get('/bifa', function () {
    return view('bifa.index', ['laws' => BiFaPageCatalog::laws()]);
})->name('bifa');

Route::get('/bifa/{law}', function (
    string $law,
    BiFaRuleRegistry $registry,
    BiFaResearchDocument $research,
    BiFaKnowledgeCardFactory $cardFactory,
) {
    $pages = BiFaPageCatalog::laws();
    $lawIndex = null;
    foreach ($pages as $index => $candidate) {
        if ($candidate['slug'] === $law) {
            $lawIndex = $index;
            break;
        }
    }

    abort_if($lawIndex === null, 404);

    $page = $pages[$lawIndex];
    $researched = $page['researched'] ?? false;

    $definition = null;
    foreach ($registry->rules() as $rule) {
        if ($rule->code() === $page['code']) {
            $definition = $rule->definition();
            break;
        }
    }

    $implemented = $definition !== null;

    $original = $research->original($page);

    return view('bifa.show', [
        'law' => $page,
        'researched' => $researched,
        'implemented' => $implemented,
        'knowledgeCard' => $researched && $implemented
            ? $cardFactory->fromDetail($page, $definition)->toArray()
            : null,
        'original' => $original,
        'previousLaw' => $lawIndex > 0 ? $pages[$lawIndex - 1] : null,
        'nextLaw' => $lawIndex < count($pages) - 1 ? $pages[$lawIndex + 1] : null,
    ]);
})->where('law', '[a-z0-9-]+')->name('bifa.show');
