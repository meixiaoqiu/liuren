<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\BiFa\Rules\ShouWeiXiangJianRule;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 构造第二法基础盘面：乙未日 (rigan=1, rizhi=7)。
 *
 * 默认状态：
 *  - 甲午旬：xunHead=6 (午), xunTail=3 (卯)；
 *  - 乙寄辰 (lodging=4)；
 *  - 让 tianpan[4]=3 (卯=xunTail)、tianpan[7]=6 (午=xunHead) → A 路命中；
 *  - 默认四建 nianzhi=5, yuezhi=2, rizhi=7, shizhi=3 全部在 lessonBranches → C 路命中；
 *  - 默认三传 sanchuan0=10 (戌) 不在 lessonBranches → D 路不命中；
 *  - 让 tianpan[4]=3 (旬尾), tianpan[7]=6 (旬首)，并非 xunHead(干上神)/xunTail(支上神) → B 路不命中。
 *
 * @param  array<string, mixed>  $overrides
 */
function swxj_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 10,
        'sanchuan1' => 3,
        'sanchuan2' => 6,
        'tianpan' => [9, 10, 11, 0, 3, 4, 5, 6, 7, 8, 1, 2],
        'rigan' => 1,
        'rizhi' => 7,
        'nianzhi' => 5,
        'yuezhi' => 2,
        'shizhi' => 3,
        'sike' => [1, 3, 4, 2, 7, 6, 6, 5],
        'calculationTrace' => ['plate_patterns' => []],
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function swxj_match(array $overrides = []): ?BiFaRuleMatch
{
    return (new ShouWeiXiangJianRule)->match(PanFacts::from(swxj_pan($overrides)));
}

test('ShouWeiXiangJianRule code equals bifa.02', function () {
    $rule = new ShouWeiXiangJianRule;
    expect($rule->code())->toBe('bifa.02');
});

test('ShouWeiXiangJianRule law() returns BiFaCatalog second law', function () {
    $law = (new ShouWeiXiangJianRule)->law();
    expect($law['number'])->toBe(2)
        ->and($law['code'])->toBe('bifa.02')
        ->and($law['slug'])->toBe('shou-wei-xiang-jian')
        ->and($law['name'])->toBe('首尾相见始终宜');
});

test('A 路：旬尾临干 + 旬首临支 → 命中', function () {
    // 默认盘面已构造为 A 路命中（甲午旬·旬尾卯·旬首午；乙寄辰·干上卯·支上午）。
    $match = swxj_match();

    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('bifa.02')
        ->and($match->number)->toBe(2)
        ->and($match->matchedRoutes)->toContain('xun_tail_on_stem_xun_head_on_branch');

    $a = collect($match->subMatches)->firstWhere('code', 'xun_tail_on_stem_xun_head_on_branch');
    expect($a['matched'])->toBeTrue()
        ->and($a['detail'])->toContain('旬尾')
        ->and($a['detail'])->toContain('旬首');
});

