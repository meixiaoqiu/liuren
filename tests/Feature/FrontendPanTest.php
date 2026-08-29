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

test('frontend explains the shehai process for a shehai lesson', function () {
    $datetime = '2000-05-18T03:24';
    $expected = app(PanCalculator::class)
        ->calculate('2000-05-18 03:24:00')
        ->toArray();
    $trace = $expected['shehaiTrace'];

    expect($trace)->not->toBeNull()
        ->and($trace['candidates'])->not->toBeEmpty()
        ->and(array_column($trace['candidates'], 'lesson_index'))->toContain($trace['decision']['selected_lesson_index'])
        ->and($trace['decision']['selected_branch'])->toBe($expected['sanchuan0']);

    foreach ($trace['candidates'] as $candidate) {
        expect($candidate['depth'])->toBe(count($candidate['encounters']))
            ->and($candidate['path'])->not->toBeEmpty();
    }

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $datetime)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('涉害过程')
        ->assertSee($trace['relation'])
        ->assertSee($trace['decision']['rule'])
        ->assertSee(PanCalculator::$dizhi[$trace['decision']['selected_branch']]);

    foreach ($trace['candidates'] as $candidate) {
        $component
            ->assertSee('第'.$candidate['lesson'].'课候选')
            ->assertSee($candidate['depth'].'重');
    }
});
