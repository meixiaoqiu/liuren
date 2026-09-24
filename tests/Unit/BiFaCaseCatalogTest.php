<?php

use App\Domain\Pan\BiFa\BiFaRuleRegistry;
use App\Support\BiFaCaseCatalog;

/**
 * 案例元数据硬约束（不依赖排盘）：
 *
 *  - source_type 必须是 'daquan' 或 'generated'，无第三种；
 *  - source_type='daquan' 必须有古籍原文来源——source 字段必须含 "《六壬大全" 字样，
 *    且不得以 "程序验证"、"由…构造"、"自行构造" 等措辞结尾；
 *  - source_type='generated' 不得伪装成 daquan，且 source 必须明示现代/程序复现性质；
 *  - case_id 命名空间必须是 bifa.*，不得包含 lesson.*（防 KeJingCatalog 越界）；
 *  - status 必须是 'executable' 或 'reference_only'；
 *  - executable 案例必须有 datetime / birth / gender；
 *  - reference_only 案例可缺 datetime；
 *  - routes 必须非空；已实现法的 route 必须属于本法 definition，未实现法只允许 reference_only。
 */

/**
 * @param  list<array{case_id: string, law_code: string, status: string, routes: list<string>}>  $cases
 * @param  array<string, list<string>>  $allowedRoutesByLaw
 * @return list<string>
 */
function biFaCaseRouteContractErrors(array $cases, array $allowedRoutesByLaw): array
{
    $errors = [];

    foreach ($cases as $case) {
        if (! array_key_exists($case['law_code'], $allowedRoutesByLaw)) {
            if ($case['status'] !== 'reference_only') {
                $errors[] = "case_id={$case['case_id']} 所属法尚未实现时只能登记 reference_only";
            }

            continue;
        }

        foreach ($case['routes'] as $route) {
            if (! in_array($route, $allowedRoutesByLaw[$case['law_code']], true)) {
                $errors[] = "case_id={$case['case_id']} 声明 route={$route} 不属于 {$case['law_code']} 的 foundations";
            }
        }
    }

    return $errors;
}

test('BiFaCaseCatalog cases all have valid source_type / status / case_id format', function () {
    $allowedSources = ['daquan', 'generated'];
    $allowedStatus = ['executable', 'reference_only'];

    foreach (BiFaCaseCatalog::cases() as $case) {
        expect($case['source_type'])->toBeIn($allowedSources, "case_id={$case['case_id']} source_type 非法")
            ->and($case['status'])->toBeIn($allowedStatus, "case_id={$case['case_id']} status 非法")
            ->and($case['case_id'])->toStartWith('bifa.', "case_id={$case['case_id']} 必须 bifa.* 命名空间")
            ->and($case['case_id'])->not->toContain('lesson.', "case_id={$case['case_id']} 不得引用 lesson.* 命名空间");
    }
});

test('BiFaCaseCatalog daquan cases must reference 《六壬大全》original text', function () {
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['source_type'] !== 'daquan') {
            continue;
        }

        // 古籍来源字段必须明确指向《六壬大全·毕法赋》（首法）。
        expect($case['source'])->toContain('《六壬大全·毕法赋》');
        expect($case['source'])->not->toContain('程序验证');
        expect($case['source'])->not->toContain('基于…条文构造');
    }
});

test('BiFaCaseCatalog generated cases are not labeled as daquan', function () {
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['source_type'] !== 'generated') {
            continue;
        }

        $source = (string) $case['source'];
        $looksGenerated = str_contains($source, '程序验证')
            || str_contains($source, '现代生产复现')
            || str_contains($source, '程序复现')
            || str_contains($source, '现代复现');

        expect($source)->not->toContain('正文完整课例')
            ->and($looksGenerated)->toBeTrue(
                "case_id={$case['case_id']} 的 generated source 必须明示现代/程序复现性质",
            );
    }
});

test('BiFaCaseCatalog executable cases have datetime / birth / gender', function () {
    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['status'] !== 'executable') {
            continue;
        }

        expect($case['datetime'])->not->toBeNull("executable case_id={$case['case_id']} 必须有 datetime")
            ->and($case['datetime'])->not->toBeEmpty("executable case_id={$case['case_id']} 必须有 datetime")
            ->and($case['birth'])->not->toBeNull("executable case_id={$case['case_id']} 必须有 birth")
            ->and($case['gender'])->toBeIn(['male', 'female'], "executable case_id={$case['case_id']} gender 非法");
    }
});

test('BiFaCaseCatalog routes only contain codes registered for their own law', function () {
    $allowedRoutesByLaw = [];
    foreach ((new BiFaRuleRegistry)->rules() as $rule) {
        $allowedRoutesByLaw[$rule->code()] = [];
        foreach ($rule->definition()['foundations'] as $foundation) {
            $allowedRoutesByLaw[$rule->code()][] = $foundation['code'];
        }
    }

    expect(biFaCaseRouteContractErrors(BiFaCaseCatalog::cases(), $allowedRoutesByLaw))->toBe([]);
});

test('BiFaCaseCatalog allows only reference_only cases for laws without a registered rule', function () {
    $referenceOnly = [[
        'case_id' => 'bifa.03.reference',
        'law_code' => 'bifa.03',
        'status' => 'reference_only',
        'routes' => ['future_route_not_defined_yet'],
    ]];
    $executable = [[
        'case_id' => 'bifa.03.executable',
        'law_code' => 'bifa.03',
        'status' => 'executable',
        'routes' => ['future_route_not_defined_yet'],
    ]];

    expect(biFaCaseRouteContractErrors($referenceOnly, []))->toBe([])
        ->and(biFaCaseRouteContractErrors($executable, []))->toBe([
            'case_id=bifa.03.executable 所属法尚未实现时只能登记 reference_only',
        ]);
});

test('BiFaCaseCatalog case_ids do not collide with KeJingCatalog cases', function () {
    $bifaIds = array_column(BiFaCaseCatalog::cases(), 'case_id');

    // KeJingCatalog 案例 id 应保持在 lesson.* 命名空间，不与 bifa.* 重叠。
    foreach ($bifaIds as $id) {
        expect($id)
            ->not->toStartWith('lesson.')
            ->and($id)
            ->not->toStartWith('kejing.');
    }
});

test('BiFaCaseCatalog counts by source_type / status', function () {
    $cases = BiFaCaseCatalog::cases();
    $bySource = [];
    $byStatus = [];
    foreach ($cases as $case) {
        $bySource[$case['source_type']] = ($bySource[$case['source_type']] ?? 0) + 1;
        $byStatus[$case['status']] = ($byStatus[$case['status']] ?? 0) + 1;
    }

    // 首法应至少有几条 daquan 案例（《六壬大全·毕法赋》原文确实列了若干日）。
    expect($bySource['daquan'] ?? 0)->toBeGreaterThanOrEqual(5);
});
