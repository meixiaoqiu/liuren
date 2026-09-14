<?php

/** 文件作用：锁定九丑十日、丑临日支 matcher，以及正文严格型与宽解释的边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JiuchouRule;
use App\Domain\Pan\Rules\RuleRegistry;

function jiuchou_plate(int $ground): array
{
    $plate = range(0, 11);
    [$plate[1], $plate[$ground]] = [$plate[$ground], $plate[1]];

    return $plate;
}

function jiuchou_facts(array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'rigan' => 1, 'rizhi' => 3, 'shizhi' => 0, 'sanchuan0' => 1,
        'tianpan' => jiuchou_plate(3),
    ], $changes)));
}

test('all ten jiuchou days can match', function (int $stem, int $branch) {
    expect((new JiuchouRule)->match(jiuchou_facts([
        'rigan' => $stem, 'rizhi' => $branch, 'tianpan' => jiuchou_plate($branch),
    ])))->not->toBeNull();
})->with([
    '戊子' => [4, 0], '戊午' => [4, 6], '壬子' => [8, 0], '壬午' => [8, 6],
    '乙卯' => [1, 3], '乙酉' => [1, 9], '己卯' => [5, 3], '己酉' => [5, 9],
    '辛卯' => [7, 3], '辛酉' => [7, 9],
]);

test('jiuchou day and chou at day branch match with auditable evidence', function () {
    $match = (new JiuchouRule)->match(jiuchou_facts());
    expect($match)->not->toBeNull()
        ->and($match?->evidence)->toMatchArray([
            'day_stem' => 1, 'day_branch' => 3, 'day_ganzhi' => '乙卯', 'chou_ground' => 3,
            'is_jiuchou_day' => true, 'chou_at_day_branch' => true,
        ])
        ->and($match?->description)->toBe('九丑十日占课，天盘丑加临日支，为九丑课。');
});

test('non jiuchou day never matches despite chou at day branch or four zhong time and fayong', function () {
    expect((new JiuchouRule)->match(jiuchou_facts(['rigan' => 0])))->toBeNull();
});

test('jiuchou day without chou at day branch does not match', function () {
    expect((new JiuchouRule)->match(jiuchou_facts(['tianpan' => jiuchou_plate(6)])))->toBeNull();
});

test('chou at day stem lodging is not chou at day branch', function () {
    expect((new JiuchouRule)->match(jiuchou_facts([
        'rigan' => 1, 'rizhi' => 3, 'tianpan' => jiuchou_plate(4),
    ])))->toBeNull();
});

test('chou at another four zhong and fayong does not enter matcher', function () {
    expect((new JiuchouRule)->match(jiuchou_facts([
        'tianpan' => jiuchou_plate(9), 'sanchuan0' => 1,
    ])))->toBeNull();
});

test('chou need not fayong and hour need not be four zhong', function () {
    $match = (new JiuchouRule)->match(jiuchou_facts(['shizhi' => 2, 'sanchuan0' => 5]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['four_zhong_time'])->toBeFalse()
        ->and($match?->evidence['chou_fayong'])->toBeFalse()
        ->and($match?->evidence['strict_daquan_form'])->toBeFalse();
});

test('four zhong time alone and chou fayong alone cannot establish non jiuchou plate', function () {
    $rule = new JiuchouRule;
    expect($rule->match(jiuchou_facts(['rigan' => 0, 'sanchuan0' => 5])))->toBeNull()
        ->and($rule->match(jiuchou_facts(['rigan' => 0, 'shizhi' => 2, 'sanchuan0' => 1])))->toBeNull();
});

test('strict daquan judgment requires both four zhong time and chou fayong', function () {
    $rule = new JiuchouRule;
    $strict = $rule->match(jiuchou_facts());
    $onlyTime = $rule->match(jiuchou_facts(['sanchuan0' => 5]));
    $onlyFayong = $rule->match(jiuchou_facts(['shizhi' => 2]));
    expect($strict?->evidence['strict_daquan_form'])->toBeTrue()
        ->and(collect($strict?->evidence['judgments'])->pluck('code')->all())->toContain('strict_daquan_form')
        ->and($onlyTime?->evidence['strict_daquan_form'])->toBeFalse()
        ->and(collect($onlyTime?->evidence['judgments'])->pluck('code')->all())->not->toContain('strict_daquan_form')
        ->and($onlyFayong?->evidence['strict_daquan_form'])->toBeFalse()
        ->and(collect($onlyFayong?->evidence['judgments'])->pluck('code')->all())->not->toContain('strict_daquan_form');
});

test('registry places jiuchou immediately after yangjiu', function () {
    $codes = array_map(fn ($rule) => $rule->code(), (new RuleRegistry)->rules());
    $index = array_search('lesson.jiuchou', $codes, true);
    expect($index)->not->toBeFalse()->and($codes[$index - 1])->toBe('lesson.yangjiu');
});

test('missing or malformed plate data cannot match', function (array $changes) {
    expect((new JiuchouRule)->match(jiuchou_facts($changes)))->toBeNull();
})->with([
    'missing plate' => [['tianpan' => null]], 'short plate' => [['tianpan' => [1, 2]]],
    'string branch' => [['rizhi' => '3']], 'missing hour' => [['shizhi' => null]],
    'bad initial' => [['sanchuan0' => 12]],
]);
