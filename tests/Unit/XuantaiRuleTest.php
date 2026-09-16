<?php

/** 文件作用：锁定第59课玄胎课“三传俱孟”主体，以及病胎、生胎、绝胎三个动态分型。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\XuantaiRule;

function xuantai_facts(array $changes = []): PanFacts
{
    $base = [
        'sanchuan0' => 8,
        'sanchuan1' => 11,
        'sanchuan2' => 2,
        'tianpan' => range(0, 11),
        'calculationTrace' => [
            'plate_patterns' => [],
        ],
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('xuantai metadata and registry order are stable', function () {
    $rule = new XuantaiRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect($rule->code())->toBe('lesson.xuantai')
        ->and([$rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['玄胎课', '家人', '䷤'])
        ->and(array_search('lesson.xuantai', $codes, true))
        ->toBe(array_search('structure.jiase', $codes, true) + 1);
});

test('xuantai static definition separates foundations from judgments', function () {
    $definition = (new XuantaiRule)->definition();

    expect($definition['description'])->toBe(XuantaiRule::DESCRIPTION)
        ->and($definition['xiang'])->toBe(XuantaiRule::XIANG)
        ->and(array_column($definition['foundations'], 'code'))->toBe([
            'three_transmissions_all_meng',
        ])
        ->and(array_column($definition['judgments'], 'code'))->toContain(
            'bing_tai',
            'sheng_tai',
            'jue_tai',
            'offspring_void',
            'tianhou_void',
        );
});

test('three transmissions all in four meng establish xuantai', function (array $transmissions) {
    $match = (new XuantaiRule)->match(xuantai_facts([
        'sanchuan0' => $transmissions[0],
        'sanchuan1' => $transmissions[1],
        'sanchuan2' => $transmissions[2],
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['transmissions'])->toBe($transmissions)
        ->and($match?->evidence['foundations'][0]['matched'])->toBeTrue();
})->with([
    '申亥寅' => [[8, 11, 2]],
    '寅巳申' => [[2, 5, 8]],
    '亥寅巳' => [[11, 2, 5]],
    '允许孟神重复' => [[2, 2, 11]],
]);

test('any non meng transmission rejects xuantai', function (array $changes) {
    expect((new XuantaiRule)->match(xuantai_facts($changes)))->toBeNull();
})->with([
    '初传非孟' => [['sanchuan0' => 0]],
    '中传非孟' => [['sanchuan1' => 3]],
    '末传非孟' => [['sanchuan2' => 10]],
    '缺末传' => [['sanchuan2' => null]],
    '字符串不是合法传支' => [['sanchuan1' => '11']],
]);

test('progressive changsheng is bing tai and uses ding e speed semantics', function () {
    // 天盘相对地盘退三位：寅加巳、巳加申、申加亥、亥加寅。
    $tianpan = [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8];
    $match = (new XuantaiRule)->match(xuantai_facts(['tianpan' => $tianpan]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($judgments['bing_tai']['matched'])->toBeTrue()
        ->and($judgments['bing_tai']['description'])->toContain('主事速')
        ->and($judgments['sheng_tai']['matched'])->toBeFalse();
});

test('retrograde changsheng is sheng tai', function () {
    // 天盘相对地盘进三位：寅加亥、亥加申、申加巳、巳加寅。
    $tianpan = [3, 4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2];
    $match = (new XuantaiRule)->match(xuantai_facts(['tianpan' => $tianpan]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($judgments['sheng_tai']['matched'])->toBeTrue()
        ->and($judgments['sheng_tai']['description'])->toContain('主事迟')
        ->and($judgments['bing_tai']['matched'])->toBeFalse();
});

test('fanyin xuantai is jue tai without changing the main matcher', function () {
    $match = (new XuantaiRule)->match(xuantai_facts([
        'tianpan' => [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5],
        'calculationTrace' => [
            'plate_patterns' => ['fanyin'],
        ],
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($judgments['jue_tai']['matched'])->toBeTrue();
});

test('malformed tianpan does not block the main xuantai matcher', function () {
    $match = (new XuantaiRule)->match(xuantai_facts(['tianpan' => [1, 2, 3]]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($judgments['bing_tai']['matched'])->toBeFalse()
        ->and($judgments['sheng_tai']['matched'])->toBeFalse();
});
