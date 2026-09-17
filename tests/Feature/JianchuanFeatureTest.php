<?php

/** 文件作用：验证第61课间传课《大全》甲子日辰加甲正文课例的现代生产复现、前台与课经目录。 */

use App\Livewire\Pan\CreatePan;
use App\Services\PanCalculator;
use App\Support\KeJingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('daquan jia zi chen over jia example reproduces chen wu shen transmissions', function () {
    $pan = app(PanCalculator::class)->calculate('2031-01-24 19:00:00')->toArray();

    expect([$pan['rigan'], $pan['rizhi']])->toBe([0, 0])
        ->and($pan['shizhi'])->toBe(10)
        ->and($pan['yuejiang'])->toBe(0)
        ->and($pan['tianpan'][2])->toBe(4)
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([4, 6, 8]);
});

test('frontend shows jianchuan lesson shared reasoning and deng santian subtype', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2031-01-24T19:00')
        ->set('birthDatetime', '1986-08-01T00:00')
        ->set('gender', 'male')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('间传课')
        ->assertSee('巽卦')
        ->assertSee('䷸')
        ->assertSee('间传判断')
        ->assertSee('顺间传')
        ->assertSee('登三天格')
        ->assertSee('辰、午、申')
        ->assertSee('查看间传课详解');
});

test('jianchuan catalog exposes the daquan example as an executable source case', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.jianchuan');

    expect($lesson)->not->toBeNull()
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['间传课', '巽', '䷸'])
        ->and($lesson['cases'])->toHaveCount(1)
        ->and($lesson['cases'][0]['datetime'])->toBe('2031-01-24T19:00')
        ->and($lesson['cases'][0]['source_type'])->toBe('daquan')
        ->and($lesson['source_examples'])->toHaveCount(2)
        ->and($lesson['source_examples'][0]['source'])->toBe('《六壬大全》正文')
        ->and($lesson['source_examples'][0]['source_type'])->toBe('daquan');
});

test('jianchuan detail exposes all twenty four named subtypes and complete original section', function () {
    $response = $this->get(route('kejing.show', ['lesson' => 'jianchuan']))
        ->assertOk()
        ->assertSee('第 61 课')
        ->assertSee('《六壬大全》完整原文')
        ->assertSee('登三天格')
        ->assertSee('向阳格')
        ->assertSee('顾祖格')
        ->assertSee('回明格')
        ->assertSee('断涧格');

    foreach ([
        '登三天格', '出三天格', '涉三渊格', '入三渊格', '向阳格', '出阳格',
        '出户格', '盈阳格', '变盈格', '入冥格', '凝阴格', '溟濛格',
        '冥阴格', '偃蹇格', '悖戾格', '凝阳格', '顾祖格', '涉疑格',
        '极阴格', '时遁格', '励明格', '回明格', '转悖格', '断涧格',
    ] as $label) {
        $response->assertSee($label);
    }
});
