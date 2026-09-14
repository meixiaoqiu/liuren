<?php

/** 文件作用：验证第57课盘珠课目录案例可由生产排盘命中，并在前台同时展示盘珠课、天心格、回还格。 */

use App\Livewire\Pan\CreatePan;
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

test('kejing page exposes panzhu source evidence without exposing internal indices', function () {
    $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('盘珠课')
        ->assertSee('庚戌年·丑月·甲子日·丑时·子将')
        ->assertSee('天心格')
        ->assertSee('回还格')
        ->assertDontSee('sike[0]')
        ->assertDontSee('sanchuan0');
});
