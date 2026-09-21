<?php

use App\Domain\Pan\BiFa\BiFaRuleEngine;
use App\Livewire\Pan\CreatePan;
use App\Support\BiFaCatalog;
use App\Support\BiFaPageCatalog;
use Livewire\Livewire;

test('bifa index lists every law and the pan header exposes bifa menu', function () {
    $response = $this->get(route('bifa'))->assertOk();

    $response->assertSee('毕法赋');
    $response->assertSee('《毕法赋》百法');

    // 第 1 法及名称。
    $response->assertSee('第 1 法');
    $response->assertSee('前后引从升迁吉');

    // 全 100 法均出现在目录（尚未研究的项以编号 + 法名占位）。
    foreach (BiFaCatalog::laws() as $law) {
        $response->assertSee($law['name']);
    }

    // 页头必须包含排盘 / 课经 / 毕法 / 速查 四项。
    $response->assertSee('排盘');
    $response->assertSee('课经');
    $response->assertSee('毕法');
    $response->assertSee('速查');
});

test('bifa detail page renders the first law with research entry', function () {
    $response = $this->get(route('bifa.show', ['law' => 'qian-hou-yin-cong']))->assertOk();

    $response->assertSee('前后引从升迁吉');
    $response->assertSee('第 1 法');
    $response->assertSee('引干宜进职');
    $response->assertSee('打开完整研究记录');
    $response->assertSee('docs/%E6%AF%95%E6%B3%95/01-%E5%89%8D%E5%90%8E%E5%BC%95%E4%BB%8E%E5%8D%87%E8%BF%81%E5%90%89.md', false);

    // 至少存在"上一法 / 下一法"的导航元素（首页没有"上一法"，但应该有"下一法"）。
    $response->assertSee('下一法');

    // 不应暴露内部实现标记。
    $response->assertDontSee('QianHouYinCongRule');
    $response->assertDontSee('YinCongRule');
    $response->assertDontSee('BiFaRuleEngine');
});

test('unknown bifa slug returns 404', function () {
    $this->get('/bifa/not-a-real-law')->assertNotFound();
});

test('bifa catalog exposes 100 laws with code prefix bifa.', function () {
    $laws = BiFaCatalog::laws();
    expect($laws)->toHaveCount(100);

    foreach ($laws as $index => $law) {
        expect($law['number'])->toBe($index + 1)
            ->and($law['code'])->toStartWith('bifa.')
            ->and($law['slug'])->not->toBeEmpty();

        $found = BiFaPageCatalog::findBySlug($law['slug']);
        expect($found)->not->toBeNull()
            ->and($found['code'])->toBe($law['code']);
    }
});

test('bifa rule engine does not touch the pan rule registry', function () {
    $registry = new \App\Domain\Pan\BiFa\BiFaRuleRegistry;
    foreach ($registry->rules() as $rule) {
        expect($rule)->toBeInstanceOf(\App\Domain\Pan\BiFa\BiFaRule::class)
            ->and($rule->code())->toStartWith('bifa.');
    }

    // PanRuleRegistry 中不得出现 bifa. 开头 code，避免两套体系混入。
    $panRegistry = new \App\Domain\Pan\Rules\RuleRegistry;
    foreach ($panRegistry->rules() as $rule) {
        expect($rule->code())->not->toStartWith('bifa.');
    }
});

test('bifa panel renders on the pan page when a law matches and does not pollute kejing', function () {
    // 庚辰日 13:00（YinCongRuleTest 已知结构：拱天干）。
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-23T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    // bifaMatches 是组件公共属性；bifaInterpretations 是 render 传递的 view 变量。
    $bifa = $component->get('bifaMatches');

    expect($bifa)->toBeArray()
        ->and($bifa)->not->toBeEmpty();

    $firstLaw = $bifa[0];
    expect('bifa.qian_hou_yin_cong')->toBe($firstLaw['code'])
        ->and('前后引从升迁吉')->toBe($firstLaw['name'])
        ->and($firstLaw['matched_routes'])->toContain('yin_gan');

    // 毕法独立条目不能出现在课经 ruleMatches 中。
    $ruleMatches = $component->get('ruleMatches');
    foreach ($ruleMatches as $rule) {
        expect($rule['code'])->not->toStartWith('bifa.');
    }

    // 排盘页应展示毕法独立区块标题。
    $component->assertSee('毕法');
    $component->assertSee('《毕法赋》独立判定');
});

test('bifa component property bifaMatches is populated', function () {
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-23T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class);

    $bifa = $component->get('bifaMatches');

    expect($bifa)->toBeArray()
        ->and($bifa)->not->toBeEmpty();

    $firstLaw = $bifa[0];
    expect('bifa.qian_hou_yin_cong')->toBe($firstLaw['code'])
        ->and('前后引从升迁吉')->toBe($firstLaw['name'])
        ->and($firstLaw['matched_routes'])->toContain('yin_gan');
});

test('bifa panel is rendered on the pan page even when bifa law does not match', function () {
    // 选一个必然不命中的盘——无伏吟、夹拱条件全不满足。
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-09T07:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $bifa = $component->get('bifaMatches');
    expect($bifa)->toBeArray()->not->toBeEmpty();

    $firstLaw = $bifa[0];
    expect('bifa.qian_hou_yin_cong')->toBe($firstLaw['code'])
        ->and($firstLaw['matched'])->toBeFalse();

    // 第一法不命中时仍展示独立区块（用于明示"毕法当前未成立"）。
    $component->assertSee('毕法');
});

test('bifa unmatched page never claims a yincong lesson implies the first bifa law', function () {
    // 任意盘面下，毕法区块不得出现"引从课→所以第一法成立"等跨体系引用。
    $component = Livewire::withQueryParams([
        'datetime' => '2000-01-23T13:00',
        'birth' => '1986-08-01T00:00',
        'gender' => 'male',
    ])->test(CreatePan::class)
        ->assertHasNoErrors();

    $component->assertDontSee('引从课→所以第一法成立');
});