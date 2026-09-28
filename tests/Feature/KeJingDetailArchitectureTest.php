<?php

use App\Support\KeJingCaseInterpreter;
use App\Support\KeJingPageCatalog;

test('plugin off detail adapter returns a safe empty skeleton', function () {
    $lesson = KeJingPageCatalog::findByCode('lesson.sanguang');
    $detail = app(KeJingCaseInterpreter::class)->build($lesson);

    expect($detail['pan'])->toBeNull()
        ->and($detail['canonicalCase'])->toBeNull()
        ->and($detail['grids'])->toBe([])
        ->and($detail['interpretation']['description'])->toBe('')
        ->and($detail['interpretation']['gua'])->toBeNull()
        ->and($detail['interpretation']['guaSymbol'])->toBeNull();
});
