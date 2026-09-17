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
    $definition = collect((new RuleRegistry)->rules())
        ->first(static fn ($rule): bool => $rule->code() === 'lesson.sanguang')
        ?->definition();

    expect($definition)->not->toBeNull();

    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee($definition['description'])
        ->assertSee($definition['xiang'])
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
        ->assertSee('三光课')
        ->assertSee('贲卦')
        ->assertSee('成立条件')
        ->assertSee('查看三光课详解');
});

test('lesson number belongs to the detail page heading and is absent from pan interpretation', function () {
    $catalogLesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.sanguang');
    $pageLesson = KeJingPageCatalog::findByCode('lesson.sanguang');
    $case = $catalogLesson['cases'][0];

    expect($pageLesson)->not->toBeNull();

    $this->get(route('kejing.show', ['lesson' => 'sanguang']))
        ->assertOk()
        ->assertSee('data-kejing-page-number', false)
        ->assertSee('第 '.$pageLesson['number'].' 课');

    Livewire::withQueryParams([
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ])->test(CreatePan::class)
        ->assertHasNoErrors()
        ->assertDontSee('第 '.$pageLesson['number'].' 课');
});

test('pan and detail kejing headers share the compact chongshen presentation', function () {
    $interpretation = [
        'code' => 'lesson.__shared_header_test',
        'name' => '测试课',
        'group' => '六十四课',
        'description' => '测试现代描述',
        'marker' => '经',
        'gua' => '坤',
        'guaSymbol' => '䷁',
        'xiang' => '测试象辞',
        'evidence' => [
            'foundations' => [],
            'judgments' => [],
        ],
    ];

    $render = static fn (string $mode): string => view('kejing.partials.interpretation-summary', [
        'interpretation' => $interpretation,
        'lessonPage' => null,
        'staticDefinition' => [
            'description' => '测试现代描述',
            'xiang' => '测试象辞',
            'foundations' => [],
            'judgments' => [],
        ],
        'mode' => $mode,
    ])->render();

    $panHtml = $render('pan');
    $detailHtml = $render('detail');

    foreach ([$panHtml, $detailHtml] as $html) {
        expect($html)
            ->toContain('data-kejing-pan-heading="compact"')
            ->toContain('data-kejing-pan-gua="compact"')
            ->toContain('data-kejing-gua-slot="present"')
            ->toContain('size-20')
            ->toContain('text-4xl')
            ->toContain('经')
            ->toContain('坤卦')
            ->toContain('测试课')
            ->toContain('䷁');
    }

    expect($panHtml)
        ->toContain('测试现代描述')
        ->toContain('测试象辞')
        ->not->toContain('现代汉语描述');

    expect($detailHtml)
        ->toContain('测试现代描述')
        ->toContain('测试象辞')
        ->not->toContain('现代汉语描述')
        ->not->toContain('象曰');
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
        expect($example['source_type'])->toBe('daquan');
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

test('unstructured lesson detail keeps canonical match evidence without pretending it is the full definition', function () {
    $this->get(route('kejing.show', ['lesson' => 'zhuyin']))
        ->assertOk()
        ->assertSee('当前正式规则尚未结构化录入本课成立条件')
        ->assertSee('标准课例命中证据（非完整定义）')
        ->assertSee('戌入传')
        ->assertSee('巳入传');
});

test('detail presentation prefers the static definition description and xiang when available', function () {
    $html = view('kejing.partials.interpretation-summary', [
        'interpretation' => [
            'code' => 'lesson.__static_definition_test',
            'name' => '测试课',
            'group' => '六十四课',
            'description' => '动态描述不应优先',
            'marker' => '经',
            'gua' => null,
            'guaSymbol' => null,
            'xiang' => '动态象曰不应优先',
            'evidence' => [],
        ],
        'lessonPage' => null,
        'staticDefinition' => [
            'description' => '静态定义描述',
            'xiang' => '静态定义象曰',
            'foundations' => [[
                'code' => 'foundation.test',
                'title' => '静态成立条件',
                'description' => '静态条件描述',
            ]],
            'judgments' => [],
        ],
        'mode' => 'detail',
    ])->render();

    expect($html)
        ->toContain('静态定义描述')
        ->toContain('静态定义象曰')
        ->toContain('静态成立条件')
        ->not->toContain('动态描述不应优先')
        ->not->toContain('动态象曰不应优先');
});

test('every source example exposes an explicit structural source type', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['source_examples'] ?? [] as $example) {
            expect($example)->toHaveKey('source_type');
            expect(['daquan', 'other'])->toContain($example['source_type']);
        }
    }
});

test('source example classification uses an exhaustive exact source registry', function () {
    $reflection = new ReflectionClass(KeJingCatalog::class);
    $constant = $reflection->getReflectionConstant('SOURCE_EXAMPLE_TYPES');

    expect($constant)->not->toBeFalse();
    $sourceTypes = $constant->getValue();

    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['source_examples'] ?? [] as $example) {
            $source = $example['source'] ?? null;
            if ($source === null) {
                expect($example['source_type'])->toBe('other');

                continue;
            }

            expect($sourceTypes)->toHaveKey($source);
            expect($example['source_type'])->toBe($sourceTypes[$source]);
        }
    }
});
