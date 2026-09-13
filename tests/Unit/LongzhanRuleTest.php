<?php

/** 文件作用：锁定龙战课严格同位、行年必要上下文及正文三交、夫妻年附加判断。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\LongzhanRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;

function longzhan_pan(array $changes = []): PanResult
{
    return new PanResult(array_replace([
        'rizhi' => 3,
        'sike' => [3, 3, 3, 3, 3, 3, 3, 3],
        'sanchuan0' => 3, 'sanchuan1' => 9, 'sanchuan2' => 3,
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 4),
        'context' => ['people' => [['role' => 'querent', 'nianming' => 6, 'xingnian' => 3]]],
    ], $changes));
}

function longzhan_match(array $changes = []): mixed
{
    return (new LongzhanRule)->match(PanFacts::from(longzhan_pan($changes)));
}

test('mao and you strict same-position routes both match', function (array $changes, int $branch) {
    $match = longzhan_match($changes);
    expect($match)->not->toBeNull()
        ->and($match->evidence)->toMatchArray([
            'day_branch' => $branch, 'initial' => $branch, 'querent_xingnian' => $branch,
            'matched_branch' => $branch, 'same_position' => true,
        ]);
})->with([
    '卯路' => [[], 3],
    '酉路' => [['rizhi' => 9, 'sanchuan0' => 9, 'context' => ['people' => [['role' => 'querent', 'xingnian' => 9]]]], 9],
]);

test('every mismatched mao-you position is rejected', function (array $changes) {
    expect(longzhan_match($changes))->toBeNull();
})->with([
    '卯日酉用行年卯' => [['sanchuan0' => 9]],
    '卯日卯用行年酉' => [['context' => ['people' => [['role' => 'querent', 'xingnian' => 9]]]]],
    '酉日卯用行年酉' => [['rizhi' => 9, 'context' => ['people' => [['role' => 'querent', 'xingnian' => 9]]]]],
    '酉日酉用行年卯' => [['rizhi' => 9, 'sanchuan0' => 9]],
]);

test('non-mao-you day cannot use the guanyuejing extension', function () {
    expect(longzhan_match(['rizhi' => 0]))->toBeNull();
});

test('nianming cannot replace xingnian', function () {
    expect(longzhan_match(['context' => ['people' => [['role' => 'querent', 'nianming' => 3, 'xingnian' => 4]]]]))->toBeNull();
});

test('missing querent xingnian is not evaluated', function () {
    $rule = new LongzhanRule;
    $pan = longzhan_pan(['context' => ['people' => [['role' => 'querent', 'nianming' => 3]]]]);
    $engine = new PanRuleEngine;
    expect($rule->requiredContext())->toBe(['people.querent.xingnian'])
        ->and($rule->notEvaluatedInfo()['notice'])->toContain('当前未进行判断')
        ->and(collect($engine->evaluate($pan))->pluck('code'))->not->toContain('lesson.longzhan')
        ->and(collect($engine->notEvaluated($pan))->pluck('code'))->toContain('lesson.longzhan');
});

test('sanjiao adds judgment but is not necessary for longzhan', function () {
    $without = longzhan_match();
    $with = longzhan_match([
        'sike' => [4, 0, 0, 3, 0, 6, 6, 9],
        'sanchuan0' => 3, 'sanchuan1' => 6, 'sanchuan2' => 9,
        'tianjiang' => [4, 4, 4, 10, 4, 4, 4, 4, 4, 4, 4, 4],
    ]);
    expect($without)->not->toBeNull()->and($without->evidence['judgments'])->toBe([])
        ->and($with)->not->toBeNull()
        ->and(collect($with->evidence['judgments'])->pluck('detail')->implode(' '))->toContain('主贼来必战');
});

test('spouse same xingnian adds judgment while absent or different spouse does not affect matching', function () {
    $same = longzhan_match(['context' => ['people' => [
        ['role' => 'querent', 'xingnian' => 3], ['role' => 'spouse', 'xingnian' => 3],
    ]]]);
    $different = longzhan_match(['context' => ['people' => [
        ['role' => 'querent', 'xingnian' => 3], ['role' => 'spouse', 'xingnian' => 9],
    ]]]);
    $absent = longzhan_match();
    expect($same)->not->toBeNull()
        ->and(collect($same->evidence['judgments'])->pluck('detail')->implode(' '))->toContain('主室家离散')
        ->and($different)->not->toBeNull()->and($different->evidence['judgments'])->toBe([])
        ->and($absent)->not->toBeNull()->and($absent->evidence['judgments'])->toBe([]);
});

test('production calculator and fate calculator reproduce the daquan ding-mao case', function () {
    $calculator = new PanCalculator;
    $pan = $calculator->calculate('2027-04-18 08:00:00');
    $birth = $calculator->calculate('2002-06-01 12:00:00');
    $fate = (new FateCalculator)->calculate($birth->get('nian_index'), $pan->get('nian_index'), 'male');
    $pan = new PanResult([...$pan->toArray(), 'context' => ['people' => [['role' => 'querent', ...$fate]]]]);
    $match = (new LongzhanRule)->match(PanFacts::from($pan));

    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([3, 3, 4, 10])
        ->and($pan->get('tianpan'))->toBe([6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5])
        ->and($pan->get('sike'))->toBe([3, 1, 1, 7, 3, 9, 9, 3])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([3, 9, 3])
        ->and($fate)->toMatchArray(['nianming' => 6, 'xingnian' => 3, 'xingnian_gan' => 7])
        ->and($match)->not->toBeNull();
});
