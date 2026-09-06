<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\DiShengRule;
use App\Domain\Pan\Rules\HengtongRule;
use App\Domain\Pan\Rules\HuShengRule;
use App\Domain\Pan\Rules\HuWangRule;
use App\Domain\Pan\Rules\JuShengRule;
use App\Domain\Pan\Rules\JuWangRule;
use App\Domain\Pan\Rules\RuleMatch;

/**
 * 构造亨通课基础盘面：丙申日，三传申、亥、寅（递生顺）。
 * 干上神、支上神缺省取寅（2），不与日干、日支构成生旺关系，便于单独测试递生。
 *
 * @param  array<string, mixed>  $overrides
 */
function ht_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 8,
        'sanchuan1' => 11,
        'sanchuan2' => 2,
        'rigan' => 2,
        'rizhi' => 8,
        'sike' => [2, 2, 2, 2, 8, 2, 2, 2],
    ], $overrides));
}

/**
 * @param  class-string<HengtongRule|DiShengRule|JuShengRule|HuShengRule|JuWangRule|HuWangRule>  $rule
 * @param  array<string, mixed>  $overrides
 */
function ht_match(string $rule, array $overrides = []): ?RuleMatch
{
    return (new $rule)->match(PanFacts::from(ht_pan($overrides)));
}

test('hengtong lesson lists the matched grids in its foundations', function () {
    $match = ht_match(HengtongRule::class);

    expect($match)->not->toBeNull()
        ->and($match->gua)->toBe('渐')
        ->and($match->guaSymbol)->toBe('䷴')
        ->and($match->marker)->toBe('经')
        ->and($match->evidence['foundations'])->toHaveCount(1)
        ->and($match->evidence['foundations'][0]['title'])->toBe('递生格')
        ->and($match->evidence['foundations'][0]['detail'])->toBe('三传递生日干。')
        ->and($match->evidence['judgments'])->toBe([])
        ->and($match->evidence['uncovered'])->toHaveCount(7);
});

test('hengtong lesson lists multiple matched grids', function () {
    // 戊辰日，干上午、支上巳：火生土，俱生与互生同时成立。
    $match = ht_match(HengtongRule::class, [
        'sanchuan0' => 0,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'rigan' => 4,
        'rizhi' => 4,
        'sike' => [4, 6, 6, 6, 4, 5, 5, 5],
    ]);

    expect($match)->not->toBeNull()
        ->and(collect($match->evidence['foundations'])->pluck('title')->all())->toBe(['俱生格', '互生格']);
});

test('di-sheng grid matches the forward chain ending in the day stem', function () {
    $match = ht_match(DiShengRule::class);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('递生格')
        ->and($match->group)->toBe('亨通课体')
        ->and($match->marker)->toBe('格')
        ->and($match->evidence['detail'])->toContain('初生中、中生末、末生日干');
});

test('di-sheng grid matches the backward chain ending in the day stem', function () {
    $match = ht_match(DiShengRule::class, [
        'sanchuan0' => 9,
        'sanchuan1' => 1,
        'sanchuan2' => 5,
        'rigan' => 9,
        'rizhi' => 1,
    ]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['detail'])->toContain('末生中、中生初、初生日干');
});

test('ju-sheng grid matches when stem-upper births the stem and branch-upper births the branch', function () {
    $match = ht_match(JuShengRule::class, [
        'sanchuan0' => 0,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'rigan' => 2,
        'rizhi' => 2,
        'sike' => [2, 2, 2, 2, 2, 11, 11, 11],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('俱生格')
        ->and($match->evidence['detail'])->toContain('干上寅生日干丙');
});

test('hu-sheng grid matches when stem-upper births the branch and branch-upper births the stem', function () {
    $match = ht_match(HuShengRule::class, [
        'sanchuan0' => 0,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'rigan' => 7,
        'rizhi' => 3,
        'sike' => [7, 11, 11, 11, 3, 4, 4, 4],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('互生格')
        ->and($match->evidence['detail'])->toContain('干上亥生日支卯');
});

test('ju-wang grid matches when the uppers are each their own prosperity branch', function () {
    $match = ht_match(JuWangRule::class, [
        'sanchuan0' => 0,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'rigan' => 8,
        'rizhi' => 2,
        'sike' => [8, 0, 0, 0, 2, 3, 3, 3],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('俱旺格')
        ->and($match->evidence['detail'])->toContain('干上子为日干壬之旺神');
});

test('hu-wang grid matches when the uppers are each other\'s prosperity branch', function () {
    $match = ht_match(HuWangRule::class, [
        'sanchuan0' => 0,
        'sanchuan1' => 1,
        'sanchuan2' => 2,
        'rigan' => 0,
        'rizhi' => 8,
        'sike' => [0, 9, 9, 9, 8, 3, 3, 3],
    ]);

    expect($match)->not->toBeNull()
        ->and($match->name)->toBe('互旺格')
        ->and($match->evidence['detail'])->toContain('干上酉为日支申之旺神');
});

test('hengtong does not match when there is no generation or prosperity relation', function () {
    expect(ht_match(HengtongRule::class, [
        'sanchuan0' => 2,
        'sanchuan1' => 3,
        'sanchuan2' => 4,
        'rigan' => 0,
        'rizhi' => 0,
        'sike' => [0, 2, 2, 2, 0, 2, 2, 2],
    ]))->toBeNull();
});
