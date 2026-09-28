<?php

use App\Support\KeJingPageCatalog;

test('plugin off kejing index and every identity detail remain available', function () {
    $lessons = KeJingPageCatalog::lessons();

    expect($lessons)->toHaveCount(54)
        ->and(array_column($lessons, 'number'))->toBe(range(11, 64));

    $this->get(route('kejing'))->assertOk()->assertSee('第 11 课')->assertSee('第 64 课');
    foreach ($lessons as $lesson) {
        $this->get(route('kejing.show', ['lesson' => $lesson['slug']]))
            ->assertOk()
            ->assertSee($lesson['name'])
            ->assertSee('当前未加载专家研究内容')
            ->assertDontSee('打开完整研究记录');
    }
});
