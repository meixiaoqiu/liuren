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
        ->assertSee('设置起课信息')
        ->assertSee('年命')
        ->assertSee('行年')
        ->assertSee('立即排盘')
        ->assertSee('csrf-token');
});

test('frontend calculation matches the calculator without side effects', function () {
    $datetime = '2024-08-11T14:00';
    $expected = app(PanCalculator::class)
        ->calculate('2024-08-11 14:00:00')
        ->toArray();
    $expected = [...$expected, 'nianming' => 2, 'xingnian' => 4];
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

test('frontend keeps the form available when get parameters are invalid', function () {
    Livewire::withQueryParams([
        'datetime' => 'not-a-date',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasErrors(['datetime'])
        ->assertSet('pan', null)
        ->assertSee('立即排盘');
});

test('frontend rejects a lesson time before the birth time', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '1986-07-31T23:59')
        ->set('birthDatetime', '1986-08-01T00:00')
        ->call('calculate')
        ->assertHasErrors(['birthDatetime']);
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
        ->assertSee('三光判断')
        ->assertSee('春季：木旺，火相')
        ->assertSee('旺相时段：2000-02-04 20:40:24 至 2000-04-17 12:50:10')
        ->assertSee('四季末十八日按下一个四立交节时刻前推十八个整日计算')
        ->assertSee('丙上亥乘')
        ->assertSee('贵人')
        ->assertSee('午上子乘')
        ->assertSee('天后')
        ->assertSee('发用午乘')
        ->assertSee('青龙')
        ->assertSee('日、辰、用三处均旺相且乘吉将')
        ->assertSee('得季节')
        ->assertDontSee('得月令')
        ->assertDontSee('月令旺相')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows sanyang lesson with jin hexagram', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-06-28T03:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('三阳课')
        ->assertSee('晋卦')
        ->assertSee('䷢')
        ->assertSee('课入三阳，官爵翱翔')
        ->assertSee('三阳判断')
        ->assertSee('季节旺相：火旺，土相')
        ->assertSee('贵人临辰，十二天将顺行')
        ->assertSee('丁寄未，乘六合，为贵前第3将')
        ->assertSee('日支巳乘螣蛇，为贵前第1将')
        ->assertSee('贵人顺行，日辰均乘贵前五将')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows sanqi lesson and records lianzhu without inventing a grid', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-27T14:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('三奇课')
        ->assertSee('豫卦')
        ->assertSee('䷏')
        ->assertSee('三奇联珠')
        ->assertDontSee('三奇联珠格')
        ->assertSee('三奇判断')
        ->assertSee('甲申旬，三传为亥、子、丑')
        ->assertSee('本旬以子为奇')
        ->assertSee('见于中传')
        ->assertSee('乙日以巳为奇')
        ->assertSee('未见于三传')
        ->assertSee('占日所在六甲旬的旬奇发用、入于中传或末传，故成三奇课')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows liuyi lesson with dui hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-06-27T03:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('六仪课')
        ->assertSee('兑卦')
        ->assertSee('䷹')
        ->assertSee('六仪判断')
        ->assertSee('占日属于甲寅旬，三传为寅、未、子')
        ->assertSee('本旬以旬首地支寅为仪，见于初传')
        ->assertSee('辰日以寅为支仪')
        ->assertSee('旬仪发用、入于中传或末传，故成六仪课')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows guanjue lesson with yi hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->assertSet('birthDatetime', '1986-08-01T00:00')
        ->assertSet('gender', 'male')
        ->set('datetime', '1986-09-28T03:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('年命寅 · 行年寅')
        ->assertSee('官爵课')
        ->assertSee('益卦')
        ->assertSee('䷩')
        ->assertSee('官爵印绶，得之荣华')
        ->assertSee('官爵判断')
        ->assertSee('三传为申、戌、子')
        ->assertSee('初传申，为太岁、本命、行年驿马')
        ->assertSee('天魁戌见于中传')
        ->assertSee('太常见于末传')
        ->assertSee('占日驿马仅作课内参考')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows fugui lesson with dayou hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2025-01-10T07:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('富贵课')
        ->assertSee('大有卦')
        ->assertSee('䷍')
        ->assertSee('天降福德，万事新鲜')
        ->assertSee('富贵判断')
        ->assertSee('基础：吉')
        ->assertSee('当前：吉象成立')
        ->assertSee('成立依据')
        ->assertSee('课义判断')
        ->assertSee('初传子乘天乙贵人，得旺气')
        ->assertSee('天盘子水临地盘卯木，上生下')
        ->assertSee('地盘卯为日支')
        ->assertSee('未见明确增减条件')
        ->assertDontSee('综合提示')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows fugui imprisonment and exception as an ordered judgment chain', function () {
    Livewire::withQueryParams([
        'datetime' => '2026-03-08T05:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors()
        ->assertSee('富贵课')
        ->assertSeeInOrder(['太常为绶', '贵人入狱', '不以坐狱论'])
        ->assertSee('增强')
        ->assertSee('减损')
        ->assertSee('例外')
        ->assertDontSee('增吉')
        ->assertDontSee('减吉')
        ->assertDontSee('解凶');
});

test('frontend accepts reproducible pan inputs from get parameters', function () {
    Livewire::withQueryParams([
        'datetime' => '2025-01-10T07:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertSet('datetime', '2025-01-10T07:00')
        ->assertSet('birthDatetime', '1986-08-01T00:00')
        ->assertSet('gender', 'male')
        ->assertHasNoErrors()
        ->assertSet('pan.calculationTime', '2025-01-10 07:00:00')
        ->assertSee('富贵课');
});

test('frontend shows shitai lesson with the daquan text and correction reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('birthDatetime', '1800-01-01T00:00')
        ->set('datetime', '1900-11-01T20:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('时泰课')
        ->assertSee('泰卦')
        ->assertSee('䷊')
        ->assertSee('时泰判断')
        ->assertSee('三传为子、巳、戌')
        ->assertSee('太岁子见于初传；月建戌见于末传')
        ->assertSee('青龙见于初传，六合见于末传')
        ->assertSee('太岁子为日财；月建戌非日财德')
        ->assertSee('初末传乘青龙、六合相对')
        ->assertSee('岁月发用更佳，入于中传或末传亦可')
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
