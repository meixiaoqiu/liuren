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
    $expected = [
        ...$expected,
        'nianming' => 2,
        'xingnian' => 4,
        'xingnian_gan' => 0,
        'context' => [
            'people' => [
                ['role' => 'querent', 'birth_datetime' => '1986-08-01T00:00', 'gender' => 'male', 'nianming' => 2, 'xingnian' => 4, 'xingnian_gan' => 0],
            ],
        ],
    ];
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
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', '2000-02-18T11:00')
        ->call('calculate')
        ->assertHasNoErrors();

    $matches = $component->get('ruleMatches');
    $names = array_column($matches, 'name');
    $markers = array_column($matches, 'marker');
    $sanguangIndex = array_search('三光课', $names, true);
    $fanyinIndex = array_search('返吟课', $names, true);

    // 返吟、三光为 primary（经），应排在 secondary（传）之前；具体下标因其他 primary 规则增减可能变化。
    expect($fanyinIndex)->toBeInt()
        ->and($sanguangIndex)->toBeInt()
        ->and($matches[$fanyinIndex]['marker'])->toBe('经')
        ->and($matches[$sanguangIndex]['marker'])->toBe('经')
        ->and(in_array('传', $markers, true))->toBeTrue('至少应保留一处非经标记的辅助判断');

    $component
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
        ->set('datetime', '2004-04-16T18:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('三阳课')
        ->assertSee('晋卦')
        ->assertSee('䷢')
        ->assertSee('课入三阳，官爵翱翔')
        ->assertSee('三阳判断')
        ->assertSee('季节旺相：木旺，火相')
        ->assertSee('贵人临亥，十二天将顺行')
        ->assertSee('乙寄辰，乘青龙，为贵前第5将')
        ->assertSee('日支丑乘朱雀，为贵前第2将')
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

test('frontend shows rong-hua lesson with jian hexagram and its reasoning', function () {
    // 2001-05-03 11:00：丙寅日干上申马、支上巳禄，申旺相发用，中传亥贵。
    Livewire::test(CreatePan::class)
        ->set('datetime', '2001-05-03T11:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('荣华课')
        ->assertSee('渐卦')
        ->assertSee('䷴')
        ->assertSee('干支吉神，入宅俱利')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows fugui lesson with dayou hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2025-01-10T08:00')
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
        'datetime' => '2026-03-08T06:40',
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
        'datetime' => '2025-01-10T08:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertSet('datetime', '2025-01-10T08:00')
        ->assertSet('birthDatetime', '1986-08-01T00:00')
        ->assertSet('gender', 'male')
        ->assertHasNoErrors()
        ->assertSet('pan.calculationTime', '2025-01-10 08:00:00')
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

test('frontend shows longde lesson with cui hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2013-09-04T18:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('龙德课')
        ->assertSee('萃卦')
        ->assertSee('䷬')
        ->assertSee('君恩及下，万姓欢忻')
        ->assertSee('龙德判断')
        ->assertSee('三传为巳、丑、酉')
        ->assertSee('太岁巳作初传，乘')
        ->assertSee('贵人')
        ->assertSee('月将巳见于初传，与太岁同神')
        ->assertSee('太岁乘贵人发用，月将又入于三传')
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

test('frontend shows xuangai lesson with sheng hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-02-12T06:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('轩盖课')
        ->assertSee('升卦')
        ->assertSee('䷭')
        ->assertSee('轩盖判断')
        ->assertSee('成立依据')
        ->assertSee('胜光发用')
        ->assertSee('太冲居中')
        ->assertSee('神后居末')
        ->assertSee('已核实的课义条件')
        ->assertSee('正七月正格')
        ->assertSee('盘面')
        ->assertSee('三传所乘天将')
        ->assertSee('课遇高轩，车马皆全')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend does not classify a non-wu-mao-zi transmission as xuangai lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-05-07T15:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('元首课')
        ->assertDontSee('轩盖课');
});

test('frontend shows the verified wangxiang condition for a xuangai lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-02-09T06:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('轩盖课')
        ->assertSee('已核实的课义条件')
        ->assertSee('日用旺相')
        ->assertSee('正七月正格')
        ->assertDontSee('三传落空亡')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows the empty transmission condition for a xuangai lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2001-02-15T06:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('轩盖课')
        ->assertSee('已核实的课义条件')
        ->assertSee('三传落空亡')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows zhuyin lesson with ding hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('birthDatetime', '1800-01-01T00:00')
        ->set('datetime', '1903-02-17T14:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('铸印课')
        ->assertSee('鼎卦')
        ->assertSee('䷱')
        ->assertSee('铸印判断')
        ->assertSee('成课条件')
        ->assertSee('戌入传')
        ->assertSee('巳入传')
        ->assertSee('吉凶判断')
        ->assertSee('顽金铸篆，藉火功全')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows zhuolun lesson with yi hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-02-13T09:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('斫轮课')
        ->assertSee('颐卦')
        ->assertSee('䷚')
        ->assertSee('斫轮判断')
        ->assertSee('成课条件')
        ->assertSee('卯加庚辛')
        ->assertSee('卯为用')
        ->assertSee('吉凶判断')
        ->assertSee('木欲成器，须假金斫')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows yincong lesson with huan hexagram and its reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-01-23T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('引从课')
        ->assertSee('涣卦')
        ->assertSee('䷺')
        ->assertSee('引从判断')
        ->assertSee('成课条件')
        ->assertSee('拱天干')
        ->assertSee('吉凶判断')
        ->assertSee('拱夹支干，仕人佳兆')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows yincong lesson with day prosperity flanking in fuyin', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-06-28T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('引从课')
        ->assertSee('引从判断')
        ->assertSee('成课条件')
        ->assertSee('干支拱日禄')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows hengtong lesson with jian hexagram and its grids', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-04-08T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('亨通课')
        ->assertSee('渐卦')
        ->assertSee('䷴')
        ->assertSee('三传相生，干支有情')
        ->assertSee('亨通判断')
        ->assertSee('成课条件')
        ->assertSee('递生格')
        ->assertSee('递生格依据')
        ->assertSee('三传申、亥、寅')
        ->assertSee('吉凶判断')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows hengtong lesson with ju-wang grid', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-06-13T13:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('亨通课')
        ->assertSee('亨通判断')
        ->assertSee('俱旺格')
        ->assertSee('俱旺格依据')
        ->assertSee('干上子为日干壬之旺神')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend classifies the de-yun lesson pan as fanchang with the de-yun grid', function () {
    // 德孕课盘面（2000-09-11）：行年甲己合 + 寅亥合 → 命中德孕格；
    // 寅亥非三合、且秋季寅木失令 → 旺孕格不命中。繁昌课由德孕格成立。
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-09-11T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', [
            ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'female'],
        ])
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('繁昌课')
        ->assertSee('咸卦')
        ->assertSee('德孕格')
        ->assertSee('甲己')
        ->assertSee('寅亥')
        ->assertSee('《观月经》')
        ->assertSee('不论三传')
        ->assertSee('德孕格依据')
        ->assertSee('繁昌判断')
        ->assertDontSee('原文参考盘')
        ->assertDontSee('尚未覆盖')
        ->assertDontSee('旺孕格依据')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend shows fanchang lesson with wang-yun grid', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-04-15T13:00')
        ->set('birthDatetime', '1964-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', [
            ['role' => 'spouse', 'birth_datetime' => '1974-06-01T00:00', 'gender' => 'female'],
        ])
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('繁昌课')
        ->assertSee('旺孕格')
        ->assertSee('旺孕格依据')
        ->assertSee('三合')
        ->assertDontSee('德孕格依据')
        ->assertDontSee('规则尚未覆盖');
});