test('A 路：干正确、支错误 → 不命中 A（C 路仍命中 → match 非空）', function () {
    // 让支上神改为非旬首：tianpan[7] = 0 (子，非午)。同时 C 路四建仍在 lessonBranches → 命中。
    $match = swxj_match([
        'tianpan' => [9, 10, 11, 0, 3, 4, 5, 0, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('xun_tail_on_stem_xun_head_on_branch');

    $a = collect($match->subMatches)->firstWhere('code', 'xun_tail_on_stem_xun_head_on_branch');
    expect($a['matched'])->toBeFalse();
});

test('A 路：支正确、干错误 → 不命中 A（C 路仍命中 → match 非空）', function () {
    // 让干上神改为非旬尾：tianpan[4] = 9 (酉，非卯)。同时 C 路四建仍在 lessonBranches → 命中。
    $match = swxj_match([
        'tianpan' => [9, 10, 11, 0, 9, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('xun_tail_on_stem_xun_head_on_branch');

    $a = collect($match->subMatches)->firstWhere('code', 'xun_tail_on_stem_xun_head_on_branch');
    expect($a['matched'])->toBeFalse();
});

test('B 路：旬首临干 + 旬尾临支 → 命中', function () {
    // 乙丑日（rigan=1, rizhi=1），甲子旬：xunHead=0 (子), xunTail=9 (酉)。
    // 乙寄辰(lodging=4)；让 tianpan[4]=0 (子=xunHead)、tianpan[1]=9 (酉=xunTail)。
    // 同时关闭 A 路：让 zhiShang != xunHead(0)。
    $match = swxj_match([
        'rigan' => 1,
        'rizhi' => 1,
        'tianpan' => [8, 9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7],
        'sike' => [1, 0, 4, 8, 1, 9, 1, 5],
        // C 路：四建包含 1=丑（在 lessonBranches {0,4,8,1,9,5}）→ C 也命中
        'nianzhi' => 1,
        'yuezhi' => 8,
        'shizhi' => 0,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('xun_head_on_stem_xun_tail_on_branch');

    $b = collect($match->subMatches)->firstWhere('code', 'xun_head_on_stem_xun_tail_on_branch');
    expect($b['matched'])->toBeTrue();
});

test('A 路与 B 路是两个独立 route，互不冒充', function () {
    // 默认盘面 A 命中；B 应不命中（因为 tianpan[4]=3≠xunHead=6，tianpan[7]=6≠xunTail=3）。
    $match = swxj_match();

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('xun_tail_on_stem_xun_head_on_branch')
        ->and($match->matchedRoutes)->not->toContain('xun_head_on_stem_xun_tail_on_branch');
});

test('C 路（天心）：四建尽入四课 → 命中', function () {
    // 让 A/B 路断掉：让 ganShang、zhiShang 既不是 xunTail 也不是 xunHead（甲午旬）。
    // tianpan[4]=4, tianpan[7]=0 → 干上神=4 非旬尾/旬首；支上神=0 非旬首/旬尾。
    // sike[1..7] = {0, 8, 5, 1, 9}；四建 {5, 8, 1, 9} 皆在集合内。
    // 改变 sike 让 lessonBranches = {0, 8, 5, 1, 9}。
    // 让 sanchuan = {3, 4, 6}（皆不在集合内）→ D 不命中。
    $match = swxj_match([
        'sike' => [1, 0, 8, 5, 1, 9, 1, 9],
        'sanchuan0' => 3,
        'sanchuan1' => 4,
        'sanchuan2' => 6,
        'nianzhi' => 5,
        'yuezhi' => 8,
        'rizhi' => 1,
        'shizhi' => 9,
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 0, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('tianxin_four_establishments_in_lessons');

    $c = collect($match->subMatches)->firstWhere('code', 'tianxin_four_establishments_in_lessons');
    expect($c['matched'])->toBeTrue()
        ->and($c['detail'])->toContain('太岁')
        ->and($c['detail'])->toContain('月建')
        ->and($c['detail'])->toContain('日支')
        ->and($c['detail'])->toContain('占时');
});

test('C 路（天心）：缺任意一个四建 → 不命中（D 路仍命中 → match 非空）', function () {
    // 让 nianzhi=10 不在 lessonBranches；其余四建仍在。
    // 让 D 路通过：sanchuan = {0, 8, 5} 都在 lessonBranches 内。
    $match = swxj_match([
        'sike' => [1, 0, 8, 5, 1, 9, 1, 9],
        'sanchuan0' => 0,
        'sanchuan1' => 8,
        'sanchuan2' => 5,
        'nianzhi' => 10,
        'yuezhi' => 8,
        'rizhi' => 1,
        'shizhi' => 9,
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 0, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('tianxin_four_establishments_in_lessons');
});

test('sike[0] 日干不得误作地支加入四课集合', function () {
    // 把 nianzhi 设为 rigan=1。若程序错误地将 sike[0] 加入 lessonBranches，
    // 则 nianzhi=rigan=1 也算"在集合内"，会假命中。
    // 这里把 sike 集合构造为不含 rigan=1（即不含 1）。
    // 同时让 D 路通过：sanchuan = {0, 8, 5} 都在 lessonBranches 内 → match 非空。
    $match = swxj_match([
        'sike' => [1, 0, 8, 5, 5, 9, 5, 2],   // 集合 = {0,8,5,9,2}，不含 1
        'sanchuan0' => 0,
        'sanchuan1' => 8,
        'sanchuan2' => 5,
        'nianzhi' => 1,                          // = rigan（用作诱饵）
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 6, 7, 8, 1, 2],
    ]);

    // nianzhi=1 不在 lessonBranches → 天心格不应命中（同时整体不命中 A/B → 只有 D 不命中 → match 返回 null）。
    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('tianxin_four_establishments_in_lessons');
});

test('四课下位属于“四课之内”，不能只检查上神', function () {
    // 让 sike[1..7] = {9, 0, 5, 7, 9, 7, 5} = {9, 0, 5, 7}。
    // 让 C 路命中条件仅依赖下位：nianzhi=0（下位 sike[2]=0）；yuezhi/rizhi/shizhi 也在集合内。
    // 让 A/B 不命中（仅 ganShang/zhiShang 不是 xunTail/xunHead）；让 D 命中保持 match 非空。
    $match = swxj_match([
        'sike' => [1, 9, 0, 5, 7, 9, 7, 5],
        'sanchuan0' => 0,
        'sanchuan1' => 5,
        'sanchuan2' => 7,
        'nianzhi' => 0,                          // 太岁子靠下位 sike[2]=0 进入集合
        'yuezhi' => 9,                           // 月建酉靠 sike[1]=9 进入集合
        'rizhi' => 7,                            // 日支未靠 sike[4]=7
        'shizhi' => 5,                           // 占时巳靠 sike[3]=5 / sike[7]=5
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('tianxin_four_establishments_in_lessons');
});

test('四课下位属于“四课之内”：占时不在集合内则 C 不命中', function () {
    // 占时=10（戌）不在 lessonBranches。让 D 路通过使 match 非空。
    $match = swxj_match([
        'sike' => [1, 9, 0, 5, 7, 9, 7, 5],
        'sanchuan0' => 0,
        'sanchuan1' => 5,
        'sanchuan2' => 7,
        'nianzhi' => 0,
        'yuezhi' => 9,
        'rizhi' => 7,
        'shizhi' => 10,                          // 戌不在集合内
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('tianxin_four_establishments_in_lessons');
});

test('D 路（回还）：三传尽入四课 → 命中', function () {
    // 让 sanchuan0/1/2 = {0, 8, 5}，sike[1..7] = {0, 8, 5, 1, 9}，三传皆在内。
    // 断掉 A/B：tianpan[4]=4, tianpan[7]=0（既非旬尾非旬首）。
    // 断掉 C：nianzhi=10 不在集合。
    $match = swxj_match([
        'sanchuan0' => 0,
        'sanchuan1' => 8,
        'sanchuan2' => 5,
        'sike' => [1, 0, 8, 5, 5, 1, 5, 9],
        'nianzhi' => 10,
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain('huihuan_transmissions_in_lessons');

    $d = collect($match->subMatches)->firstWhere('code', 'huihuan_transmissions_in_lessons');
    expect($d['matched'])->toBeTrue()
        ->and($d['detail'])->toContain('初传')
        ->and($d['detail'])->toContain('中传')
        ->and($d['detail'])->toContain('末传');
});

test('D 路（回还）：缺任意一传 → 不命中（C 路仍命中 → match 非空）', function () {
    // 让 sanchuan2 = 10 不在集合内；让 C 路通过保证 match 非空。
    $match = swxj_match([
        'sanchuan0' => 0,
        'sanchuan1' => 8,
        'sanchuan2' => 10,
        'sike' => [1, 0, 8, 5, 1, 9, 1, 9],
        'nianzhi' => 0,
        'yuezhi' => 8,
        'rizhi' => 1,
        'shizhi' => 9,
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 0, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull();
    expect($match->matchedRoutes)->not->toContain('huihuan_transmissions_in_lessons');
});

test('仅一条 route 成立时第二法仍成立', function () {
    // 构造仅 D 命中。sanchuan0/1/2 全在集合内；C 不命中（nianzhi 不在集合）；
    // A/B 不命中（tianpan[4]=4 非 xunTail, tianpan[7]=0 非 xunHead）。
    $match = swxj_match([
        'sanchuan0' => 0,
        'sanchuan1' => 8,
        'sanchuan2' => 5,
        'sike' => [1, 0, 8, 5, 5, 1, 5, 9],
        'nianzhi' => 10,
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toHaveCount(1)
        ->and($match->matchedRoutes)->toContain('huihuan_transmissions_in_lessons');
});

test('多条 route 可同时成立', function () {
    // 构造 A + C + D 同时命中。
    // 乙未日 rigan=1 rizhi=7；甲午旬 xunHead=6, xunTail=3；乙寄辰(lodging=4)。
    // A 路：tianpan[4]=3(xunTail), tianpan[7]=6(xunHead)。
    // C 路：四建 = {5, 6, 7, 6} 都在 sike[1..7]={3, 2, 7, 6, 5} 内。
    // D 路：sanchuan = {3, 2, 7}，都在 sike[1..7] 内。
    $match = swxj_match([
        'sanchuan0' => 3,
        'sanchuan1' => 2,
        'sanchuan2' => 7,
        'nianzhi' => 5,
        'yuezhi' => 6,
        'shizhi' => 6,
        'sike' => [1, 3, 3, 2, 7, 6, 6, 5],
        'tianpan' => [9, 10, 11, 0, 3, 4, 5, 6, 7, 8, 1, 2],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->matchedRoutes)->toContain(
            'xun_tail_on_stem_xun_head_on_branch',
            'tianxin_four_establishments_in_lessons',
            'huihuan_transmissions_in_lessons',
        )
        ->and($match->matchedRoutes)->toHaveCount(3);
});

test('四条 route 全不成立时 match() 返回 null', function () {
    // 让所有 route 都失败：
    //  - A/B：tianpan 让 ganShang/zhiShang 既不是 xunTail(3) 也不是 xunHead(6)。
    //  - C：四建不在 lessonBranches 中。
    //  - D：sanchuan 至少一个不在 lessonBranches 中。
    $match = swxj_match([
        // 干上神(4)=4(辰 非卯非午)；支上神(7)=4(辰 非午非卯)。
        'tianpan' => [9, 10, 11, 0, 4, 4, 5, 4, 7, 8, 1, 2],
        // sanchuan = {3, 4, 6} 都可能在 lessonBranches；改 lessonBranches 让它们不在。
        'sike' => [1, 9, 10, 11, 0, 1, 2, 5],
        'nianzhi' => 4,
        'yuezhi' => 6,
        'shizhi' => 7,
    ]);

    expect($match)->toBeNull();
});

test('第二法无人物资料依赖，不产生 pending_routes', function () {
    $match = swxj_match(['context' => ['people' => []]]);

    expect($match)->not->toBeNull()
        ->and($match->pendingRoutes)->toBe([]);
});

test('definition() foundations code 与 route 完全一致', function () {
    $definition = (new ShouWeiXiangJianRule)->definition();
    $codes = array_column($definition['foundations'], 'code');

    expect($codes)->toHaveCount(4)
        ->and($codes)->toContain(
            'xun_tail_on_stem_xun_head_on_branch',
            'xun_head_on_stem_xun_tail_on_branch',
            'tianxin_four_establishments_in_lessons',
            'huihuan_transmissions_in_lessons',
        );
});

test('60 日乘 12 种合法天盘穷尽推出十日且 A B 永远互斥', function () {
    $stemNames = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    $branchNames = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    $aDays = [];
    $bDays = [];
    $both = [];

    for ($dayIndex = 0; $dayIndex < 60; $dayIndex++) {
        $rigan = $dayIndex % 10;
        $rizhi = $dayIndex % 12;
        $dayName = $stemNames[$rigan].$branchNames[$rizhi];

        for ($offset = 0; $offset < 12; $offset++) {
            $tianpan = [];
            for ($ground = 0; $ground < 12; $ground++) {
                $tianpan[] = ($ground + $offset) % 12;
            }

            $match = swxj_match([
                'rigan' => $rigan,
                'rizhi' => $rizhi,
                'tianpan' => $tianpan,
            ]);
            $routes = $match?->matchedRoutes ?? [];
            $a = in_array('xun_tail_on_stem_xun_head_on_branch', $routes, true);
            $b = in_array('xun_head_on_stem_xun_tail_on_branch', $routes, true);

            if ($a) {
                $aDays[$dayName] = true;
            }
            if ($b) {
                $bDays[$dayName] = true;
            }
            if ($a && $b) {
                $both[] = "{$dayName}@{$offset}";
            }
        }
    }

    expect(array_keys($aDays))->toHaveCount(5)
        ->and(array_keys($aDays))->toEqualCanonicalizing(['乙未', '辛丑', '丙申', '壬寅', '戊申'])
        ->and(array_keys($bDays))->toHaveCount(5)
        ->and(array_keys($bDays))->toEqualCanonicalizing(['乙丑', '辛未', '丙寅', '戊寅', '壬申'])
        ->and(array_intersect(array_keys($aDays), array_keys($bDays)))->toBe([])
        ->and($both)->toBe([]);
});
