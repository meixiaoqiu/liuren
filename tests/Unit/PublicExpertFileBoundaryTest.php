<?php

test('public worktree contains only core pan rule files and generic trace views', function () {
    $ruleFiles = array_map('basename', glob(app_path('Domain/Pan/Rules/*.php')) ?: []);
    sort($ruleFiles);
    $allowedRules = [
        'BazhuanRule.php', 'BiezheRule.php', 'ChaweiRule.php', 'ChongSanchuanRule.php', 'ChongshenRule.php',
        'ChuchuanMethodRule.php', 'ConditionalEvaluationRule.php', 'ContextAwareRule.php', 'DongsheYanmuRule.php',
        'DongsheYanmuSanchuanRule.php', 'DuzhuanRule.php', 'DuzuRule.php', 'FanyinRule.php', 'FuyinRule.php',
        'GanShangshenSanchuanRule.php', 'HaoshiRule.php', 'HushiRule.php', 'HushiSanchuanRule.php',
        'JianjiRule.php', 'JinglanRule.php', 'LessonDefinitionDefaults.php', 'LessonPatternRule.php',
        'MaoxingRule.php', 'PanRule.php', 'PanRuleEngine.php', 'RuleMatch.php', 'RuleRegistry.php',
        'SanchuanMethodRule.php', 'ShehaiRule.php', 'TansheRule.php', 'TianpanShunchuanRule.php',
        'WeibuBuxiuRule.php', 'YaokeRule.php', 'YuanshouRule.php', 'ZhiyiRule.php', 'ZhuixiaRule.php',
        'ZirenRule.php', 'ZixinRule.php',
    ];
    sort($allowedRules);

    $traceFiles = array_map('basename', glob(resource_path('views/livewire/pan/partials/*-trace.blade.php')) ?: []);
    sort($traceFiles);

    expect($ruleFiles)->toBe($allowedRules)
        ->and(glob(app_path('Domain/Pan/BiFa/Rules/*.php')) ?: [])->toBe([])
        ->and(glob(base_path('docs/课经/*.md')) ?: [])->toBe([])
        ->and(glob(base_path('docs/毕法/*.md')) ?: [])->toBe([])
        ->and($traceFiles)->toBe(['bifa-trace.blade.php', 'grid-trace.blade.php', 'lesson-trace.blade.php', 'shehai-trace.blade.php']);
});

test('generic extension contracts and presentation adapters remain present', function () {
    foreach ([
        app_path('Domain/Pan/Rules/PanRule.php'), app_path('Domain/Pan/Rules/RuleMatch.php'),
        app_path('Domain/Pan/Rules/PanRuleEngine.php'), app_path('Domain/Pan/Rules/RuleRegistry.php'),
        app_path('Domain/Pan/Rules/LessonDefinitionDefaults.php'), app_path('Domain/Pan/BiFa/BiFaRule.php'),
        app_path('Domain/Pan/BiFa/BiFaRuleMatch.php'), app_path('Domain/Pan/BiFa/BiFaRuleEngine.php'),
        app_path('Domain/Pan/BiFa/BiFaRuleRegistry.php'), app_path('Extensions/KeJingContribution.php'),
        app_path('Extensions/KeJingExtensionRegistry.php'), app_path('Extensions/BiFaContribution.php'),
        app_path('Extensions/BiFaExtensionRegistry.php'), resource_path('views/livewire/pan/partials/lesson-trace.blade.php'),
        resource_path('views/livewire/pan/partials/grid-trace.blade.php'), resource_path('views/livewire/pan/partials/bifa-trace.blade.php'),
        resource_path('views/livewire/pan/partials/shehai-trace.blade.php'),
    ] as $path) {
        expect(is_file($path))->toBeTrue($path);
    }
});

test('no private or expert text paths exist in public source tree', function () {
    expect(file_exists(base_path('docs/课经')))->toBeFalse();
    expect(file_exists(base_path('docs/毕法')))->toBeFalse();
    expect(is_dir(base_path('resources/views/kejing/trace')))->toBeFalse();
    expect(is_dir(base_path('resources/docs/课经')))->toBeFalse();
    expect(is_dir(base_path('resources/docs/毕法')))->toBeFalse();
    expect(glob(base_path('app/KeJing/*')) ?: [])->toBe([]);
    expect(glob(base_path('app/BiFa/*')) ?: [])->toBe([]);
});