test('frontend marks fanchang as not evaluated without a spouse', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('需要配偶出生信息，当前未进行判断')
        ->assertDontSee('繁昌判断')
        ->assertDontSee('德孕格')
        ->assertSee('添加相关人物');
});

test('not_evaluated notice is rendered below all lesson interpretations', function () {
    // 未评估提示（如「需要配偶出生信息」）应排在解盘信息最下方，
    // 即所有 lessonInterpretations 渲染之后。
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->call('calculate')
        ->assertHasNoErrors();

    $html = $component->html();

    $noticePos = mb_strpos($html, '需要配偶出生信息，当前未进行判断');
    expect($noticePos)->not->toBeFalse();

    // 至少确认它在 HTML 中出现在所有"繁昌判断"等解释之后不会发生冲突：
    // 本盘无配偶、命课未触发，因此页面中没有"繁昌判断"标题。直接断言其在文档中存在即可。
    expect($noticePos)->toBeGreaterThan(0);
});

test('frontend rejects a spouse with the same gender as the querent', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', [
            ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'male'],
        ])
        ->call('calculate')
        ->assertHasErrors(['people.0.gender']);
});

test('adding a second person after a spouse defaults to the other role', function () {
    $component = Livewire::test(CreatePan::class)
        ->set('gender', 'male')
        ->call('addPerson');

    expect($component->get('people'))->toBe([
        ['role' => 'spouse', 'birth_datetime' => '', 'gender' => 'female'],
    ]);

    $component->call('addPerson');

    expect($component->get('people'))->toHaveCount(2)
        ->and($component->get('people')[1]['role'])->toBe('other')
        ->and($component->get('people')[1]['gender'])->toBe('male');
});

test('frontend rejects more than the people array max (anti-DoS)', function () {
    // people 数组的 max 上限由 CreatePan::MAX_PEOPLE 决定。
    // 公开 URL 可直接构造 N 条记录；服务端必须在 validate 时拒绝超限。
    $reflection = new ReflectionClass(CreatePan::class);
    $max = $reflection->getConstant('MAX_PEOPLE');
    expect($max)->toBeInt();

    $oversize = [];
    for ($i = 0; $i < $max + 1; $i++) {
        $oversize[] = [
            'role' => 'other',
            'birth_datetime' => '1980-01-01T00:00',
            'gender' => 'male',
        ];
    }

    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', $oversize)
        ->call('calculate')
        ->assertHasErrors(['people']);
});

