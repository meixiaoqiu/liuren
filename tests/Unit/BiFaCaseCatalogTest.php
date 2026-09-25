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
 *  - routes 语义约束：
 *      * status='executable' 或 source_type='generated' 的成立案例：routes 必须非空，
 *        已实现法的 route 必须属于本法 definition；
 *      * status='reference_only' 的正文反例：允许 routes 留空——前提是该案例是
 *        正文中明确作为反例登记的（如 bifa.09.daquan-bing-yin-counterexample）；
 *  - 未实现法只允许登记 reference_only。
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

test('BingYin counterexample is a reference_only case with empty routes and is excluded by escape_to_wealth filter', function () {
    // 丙寅日见在之财落空是《六壬大全》明确的求财反例，不代表 escape_to_wealth 成立。
    $case = BiFaCaseCatalog::findByCaseId('bifa.09.daquan-bing-yin-counterexample');
    expect($case)->not->toBeNull()
        ->and($case['law_code'])->toBe('bifa.09')
        ->and($case['source_type'])->toBe('daquan')
        ->and($case['status'])->toBe('reference_only')
        ->and($case['routes'])->toBe([])
        ->and($case['source'])->toContain('《六壬大全·毕法赋》');

    // 反例不得被 casesByMatchedRoutes('bifa.09', ['escape_to_wealth']) 返回。
    $matched = BiFaCaseCatalog::casesByMatchedRoutes('bifa.09', ['escape_to_wealth']);
    $matchedIds = array_column($matched, 'case_id');
    expect($matchedIds)->not->toContain('bifa.09.daquan-bing-yin-counterexample');

    // 反例仍出现在 casesForLaw 中（属于 bifa.09 的目录条目），只是 routes 为空。
    $allForLaw = BiFaCaseCatalog::casesForLaw('bifa.09');
    $allIds = array_column($allForLaw, 'case_id');
    expect($allIds)->toContain('bifa.09.daquan-bing-yin-counterexample');
});

test('BiFaCaseCatalog routes contract: routes=[] is forbidden except for explicitly registered reference_only counterexamples', function () {
    // 契约：
    //   1. status='executable' 的案例不得 routes=[]；
    //   2. status='reference_only' 但 source_type='generated' 的案例，默认不得 routes=[]；
    //   3. 唯一允许 routes=[] 的例外是下方白名单内"明确登记的反例"；
    //   4. 白名单以外的任何案例一旦 routes=[] 都必须失败。
    //
    // 当前白名单仅含两条已注册的反例：
    //   - bifa.01.generated-no-match-no-pending：generated 反例，覆盖 BiFaRuleEngine 在
    //     matched_routes / pending_routes 同时为空时返回 null 的契约；
    //   - bifa.09.daquan-bing-yin-counterexample：daquan 反例，覆盖丙寅日「日干下临财乡」
    //     求财不成立。
    // 新增合法空 routes 案例时必须同时更新本白名单并补充 contract 注释。
    $allowedEmptyRoutesCaseIds = [
        'bifa.01.generated-no-match-no-pending',
        'bifa.09.daquan-bing-yin-counterexample',
    ];

    foreach (BiFaCaseCatalog::cases() as $case) {
        if ($case['routes'] !== []) {
            continue;
        }

        expect($case['status'])->toBe(
            'reference_only',
            "case_id={$case['case_id']} routes=[] 仅允许 reference_only 状态"
        );

        expect(in_array($case['case_id'], $allowedEmptyRoutesCaseIds, true))->toBeTrue(
            "case_id={$case['case_id']} 的 routes=[] 必须是白名单内的 reference_only 反例"
        );
    }
});

test('BiFaCaseCatalog routes contract: executable and non-whitelisted generated cases must declare non-empty routes', function () {
    $allowedEmptyRoutesCaseIds = [
        'bifa.01.generated-no-match-no-pending',
        'bifa.09.daquan-bing-yin-counterexample',
    ];

    foreach (BiFaCaseCatalog::cases() as $case) {
        // 白名单内的反例跳过——它们的 routes=[] 是有意为之。
        if (in_array($case['case_id'], $allowedEmptyRoutesCaseIds, true)) {
            continue;
        }

        if ($case['status'] === 'executable') {
            expect($case['routes'])->not->toBe(
                [],
                "executable case_id={$case['case_id']} 必须声明至少一条 route，不允许 routes=[]"
            );
        }

        if ($case['source_type'] === 'generated') {
            expect($case['routes'])->not->toBe(
                [],
                "generated case_id={$case['case_id']} 必须声明至少一条 route，不允许 routes=[]"
            );
        }
    }
});

test('BiFaCaseCatalog bing-yin counterexample is one of the legal empty-routes cases', function () {
    $emptyRoutesCases = array_values(array_filter(
        BiFaCaseCatalog::cases(),
        static fn (array $case): bool => $case['routes'] === [],
    ));

    $emptyRoutesIds = array_column($emptyRoutesCases, 'case_id');
    expect($emptyRoutesIds)->toContain('bifa.09.daquan-bing-yin-counterexample');

    // 锁死丙寅反例自身的字段，确保它不会被未来误改。
    $bingyin = BiFaCaseCatalog::findByCaseId('bifa.09.daquan-bing-yin-counterexample');
    expect($bingyin)->not->toBeNull()
        ->and($bingyin['law_code'])->toBe('bifa.09')
        ->and($bingyin['source_type'])->toBe('daquan')
        ->and($bingyin['status'])->toBe('reference_only')
        ->and($bingyin['routes'])->toBe([]);
});
