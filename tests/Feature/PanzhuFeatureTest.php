<?php

/** 文件作用：验证第57课盘珠课目录案例可由生产排盘命中，并在前台同时展示盘珠课、天心格、回还格。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HuihuanRule;
use App\Domain\Pan\Rules\TianxinRule;
use App\Livewire\Pan\CreatePan;
use App\Services\PanCalculator;
use App\Support\KeJingCatalog;
use Livewire\Livewire;

test('panzhu catalog production case reproduces the classic combined structure', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.panzhu');
    $case = $lesson['cases'][0];

    expect($lesson)->not->toBeNull()
        ->and([$lesson['name'], $lesson['gua'], $lesson['guaSymbol']])->toBe(['盘珠课', '大壮', '䷡'])
        ->and($lesson['summary'])->toContain('太岁')->toContain('三传')->toContain('四课')
        ->and($case)->toMatchArray([
            'case_id' => 'lesson.panzhu.geng_xu_chou_month_jia_zi_chou_time',
            'datetime' => '2031-01-24T01:00',
            'status' => 'executable',
        ]);

    $component = Livewire::test(CreatePan::class)
        ->set('datetime', $case['datetime'])
        ->set('birthDatetime', $case['birth'])
        ->set('gender', $case['gender'])
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('盘珠课')
        ->assertSee('大壮卦')
        ->assertSee('䷡')
        ->assertSee('盘珠判断')
        ->assertSee('尚未覆盖')
        ->assertSee('日用旺相与神将吉凶尚未作为盘珠课课内 judgment 程序化')
        ->assertSee('天心格')
        ->assertSee('回还格');

    $pan = $component->get('pan');
    $matches = collect($component->get('ruleMatches'));
    $panzhu = $matches->firstWhere('code', 'lesson.panzhu');
    $tianxin = $matches->firstWhere('code', 'structure.tianxin');
    $huihuan = $matches->firstWhere('code', 'structure.huihuan');

    expect([$pan['nianzhi'], $pan['yuezhi'], $pan['rigan'], $pan['rizhi'], $pan['shizhi'], $pan['yuejiang']])
        ->toBe([10, 1, 0, 0, 1, 0])
        ->and($pan['sike'])->toBe([0, 1, 1, 0, 0, 11, 11, 10])
        ->and([$pan['sanchuan0'], $pan['sanchuan1'], $pan['sanchuan2']])->toBe([0, 11, 10])
        ->and($panzhu)->not->toBeNull()
        ->and($panzhu['evidence']['lesson_branches'])->toBe([1, 0, 11, 10])
        ->and($tianxin)->not->toBeNull()
        ->and($tianxin['evidence']['matched_routes'])->toBe(['four_lessons'])
        ->and($huihuan)->not->toBeNull();
});

test('panzhu source examples remain reproducible through production calculator', function () {
    $calculator = new PanCalculator;

    $tianxinFacts = PanFacts::from($calculator->calculate('2044-08-24 17:00:00'));
    $tianxin = (new TianxinRule)->match($tianxinFacts);

    expect([
        $tianxinFacts->get('niangan'),
        $tianxinFacts->get('nianzhi'),
        $tianxinFacts->get('yuezhi'),
        $tianxinFacts->get('rigan'),
        $tianxinFacts->get('rizhi'),
        $tianxinFacts->get('shizhi'),
        $tianxinFacts->get('yuejiang'),
    ])->toBe([0, 0, 8, 1, 5, 9, 5])
        ->and($tianxinFacts->get('sike'))->toBe([1, 0, 0, 8, 5, 1, 1, 9])
        ->and([
            $tianxinFacts->get('sanchuan0'),
            $tianxinFacts->get('sanchuan1'),
            $tianxinFacts->get('sanchuan2'),
        ])->toBe([9, 5, 1])
        ->and($tianxin)->not->toBeNull()
        ->and($tianxin?->evidence['matched_routes'])->toBe(['four_lessons']);

    $wuziFacts = PanFacts::from($calculator->calculate('1900-07-14 00:00:00'));

    expect([
        $wuziFacts->get('rigan'),
        $wuziFacts->get('rizhi'),
        $wuziFacts->get('shizhi'),
        $wuziFacts->get('yuejiang'),
    ])->toBe([4, 0, 0, 7])
        ->and($wuziFacts->get('sike'))->toBe([4, 0, 0, 7, 0, 7, 7, 2])
        ->and([
            $wuziFacts->get('sanchuan0'),
            $wuziFacts->get('sanchuan1'),
            $wuziFacts->get('sanchuan2'),
        ])->toBe([0, 7, 2])
        ->and((new HuihuanRule)->match($wuziFacts))->not->toBeNull();

    $xinhaiFacts = PanFacts::from($calculator->calculate('1900-02-07 01:00:00'));

    expect([$xinhaiFacts->get('rigan'), $xinhaiFacts->get('rizhi')])->toBe([7, 11])
        ->and($xinhaiFacts->get('sike'))->toBe([7, 9, 9, 8, 11, 10, 10, 9])
        ->and([
            $xinhaiFacts->get('sanchuan0'),
            $xinhaiFacts->get('sanchuan1'),
            $xinhaiFacts->get('sanchuan2'),
        ])->toBe([10, 9, 8])
        ->and((new HuihuanRule)->match($xinhaiFacts))->not->toBeNull();
});

test('kejing detail page exposes panzhu source evidence without exposing internal indices', function () {
    $this->get(route('kejing.show', ['lesson' => 'panzhu']))
        ->assertOk()
        ->assertSee('盘珠课')
        ->assertSee('庚戌年·丑月·甲子日·丑时·子将')
        ->assertSee('天心格')
        ->assertSee('回还格')
        ->assertSee('戊子日·子时·未将')
        ->assertDontSee('sike[0]')
        ->assertDontSee('sanchuan0');
});