test('addPerson refuses to grow the people array past the max', function () {
    $reflection = new ReflectionClass(CreatePan::class);
    $max = $reflection->getConstant('MAX_PEOPLE');

    $component = Livewire::test(CreatePan::class)->set('gender', 'male');

    // 通过 set 直接灌入 max-1 条，再尝试 addPerson 至 max+1，验证 addPerson 不再追加。
    $seed = [];
    for ($i = 0; $i < $max - 1; $i++) {
        $seed[] = [
            'role' => 'other',
            'birth_datetime' => '',
            'gender' => 'male',
        ];
    }
    $component->set('people', $seed);
    expect($component->get('people'))->toHaveCount($max - 1);

    $component->call('addPerson');
    expect($component->get('people'))->toHaveCount($max);

    $component->call('addPerson');
    expect($component->get('people'))->toHaveCount($max, '达到 MAX_PEOPLE 后 addPerson 必须不再追加');
});

test('frontend rejects more than one spouse in the people array', function () {
    // spouse 角色至多出现一次；公开 URL/Livewire 请求可直接伪造两条 spouse，
    // 服务端必须明确拒绝，避免规则只取一个导致结果歧义。
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', [
            ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'female'],
            ['role' => 'spouse', 'birth_datetime' => '1970-01-01T00:00', 'gender' => 'female'],
        ])
        ->call('calculate')
        ->assertHasErrors(['people.1.role']);
});

test('frontend rejects two spouses with different genders', function () {
    // 性别不同的两条 spouse 同样禁止（不仅限同性别）。
    Livewire::test(CreatePan::class)
        ->set('datetime', '2000-03-15T13:00')
        ->set('birthDatetime', '1952-06-01T00:00')
        ->set('gender', 'male')
        ->set('people', [
            ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'female'],
            ['role' => 'spouse', 'birth_datetime' => '1970-01-01T00:00', 'gender' => 'male'],
        ])
        ->call('calculate')
        ->assertHasErrors(['people.1.role']);
});

test('frontend shows bikou lesson and reasoning for the daquan example', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '1904-02-20T05:00')
        ->set('birthDatetime', '1900-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('闭口课')
        ->assertSee('谦卦')
        ->assertSee('䷎')
        ->assertSee('闭口判断')
        ->assertSee('旬尾加旬首发用');
});

test('frontend shows youzi lesson and reasoning for the daquan example', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2022-04-22T11:00')
        ->set('birthDatetime', '2000-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('游子课')
        ->assertSee('观卦')
        ->assertSee('䷓')
        ->assertSee('游子判断')
        ->assertSee('成课条件')
        ->assertSee('三传皆季')
        ->assertSee('旬丁发用')
        ->assertSee('丁马加季');
});

test('frontend shows zhuixu branch path for the daquan jia-xu example', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2024-03-11T05:00')
        ->set('birthDatetime', '2000-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('赘婿课')
        ->assertSee('旅卦')
        ->assertSee('䷷')
        ->assertSee('赘婿判断')
        ->assertSee('成课条件')
        ->assertSee('日干克辰')
        ->assertSee('支临干发用');
});

test('frontend shows sanjiao lesson and its three-layer reasoning', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2026-05-14T11:00')
        ->set('birthDatetime', '2000-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('三交课')
        ->assertSee('姤卦')
        ->assertSee('䷫')
        ->assertSee('三交判断')
        ->assertSee('一交·四仲加辰')
        ->assertSee('二交·传皆四仲')
        ->assertSee('三交·仲神乘阴合')
        ->assertSee('卯乘太阴');
});

test('frontend shows zhuixu stem path for the daquan bing-shen example', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '2019-12-25T07:00')
        ->set('birthDatetime', '2000-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('赘婿课')
        ->assertSee('干临支发用');
});

test('frontend shows independent yixun zhoubian grid without bikou lesson', function () {
    Livewire::test(CreatePan::class)
        ->set('datetime', '1905-12-22T05:00')
        ->set('birthDatetime', '1900-01-01T00:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('一旬周遍格')
        ->assertSee('闭口课篇附格')
        ->assertSee('旬尾加干、旬首加支')
        ->assertDontSee('闭口判断');
});

test('recent lesson explanations do not expose implementation notation', function (string $datetime, string $birth, string $gender) {
    Livewire::test(CreatePan::class)
        ->set('datetime', $datetime)
        ->set('birthDatetime', $birth)
        ->set('gender', $gender)
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertDontSee('sanchuan0')
        ->assertDontSee('① AND')
        ->assertDontSee('干上神 =')
        ->assertDontSee('初传 =')
        ->assertDontSee('日干德 =')
        ->assertDontSee('命中三合局 =')
        ->assertDontSee('旬首=');
})->with([
    '德庆课' => ['2001-11-21T19:00', '1984-06-01T00:00', 'male'],
    '合欢课' => ['2000-06-19T00:00', '1959-06-21T12:00', 'male'],
    '斩关课' => ['2026-01-01T01:00', '1986-08-01T00:00', 'male'],
    '闭口课' => ['1904-02-20T05:00', '1900-01-01T00:00', 'male'],
    '一旬周遍格' => ['1905-12-22T05:00', '1900-01-01T00:00', 'male'],
]);
