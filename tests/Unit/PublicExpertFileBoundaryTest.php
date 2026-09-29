<?php

use Tests\TestCase;

uses(TestCase::class);

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

test('storage/ tracked tree 不允许出现临时修复脚本或本地测试输出', function () {
    // storage/ 必须存在但只允许保留合法的 Laravel 脚手架文件：
    //   各级目录的 .gitignore（避免 Laravel 在安装时被误提交 runtime 数据）。
    // 任何临时修复脚本 / 本地测试输出 / grep dump / 一次性 patch helper
    // 都不得进入 tracked tree。
    $storage = base_path('storage');
    expect(is_dir($storage))->toBeTrue();

    // 1) tracked 文件白名单（结构性断言，不写具体业务词）
    $whitelist = [
        'storage/app/.gitignore',
        'storage/app/private/.gitignore',
        'storage/app/public/.gitignore',
        'storage/framework/.gitignore',
        'storage/framework/cache/.gitignore',
        'storage/framework/cache/data/.gitignore',
        'storage/framework/sessions/.gitignore',
        'storage/framework/testing/.gitignore',
        'storage/framework/views/.gitignore',
        'storage/logs/.gitignore',
    ];

    // 用 git ls-files 而不是文件系统遍历，只看真正 tracked 的文件。
    // 未跟踪的临时产物（如 _tmp_*）即使存在也不影响 tracked tree 验证。
    $tracked = [];
    exec('git ls-files storage/', $trackedRaw);
    foreach ($trackedRaw as $rel) {
        $rel = trim($rel);
        if ($rel !== '') {
            $tracked[] = str_replace('\\', '/', $rel);
        }
    }
    sort($tracked);
    sort($whitelist);

    expect($tracked)->toBe($whitelist);

    // 2) tracked tree 中禁止出现 *.py / *.sh / *.php / *.txt / *.json 临时产物
    foreach ($tracked as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        expect($ext)->not->toBeIn(
            ['py', 'sh', 'php', 'txt', 'json'],
            "$file 属于临时产物，不得进入 storage/ tracked tree",
        );
    }
});

test('仓库根目录 tracked tree 禁止出现常见临时修复脚本', function () {
    // 通过 git ls-files 只看 tracked 文件，排除未跟踪的临时产物干扰。
    $trackedRoot = [];
    exec('git ls-files', $trackedRootRaw);
    foreach ($trackedRootRaw as $rel) {
        $rel = trim(str_replace('\\', '/', $rel));
        if ($rel === '') {
            continue;
        }
        if (strpos($rel, '/') === false) {
            $trackedRoot[] = $rel;
        }
    }

    // 仓库根目录的 tracked 文件只允许是 Laravel / Pest / Pint / CI 标准入口。
    // 不允许 fix-* / patch-* / *_helper.* / _tmp.* / one-shot.* 等临时命名进入根目录。
    $bannedPatterns = [
        '/^fix[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^patch[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^_fix[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^_tmp[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^_helper[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^one[-_]shot[-_.].*\.(py|sh|php|txt|json)$/i',
        '/^debug[-_.].*\.(py|sh|php|txt|json)$/i',
    ];

    foreach ($trackedRoot as $file) {
        foreach ($bannedPatterns as $pattern) {
            expect(preg_match($pattern, $file))->toBe(0,
                "$file 属于临时修复脚本，不得进入仓库根目录 tracked tree");
        }
    }
});
