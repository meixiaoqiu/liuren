<?php

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PanRule;
use App\Domain\Pan\Rules\RuleMatch;
use App\Extensions\KeJingExtensionRegistry;
use App\Support\KeJingTraceView;
use Tests\TestCase;

uses(TestCase::class);

function traceBoundaryFakeRule(string $code): PanRule
{
    return new class($code) implements PanRule
    {
        public function __construct(private readonly string $value) {}

        public function code(): string
        {
            return $this->value;
        }

        public function definition(): array
        {
            return ['description' => '', 'xiang' => null, 'foundations' => [], 'judgments' => []];
        }

        public function match(PanFacts $facts): ?RuleMatch
        {
            return null;
        }
    };
}

beforeEach(function () {
    $this->app->instance(KeJingExtensionRegistry::class, new KeJingExtensionRegistry);
});

test('public core grids are outside the kejing trace adapter', function (string $code, string $name) {
    expect(KeJingTraceView::for([
        'code' => $code,
        'marker' => '格',
        'name' => $name,
    ], 'pan'))->toBeNull();
})->with([
    ['structure.jinglan', '井栏格'],
    ['structure.duzu', '独足格'],
    ['structure.weibu_buxiu', '帷簿不修格'],
]);

test('generic fallbacks require kejing registry membership', function () {
    $registry = app(KeJingExtensionRegistry::class);
    $registry->registerRule(traceBoundaryFakeRule('structure.fake_grid'));
    $registry->registerRule(traceBoundaryFakeRule('lesson.fake'));

    expect(KeJingTraceView::for([
        'code' => 'structure.fake_grid',
        'marker' => '格',
        'name' => '虚构格',
    ], 'pan'))->toBe([
        'view' => 'livewire.pan.partials.grid-trace',
        'title' => '虚构格依据',
        'specialized' => false,
    ])->and(KeJingTraceView::for([
        'code' => 'lesson.fake',
        'marker' => '经',
        'name' => '虚构课',
    ], 'pan'))->toBe([
        'view' => 'livewire.pan.partials.lesson-trace',
        'title' => '虚构判断',
        'specialized' => false,
    ]);
});

test('registered specialized trace has priority over generic fallback', function () {
    $registry = app(KeJingExtensionRegistry::class);
    $registry->registerRule(traceBoundaryFakeRule('lesson.fake'));
    $registry->registerTraceView('lesson.fake', [
        'view' => 'fake-plugin::trace',
        'contexts' => ['pan'],
        'title' => '虚构专属判断',
    ]);

    expect(KeJingTraceView::for([
        'code' => 'lesson.fake',
        'marker' => '经',
        'name' => '虚构课',
    ], 'pan'))->toBe([
        'view' => 'fake-plugin::trace',
        'title' => '虚构专属判断',
        'specialized' => true,
    ]);
});
