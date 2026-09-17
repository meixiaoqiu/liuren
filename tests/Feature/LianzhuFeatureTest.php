<?php

/** 文件作用：验证第60课连珠课《大全》乙丑日正文课例的现代生产复现、前台与课经目录。 */

use App\Livewire\Pan\CreatePan;
use App\Services\PanCalculator;
use App\Support\KeJingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('daquan yi chou you time xu general example has yin mao chen transmissions', function () {
    $pan = app(PanCalculator::class)->calculate('2031-03-26 17:00:00')->toArray();

    expect([$pan['rigan'], $pan['rizhi']])->toBe([1, 1])
        ->and($pan['shizhi'])->toBe(9)
        ->and($pan['yuejiang'])->toBe(10)
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([2, 3, 4]);
});

test('frontend shows lianzhu lesson and shared reasoning on the classical reproduction', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2031-03-26T17:00')
        ->set('birthDatetime', '1986-08-01T00:00')
        ->set('gender', 'male')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('连珠课')
        ->assertSee('复卦')
        ->assertSee('䷗')
        ->assertSee('连珠判断')
        ->assertSee('进连珠')
        ->assertSee('寅、卯、辰');
});

test('lianzhu catalog exposes the daquan example as an executable source case', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.lianzhu');

    expect($lesson)->not->toBeNull()
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['连珠课', '复', '䷗'])
        ->and($lesson['cases'])->toHaveCount(1)
        ->and($lesson['cases'][0]['datetime'])->toBe('2031-03-26T17:00')
        ->and($lesson['cases'][0]['source_type'])->toBe('daquan')
        ->and($lesson['source_examples'][0]['source'])->toBe('《六壬大全》正文')
        ->and($lesson['source_examples'][0]['source_type'])->toBe('daquan')
        ->and($lesson['source_examples'])->toHaveCount(3)
        ->and($lesson['source_examples'][1]['source'])->toBe('《六壬大全》')
        ->and($lesson['source_examples'][1]['source_type'])->toBe('daquan')
        ->and($lesson['source_examples'][2]['source'])->toBe('《订讹》');
});
