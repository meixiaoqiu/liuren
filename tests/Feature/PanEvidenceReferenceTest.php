<?php

use App\Extensions\KeJingExtensionRegistry;
use App\Livewire\Pan\CreatePan;
use Livewire\Livewire;

test('Plugin OFF 核心规则正常展示且不冒充课经 Evidence', function () {
    $component = Livewire::test(CreatePan::class)
        ->set('datetime', '2024-08-11T14:00')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('八专课');

    expect(app(KeJingExtensionRegistry::class)->rules())->toBeEmpty();

    $matches = $component->get('ruleMatches');
    expect($matches)->not->toBeEmpty();

    foreach ($matches as $match) {
        expect($match['evidence_ref'])->toBeNull();
    }

    expect($component->html())->not->toContain('data-evidence-ref="kejing:');
});
