<?php

use App\Support\BiFaPageCatalog;

test('plugin off bifa index and every identity detail remain available', function () {
    $laws = BiFaPageCatalog::laws();

    expect($laws)->toHaveCount(100)
        ->and(array_column($laws, 'number'))->toBe(range(1, 100));

    $this->get(route('bifa'))->assertOk()->assertSee('前后引从升迁吉')->assertSee('初末传相生终吉');
    foreach ($laws as $law) {
        expect($law['researched'])->toBeFalse()
            ->and($law['summary'])->toBe('')
            ->and($law['daquanCases'])->toBe([])
            ->and($law['generatedCases'])->toBe([])
            ->and($law['referenceOnlyCases'])->toBe([]);
    }

    $this->get(route('bifa.show', ['law' => $laws[0]['slug']]))
        ->assertOk()
        ->assertSee('尚未研究')
        ->assertDontSee('打开完整研究记录');
});
