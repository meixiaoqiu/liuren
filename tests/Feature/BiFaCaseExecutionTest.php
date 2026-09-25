<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\BiFaRuleEngine;
use App\Domain\Pan\BiFa\Rules\CuiGuanShiZheRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Livewire\Pan\CreatePan;
use App\Support\BiFaCaseCatalog;
use Livewire\Livewire;

/**
 * 真实可执行的案例必须满足：
 *
 *  - 通过 URL 参数（datetime / birth / gender / people）即可直接起盘；
 *  - 排盘组件自动生成的 querent 必含本命 / 行年；案例如声明仅本命或仅行年则不可 executable；
 *  - BiFaRuleEngine::evaluate 返回的 match.matched_routes 必须覆盖案例声明的 routes。
 *
 * 若某案例实际运行后不命中声明 route，本测试会失败——表明该案例应降级为 reference_only。
 *
 * 直接遍历目录中的全部案例；后续新增法律无需维护法号白名单。
 */
beforeEach(function () {
    // 排盘环境需要明确的时区，否则 FateCalculator 可能用错 UTC。
    date_default_timezone_set('Asia/Shanghai');
});

test('every executable bifa case reproduces all of its declared routes through the pan page', function () {
    $failures = [];

    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }
        if (empty($case['datetime'])) {
            $failures[] = "case_id={$case['case_id']}: executable 案例必须含 datetime";

            continue;
        }

        $params = [
            'datetime' => $case['datetime'],
            'birth' => $case['birth'] ?? '1986-08-01T00:00',
            'gender' => $case['gender'] ?? 'male',
        ];
        if (! empty($case['people'])) {
            $params['people'] = $case['people'];
        }

        $component = Livewire::withQueryParams($params)
            ->test(CreatePan::class)
            ->assertHasNoErrors();

        $bifa = collect(app(BiFaRuleEngine::class)->evaluate(new PanResult($component->get('pan'))))
            ->first(fn ($match): bool => $match->code === $case['law_code']);

        if ($bifa === null) {
            $failures[] = "case_id={$case['case_id']}: 起盘后 {$case['law_code']} 必须命中";

            continue;
        }

        $matchedRoutes = $bifa->matchedRoutes;
        foreach ($case['routes'] as $route) {
            if ($route === '') {
                continue;
            }
            if (! in_array($route, $matchedRoutes, true)) {
                $failures[] = "case_id={$case['case_id']}: 声明 route={$route} 必须在 matched_routes 中（实际：".implode(',', $matchedRoutes).'）';
            }
        }
        if ($case['law_code'] === 'bifa.03' && $case['routes'] !== $matchedRoutes) {
            $failures[] = "case_id={$case['case_id']}: 第三法 routes 必须完整且顺序一致（声明：".implode(',', $case['routes']).'；实际：'.implode(',', $matchedRoutes).'）';
        }
        if ($case['law_code'] === 'bifa.05' && $case['routes'] !== $matchedRoutes) {
            $failures[] = "case_id={$case['case_id']}: 第五法 routes 必须完整且顺序一致（声明：".implode(',', $case['routes']).'；实际：'.implode(',', $matchedRoutes).'）';
        }
        if ($case['law_code'] === 'bifa.06' && $case['routes'] !== $matchedRoutes) {
            $failures[] = "case_id={$case['case_id']}: 第六法 routes 必须完整且顺序一致（声明：".implode(',', $case['routes']).'；实际：'.implode(',', $matchedRoutes).'）';
        }
    }

    expect($failures)->toBe([], "executable 案例必须自动命中声明 route：\n".implode("\n", $failures));
});

test('fourth bifa talisman fixture at 17:00 matches through both direct rule evaluation and the pan page', function () {
    $case = collect(BiFaCaseCatalog::cases())
        ->firstWhere('case_id', 'bifa.04.generated-cui-guan-talisman');

    expect($case)->not->toBeNull()
        ->and($case['datetime'])->toBe('2000-01-20T17:00');

    $params = [
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ];
    $component = Livewire::withQueryParams($params)
        ->test(CreatePan::class)
        ->assertHasNoErrors();
    $pan = new PanResult($component->get('pan'));

    $direct = (new CuiGuanShiZheRule)->match(PanFacts::from($pan));
    $fromPage = collect(app(BiFaRuleEngine::class)->evaluate($pan))
        ->first(fn ($match): bool => $match->code === 'bifa.04');

    expect($direct?->matchedRoutes ?? [])->toContain('cui_guan_talisman')
        ->and($fromPage?->matchedRoutes ?? [])->toContain('cui_guan_talisman');
});

test('executable bifa cases do not require manual people override (querent is auto-created)', function () {
    // executable 案例的 people 字段必须为空数组——任何额外人物都会干扰 Livewire::test 的自动 querent。
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }

        expect($case['people'] ?? [])->toBe([], "case_id={$case['case_id']} executable 案例不得预置 people，必须由 Livewire 自动创建 querent")
            ->and($case['birth'] ?? null)->not->toBeNull("case_id={$case['case_id']} executable 案例必须提供 birth 以便自动生成 querent")
            ->and($case['gender'] ?? null)->toBeIn(['male', 'female'], "case_id={$case['case_id']} executable 案例必须提供合法 gender");
    }
});

test('reference_only bifa cases must have a documented reason for not being executable', function () {
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['status'] !== 'reference_only') {
            continue;
        }

        // reference_only 案例必须在文案中明确说明为什么不能 executable：
        //   - 古籍未给完整 datetime；或
        //   - URL 自动起盘后无法重现案例的声明状态；或
        //   - 仅用于 engine 单元测试覆盖，不应被排盘工具扫入。
        $reason = (string) ($case['reason'] ?? '');
        expect($reason)->not->toBeEmpty("case_id={$case['case_id']} reference_only 案例必须说明降级原因");

        // reason 至少提及下列关键词之一。
        $hasKeyword = str_contains($reason, 'reference_only')
            || str_contains($reason, 'executable')
            || str_contains($reason, '无法复现')
            || str_contains($reason, '无法重现')
            || str_contains($reason, '降级')
            || str_contains($reason, '回填');
        expect($hasKeyword)->toBeTrue("case_id={$case['case_id']} reason 缺少 reference_only / executable / 降级 等关键词");
    }
});
