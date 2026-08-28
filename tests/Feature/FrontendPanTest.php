<?php

use App\Livewire\Pan\CreatePan;
use App\Models\Pan;
use App\Services\PanCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('frontend pan page is available', function () {
    $this->get(route('pan.create'))
        ->assertOk()
        ->assertSee('大六壬排盘')
        ->assertSee('选择起课时间')
        ->assertSee('立即排盘')
        ->assertSee('csrf-token');
});

test('frontend calculation matches the calculator without side effects', function () {
    $datetime = '2024-08-11T14:00';
    $expected = app(PanCalculator::class)
        ->calculate('2024-08-11 14:00:00')
        ->toArray();
    $firstLessonTianpanBranch = PanCalculator::$jigong[$expected['rigan']];
    $firstLessonGroundIndex = array_search($firstLessonTianpanBranch, $expected['tianpan'], true);
    $firstLessonTianjiang = PanCalculator::$tianjiang[$expected['tianjiang'][$firstLessonGroundIndex]];

    session()->forget('pan');
    $recordsBefore = Pan::query()->count();

    Livewire::test(CreatePan::class)
        ->set('datetime', $datetime)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('pan', $expected)
        ->assertSee('三传')
        ->assertSee('四课')
        ->assertSee('天地盘')
        ->assertSee('解盘信息')
        ->assertSee(PanCalculator::$jiuzongmen[$expected['jiuzongmen']])
        ->assertSee($expected['wuxingShengke0'][0] === 0 ? '不生不克' : $expected['wuxingShengke0'][1])
        ->assertDontSee('旬遁')
        ->assertSee($firstLessonTianjiang)
        ->assertSee('空');

    expect(session()->has('pan'))->toBeFalse()
        ->and(Pan::query()->count())->toBe($recordsBefore);
});

test('frontend rejects an invalid datetime', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', 'not-a-date')
        ->call('calculate')
        ->assertHasErrors(['datetime'])
        ->assertSet('pan', null);
});
