<?php

use App\Domain\Pan\Rules\PanRule;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Livewire\Pan\CreatePan;
use App\Support\KeJingCatalog;
use App\Support\KeJingPageCatalog;
use Livewire\Livewire;

test('kejing index follows research document numbering and renders the visible lesson number', function () {
    $lessons = KeJingPageCatalog::lessons();
    $response = $this->get(route('kejing'))->assertOk();

    expect(array_column($lessons, 'number'))->toBe(range(11, 10 + count($lessons)));

    foreach ($lessons as $lesson) {
        $response
            ->assertSee('第 '.$lesson['number'].' 课')
            ->assertSee($lesson['name'])
            ->assertSee(route('kejing.show', ['lesson' => $lesson['slug']]), false);
    }

    $body = $response->getContent();
    expect(strpos($body, '三光课'))->toBeLessThan(strpos($body, '三阳课'));
});

test('kejing detail and pan interpretation share the same core lesson presentation', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');
    $case = $lesson['cases'][0];

    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee('现代汉语描述')
        ->assertSee('象曰')
        ->assertSee('成立条件')
        ->assertSee('增益和减损条件')
        ->assertSee('三光判断')
        ->assertSee('《六壬大全》正文课例')
        ->assertSee('非正文课例与旁证')
        ->assertSee('《六壬大全》完整原文');

    Livewire::withQueryParams([
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ])->test(CreatePan::class)
        ->assertHasNoErrors()
        ->assertSee('现代汉语描述')
        ->assertSee('象曰')
        ->assertSee('成立条件')
        ->assertSee('查看三光课详解');
});

test('kejing detail preserves an empty gua slot when a lesson has no fixed hexagram', function () {
    $lesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.jieli');

    expect($lesson)->not->toBeNull()
        ->and($lesson['gua'])->toBeNull()
        ->and($lesson['guaSymbol'])->toBeNull();

    $this->get(route('kejing.show', ['lesson' => 'jieli']))
        ->assertOk()
        ->assertSee('解离课')
        ->assertSee('data-kejing-gua-slot="empty"', false);
});

test('kejing page catalog separates daquan source material from later witnesses', function () {
    $lesson = collect(KeJingPageCatalog::lessons())->firstWhere('code', 'lesson.lide');

    expect($lesson['daquanExamples'])->not->toBeEmpty();
    foreach ($lesson['daquanExamples'] as $example) {
        expect($example['source'])->toContain('六壬大全');
    }

    $response = $this->get(route('kejing.show', ['lesson' => 'lide']))
        ->assertOk()
        ->assertSee('《六壬大全》正文课例');

    foreach ($lesson['daquanExamples'] as $example) {
        $response->assertSee($example['label']);
    }
});

test('legacy research docs do not masquerade excerpts as complete daquan original text', function () {
    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee('《六壬大全》完整原文')
        ->assertSee('尚未结构化录入');
});

test('registered pan rules expose the static lesson definition contract', function () {
    foreach ((new RuleRegistry)->rules() as $rule) {
        expect($rule)->toBeInstanceOf(PanRule::class);

        $definition = $rule->definition();
        expect($definition)
            ->toHaveKeys(['description', 'xiang', 'foundations', 'judgments'])
            ->and($definition['foundations'])->toBeArray()
            ->and($definition['judgments'])->toBeArray();
    }
});

test('shared lesson trace can suppress foundations already rendered by the common summary', function () {
    $html = view('livewire.pan.partials.lesson-trace', [
        'title' => '测试判断',
        'trace' => [
            'foundations' => [[
                'title' => '主体条件',
                'description' => '不应重复显示',
            ]],
            'judgments' => [[
                'label' => '增强条件',
                'effect' => 'increase',
                'evidence' => '不应重复显示',
            ]],
        ],
        'suppressCoreTrace' => true,
    ])->render();

    expect(trim($html))->toBe('');
});

test('generic kejing detail does not render a duplicate canonical trace section', function () {
    $this->get(route('kejing.show', ['lesson' => 'yincong']))
        ->assertOk()
        ->assertSee('成立条件')
        ->assertDontSee('标准课例判定细节');

    $this->get(route('kejing.show', ['lesson' => 'jieli']))
        ->assertOk()
        ->assertSee('成立条件')
        ->assertDontSee('标准课例判定细节');
});
