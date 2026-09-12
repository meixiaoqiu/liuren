<?php

/** 文件作用：验证天祸课严格同向干支合取、四立整日口径与生产盘双入口。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\TianhuoRule;
use App\Services\PanCalculator;

function tianhuo_facts(array $changes = [], string $datetime = '2004-05-05 12:00:00'): PanFacts
{
    $data = ['rigan' => 0, 'rizhi' => 8, 'tianpan' => range(0, 11), 'calculationTime' => $datetime];

    return PanFacts::from(new PanResult(array_replace_recursive($data, $changes)));
}

test('tianhuo matches both strict directions through production calculator', function (string $datetime, string $direction) {
    $facts = PanFacts::from((new PanCalculator)->calculate($datetime));
    $match = (new TianhuoRule)->match($facts);

    expect($match)->not->toBeNull()
        ->and($match->evidence['four_li']['name'])->toBe('立夏')
        ->and($match->evidence['today'])->toBe('甲申')
        ->and($match->evidence['yesterday'])->toBe('癸未')
        ->and($match->evidence['matched_directions'])->toBe([$direction]);
})->with([
    ['2004-05-05 15:00:00', 'today_on_yesterday'],
    ['2004-05-05 19:00:00', 'yesterday_on_today'],
]);

test('tianhuo rejects a matching plate outside a four-li day', function () {
    $tianpan = range(0, 11);
    $tianpan[1] = 2;
    $tianpan[7] = 8;

    expect((new TianhuoRule)->match(tianhuo_facts(['tianpan' => $tianpan], '2004-05-04 12:00:00')))->toBeNull();
});

test('tianhuo requires stem and branch in the same direction', function (array $set) {
    $tianpan = array_fill(0, 12, 11);
    foreach ($set as $ground => $upper) {
        $tianpan[$ground] = $upper;
    }

    expect((new TianhuoRule)->match(tianhuo_facts(['tianpan' => $tianpan])))->toBeNull();
})->with([
    'only today stem' => [[1 => 2]],
    'only today branch' => [[7 => 8]],
    'only yesterday stem' => [[2 => 1]],
    'only yesterday branch' => [[8 => 7]],
    'crossed directions' => [[1 => 2, 8 => 7]],
]);

test('four-li fact includes the whole term date but not adjacent dates', function () {
    $calculator = new PanCalculator;
    $beforeTerm = PanFacts::from($calculator->calculate('2026-02-04 00:00:00'))->fourLiDay();

    expect($beforeTerm)->not->toBeNull()
        ->and($beforeTerm['name'])->toBe('立春')
        ->and($beforeTerm['date'])->toBe('2026-02-04')
        ->and($beforeTerm['term_time'])->toBeGreaterThan('2026-02-04 00:00:00')
        ->and(PanFacts::from($calculator->calculate('2026-02-03 23:00:00'))->fourLiDay())->toBeNull()
        ->and(PanFacts::from($calculator->calculate('2026-02-05 00:00:00'))->fourLiDay())->toBeNull();
});

test('tianhuo exposes stable evidence and is registered exactly once after xingshang', function () {
    $match = (new TianhuoRule)->match(PanFacts::from((new PanCalculator)->calculate('2004-05-05 15:00:00')));
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());

    expect($match?->code)->toBe('lesson.tianhuo')->and($match?->name)->toBe('天祸课')
        ->and($match?->gua)->toBe('大过')->and($match?->guaSymbol)->toBe('䷛')
        ->and($match?->evidence)->toHaveKeys([
            'four_li', 'today', 'yesterday', 'today_stem_lodge', 'yesterday_stem_lodge',
            'today_stem_on_yesterday_stem', 'today_branch_on_yesterday_branch',
            'yesterday_stem_on_today_stem', 'yesterday_branch_on_today_branch',
        ])
        ->and($match?->evidence['judgments'])->toBe([])
        ->and(array_values(array_filter($codes, fn (string $code): bool => $code === 'lesson.tianhuo')))->toHaveCount(1)
        ->and(array_search('lesson.tianhuo', $codes, true))->toBe(array_search('lesson.xingshang', $codes, true) + 1);
});
