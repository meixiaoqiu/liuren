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
        ->assertSee('八专课')
        ->assertSee('中末传归干上神')
        ->assertDontSee('规则尚未覆盖')
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

test('frontend distinguishes a fanyin pattern from its initial transmission method', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-07T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('返吟课')
        ->assertSee('震卦')
        ->assertSee('䷲')
        ->assertDontSee('无依课')
        ->assertDontSee('涉害课')
        ->assertSee('冲神递取')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows jinglan as a grid of fanyin rather than wuqin lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-14T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('返吟课')
        ->assertSee('井栏格')
        ->assertDontSee('无亲课')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows sanguang lesson with ben hexagram', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-02-18T11:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSet('ruleMatches.0.name', '返吟课')
        ->assertSet('ruleMatches.0.marker', '课')
        ->assertSet('ruleMatches.1.name', '三光课')
        ->assertSet('ruleMatches.1.marker', '课')
        ->assertSet('ruleMatches.2.marker', '传')
        ->assertSee('三光课')
        ->assertSee('贲卦')
        ->assertSee('䷕')
        ->assertSee('课入三光，万事吉昌')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend does not classify a fanyin lesson using jianji selection as shehai lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-11T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('返吟')
        ->assertDontSee('涉害课')
        ->assertDontSee('见机格')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend does not classify a fanyin lesson using biyong selection as zhiyi lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-09T13:39')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('返吟')
        ->assertDontSee('知一课')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend does not classify a fanyin lesson using chongshen selection as chongshen lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-08T13:39')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('返吟')
        ->assertDontSee('重审课')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend hides the ordinary tianpan shunchuan explanation without losing coverage', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-22T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('遥克课')
        ->assertSee('蒿矢格')
        ->assertSee('睽卦')
        ->assertSee('䷥')
        ->assertDontSee('天盘顺传')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows maoxing lesson with its hushi grid and hexagram', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-12T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('昴星课')
        ->assertSee('虎视格')
        ->assertSee('履卦')
        ->assertSee('䷉')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend leaves biezhe hexagram empty according to liuren daquan', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-10T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('别责课')
        ->assertDontSee('涣卦')
        ->assertDontSee('䷺')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows the tongren gua metadata for a bazhuan lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-01T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('八专课')
        ->assertSee('同人卦')
        ->assertSee('䷌')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows both duzu and weibu buxiu grids when a bazhuan lesson qualifies', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-01T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('八专课')
        ->assertSee('独足格')
        ->assertSee('帷簿不修格')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows the qian gua metadata for a yuanshou lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-07T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('元首课')
        ->assertSee('乾卦')
        ->assertSee('䷀')
        ->assertSee('四课中只有一处上克下，取克下之上神为初传。')
        ->assertSee('天地得位，品物咸新。')
        ->assertSee('门庭喜溢，利见大人。');
});

test('frontend shows the kun gua metadata for a chongshen lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-06T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('重审课')
        ->assertSee('坤卦')
        ->assertSee('䷁');
});

test('frontend shows the bi gua metadata for a zhiyi lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-18T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('知一课')
        ->assertSee('比卦')
        ->assertSee('䷇');
});

test('frontend classifies the biyong method as the zhiyi lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-16T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('知一课')
        ->assertDontSee('比用课')
        ->assertSee('比卦')
        ->assertSee('䷇');
});

test('frontend shows the kan gua metadata for a shehai lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-09T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('涉害课')
        ->assertSee('坎卦')
        ->assertSee('䷜')
        ->assertSee('风波险恶，度涉艰难。')
        ->assertSee('胎孕迟滞，行人未还。');
});
