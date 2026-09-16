<?php

/** 文件作用：锁定第57课盘珠课、天心格、回还格的集合语义及“四课之中”边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\HuihuanRule;
use App\Domain\Pan\Rules\PanzhuRule;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\TianxinRule;

function panzhu_facts(array $changes = []): PanFacts
{
    $base = [
        'nianzhi' => 10,
        'yuezhi' => 1,
        'rigan' => 0,
        'rizhi' => 0,
        'shizhi' => 1,
        'sike' => [0, 1, 1, 0, 0, 11, 11, 10],
        'sanchuan0' => 0,
        'sanchuan1' => 11,
        'sanchuan2' => 10,
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('panzhu metadata and three registry entries are stable', function () {
    $rule = new PanzhuRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect($rule->code())->toBe('lesson.panzhu')
        ->and([$rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['盘珠课', '大壮', '䷡'])
        ->and(array_search('lesson.panzhu', $codes, true))->toBe(array_search('structure.cuotuo', $codes, true) + 1)
        ->and(array_search('structure.tianxin', $codes, true))->toBe(array_search('lesson.panzhu', $codes, true) + 1)
        ->and(array_search('structure.huihuan', $codes, true))->toBe(array_search('structure.tianxin', $codes, true) + 1);
});

test('panzhu static definition freezes the complete detail-page semantics', function () {
    $definition = (new PanzhuRule)->definition();

    expect($definition['description'])->toBe(PanzhuRule::DESCRIPTION)
        ->and($definition['xiang'])->toBe(PanzhuRule::XIANG)
        ->and(array_column($definition['foundations'], 'code'))->toBe([
            'four_establishments_in_lessons',
            'transmissions_in_lessons',
            'two_grids_combined',
        ])
        ->and(array_column($definition['judgments'], 'code'))->toBe([
            'wangxiang_good_generals',
            'incomplete_four_lessons',
            'fanyin_distance_shift',
            'zhanguan_empty_later',
            'soft_day_maoxing_hidden',
            'heavy_yin',
            'heavy_yang',
            'yin_over_yang',
            'yang_over_yin',
            'adverse_query_types',
            'qiu_si_bad_generals',
        ]);
});

test('classic combined structure establishes panzhu and both independent grids', function () {
    $facts = panzhu_facts();
    $panzhu = (new PanzhuRule)->match($facts);
    $tianxin = (new TianxinRule)->match($facts);
    $huihuan = (new HuihuanRule)->match($facts);

    expect($panzhu)->not->toBeNull()
        ->and($panzhu?->evidence['lesson_branches'])->toBe([1, 0, 11, 10])
        ->and($panzhu?->evidence['four_establishments'])->toBe([
            'year' => 10, 'month' => 1, 'day' => 0, 'hour' => 1,
        ])
        ->and($panzhu?->evidence['transmissions'])->toBe([0, 11, 10])
        ->and(array_column($panzhu?->evidence['foundations'] ?? [], 'code'))->toBe([
            'four_establishments_in_lessons', 'transmissions_in_lessons', 'two_grids_combined',
        ])
        ->and(array_column($panzhu?->evidence['foundations'] ?? [], 'matched'))->toBe([true, true, true])
        ->and($tianxin)->not->toBeNull()
        ->and($tianxin?->evidence['matched_routes'])->toBe(['four_lessons'])
        ->and($huihuan)->not->toBeNull();
});

test('four lessons means the complete lesson branch structure not only four upper gods', function () {
    $facts = panzhu_facts([
        'rizhi' => 5,
        // 巳只出现在第三课下位 sike[4]，不在 sike[1,3,5,7] 四个上神中。
        'sike' => [0, 1, 1, 0, 5, 11, 11, 10],
    ]);

    expect((new PanzhuRule)->match($facts))->not->toBeNull();
});

test('sike zero is a stem and must never be mistaken for a branch', function () {
    $facts = panzhu_facts([
        'nianzhi' => 5,
        'rigan' => 5,
        // 巳(5)只存在于 sike[0] 的日干数字位置；真正的四课地支中没有巳。
        'sike' => [5, 1, 1, 0, 0, 11, 11, 10],
    ]);

    expect((new PanzhuRule)->match($facts))->toBeNull()
        ->and((new TianxinRule)->match($facts))->toBeNull();
});

test('huihuan can stand alone without tianxin or panzhu', function () {
    $facts = panzhu_facts(['nianzhi' => 8]);

    expect((new HuihuanRule)->match($facts))->not->toBeNull()
        ->and((new TianxinRule)->match($facts))->toBeNull()
        ->and((new PanzhuRule)->match($facts))->toBeNull();
});

test('tianxin transmission-only route can stand alone', function () {
    $facts = panzhu_facts([
        'nianzhi' => 4,
        'yuezhi' => 5,
        'rizhi' => 6,
        'shizhi' => 4,
        'sike' => [0, 1, 1, 2, 2, 3, 3, 1],
        'sanchuan0' => 4,
        'sanchuan1' => 5,
        'sanchuan2' => 6,
    ]);

    $tianxin = (new TianxinRule)->match($facts);
    expect($tianxin)->not->toBeNull()
        ->and($tianxin?->evidence['four_establishments_in_lessons'])->toBeFalse()
        ->and($tianxin?->evidence['four_establishments_in_transmissions'])->toBeTrue()
        ->and($tianxin?->evidence['matched_routes'])->toBe(['transmissions'])
        ->and((new HuihuanRule)->match($facts))->toBeNull()
        ->and((new PanzhuRule)->match($facts))->toBeNull();
});

test('panzhu requires both four establishments and all transmissions in lessons', function () {
    expect((new PanzhuRule)->match(panzhu_facts(['sanchuan2' => 8])))->toBeNull()
        ->and((new PanzhuRule)->match(panzhu_facts(['nianzhi' => 8])))->toBeNull();
});

test('malformed lesson or branch facts cannot match', function (array $changes) {
    $facts = panzhu_facts($changes);
    expect((new PanzhuRule)->match($facts))->toBeNull()
        ->and((new TianxinRule)->match($facts))->toBeNull()
        ->and((new HuihuanRule)->match($facts))->toBeNull();
})->with([
    'short sike' => [['sike' => [0, 1, 1]]],
    'bad year branch' => [['nianzhi' => 12]],
    'string hour branch' => [['shizhi' => '1']],
    'missing final transmission' => [['sanchuan2' => null]],
]);
