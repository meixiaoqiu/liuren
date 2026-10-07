<?php

use App\Extensions\PanResultExtensionRegistry;
use App\Extensions\PanSidebarExtensionRegistry;
use App\Livewire\Pan\CreatePan;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

test('both plugin slots render contributions in their designated locations', function () {
    View::addNamespace('sample', base_path('tests/Fixtures/PanExtensions'));
    app(PanSidebarExtensionRegistry::class)->register('sample.sidebar', 'sample::slot');
    app(PanResultExtensionRegistry::class)->register('sample.result', 'sample::slot');
    $component = Livewire::test(CreatePan::class)->assertSee('等待排盘');
    $html = $component->set('datetime', '2024-08-11T14:00')->call('calculate')->assertHasNoErrors()->html();
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//aside//*[@data-sample-slot]')->length)->toBe(1)
        ->and($xpath->query('//main//section//*[@data-sample-slot]')->length)->toBe(1);
    expect(app(PanSidebarExtensionRegistry::class))->toBe(app(PanSidebarExtensionRegistry::class));
    expect(app(PanResultExtensionRegistry::class))->toBe(app(PanResultExtensionRegistry::class));
});

test('plugin off both slots are empty and no private interface is rendered', function () {
    expect(app(PanSidebarExtensionRegistry::class)->all())->toBe([])
        ->and(app(PanResultExtensionRegistry::class)->all())->toBe([]);
    Livewire::test(CreatePan::class)->set('datetime', '2024-08-11T14:00')->call('calculate')
        ->assertHasNoErrors()->assertSee('三传')->assertDontSee('专家系统')->assertDontSee('本次占问')->assertDontSee('复制解盘信息')
        ->assertDontSee('RuleMatch.evidence');
});
