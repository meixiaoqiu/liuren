<?php

/** 文件作用：验证第59课玄胎课真实生产盘、前台展示与课经目录数据。 */

use App\Livewire\Pan\CreatePan;
use App\Services\PanCalculator;
use App\Support\KeJingCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('daquan jia-yin yin-hour si-general example is reproduced by production calculator', function () {
    $pan = app(PanCalculator::class)->calculate('2031-09-11 03:00:00')->toArray();

    expect([$pan['rigan'], $pan['rizhi'], $pan['shizhi'], $pan['yuejiang']])->toBe([0, 2, 2, 5])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([8, 11, 2]);
});

test('known production pan with shen hai yin transmissions matches xuantai', function () {
    $pan = app(PanCalculator::class)->calculate('2001-05-03 11:00:00')->toArray();

    expect([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([8, 11, 2]);
});

test('frontend shows xuantai lesson and shared reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2001-05-03T11:00')
        ->set('birthDatetime', '1986-08-01T00:00')
        ->set('gender', 'male')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('玄胎课')
        ->assertSee('家人卦')
        ->assertSee('䷤')
        ->assertSee('玄胎判断')
        ->assertSee('三传俱孟')
        ->assertSee('申、亥、寅');
});

test('xuantai catalog exposes daquan reproduction as clickable case', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.xuantai');

    expect($lesson)->not->toBeNull()
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['玄胎课', '家人', '䷤'])
        ->and($lesson['cases'])->toHaveCount(2)
        ->and($lesson['cases'][0]['datetime'])->toBe('2031-09-11T03:00')
        ->and($lesson['cases'][0]['status'])->toBe('executable')
        ->and($lesson['cases'][0]['source_type'])->toBe('daquan')
        ->and($lesson['cases'][1]['datetime'])->toBe('2001-05-03T11:00')
        ->and($lesson['cases'][1]['source_type'])->toBe('other')
        ->and($lesson['source_examples'][0]['source'])->toBe('《六壬大全》正文')
        ->and($lesson['source_examples'][0]['source_type'])->toBe('daquan')
        ->and($lesson['source_examples'][0]['detail'])->toContain('2031-09-11 03:00');
});
