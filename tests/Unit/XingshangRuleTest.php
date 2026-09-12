<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\XingshangRule;

function xingshang_facts(int $stem, int $branch, int $initial, int $middle = 0, int $final = 0, ?array $querent = null, array $extra = []): PanFacts
{
    $data = ['rigan' => $stem, 'rizhi' => $branch, 'sanchuan0' => $initial, 'sanchuan1' => $middle, 'sanchuan2' => $final, ...$extra];
    if ($querent !== null) {
        $data['context'] = ['people' => [['role' => 'querent', ...$querent]]];
    }

    return PanFacts::from(new PanResult($data));
}

test('xingshang matches stem route using initial to target direction', function () {
    // 申(8)刑寅(2)，甲干寄寅。
    $match = (new XingshangRule)->match(xingshang_facts(0, 0, 8));
    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toBe(['stem'])
        ->and($match->evidence['routes'][0])->toMatchArray(['initial' => 8, 'punished' => 2, 'target' => 2, 'stem_lodge' => 2]);
});

test('xingshang matches branch route and rejects the reversed direction', function () {
    $rule = new XingshangRule;
    expect($rule->match(xingshang_facts(4, 2, 8))?->evidence['matched_routes'])->toBe(['branch'])
        // 寅刑巳不等于巳刑寅；巳实际刑申，且戊寄巳，故两路皆不成立。
        ->and($rule->match(xingshang_facts(4, 2, 5)))->toBeNull();
});

test('xingshang supports self punishment', function () {
    expect((new XingshangRule)->match(xingshang_facts(0, 6, 6))?->evidence['matched_routes'])->toBe(['branch']);
});

test('xingshang matches nianming and xingnian independently from real querent context', function () {
    $rule = new XingshangRule;
    $nianming = $rule->match(xingshang_facts(4, 0, 8, querent: ['nianming' => 2, 'xingnian' => 6]));
    $xingnian = $rule->match(xingshang_facts(4, 0, 6, querent: ['nianming' => 2, 'xingnian' => 6]));
    expect($nianming?->evidence['matched_routes'])->toBe(['nianming'])
        ->and($xingnian?->evidence['matched_routes'])->toBe(['xingnian']);
});

test('xingshang preserves all simultaneous routes', function () {
    $match = (new XingshangRule)->match(xingshang_facts(4, 2, 8, querent: ['nianming' => 2, 'xingnian' => 2]));
    expect($match?->evidence['matched_routes'])->toBe(['branch', 'nianming', 'xingnian'])
        ->and($match?->evidence['routes'])->toHaveCount(3);
});

test('xingshang still evaluates stem and branch without person context', function () {
    $pan = new PanResult(['rigan' => 4, 'rizhi' => 2, 'sanchuan0' => 8]);
    $codes = collect((new PanRuleEngine)->evaluate($pan))->pluck('code');
    expect($codes)->toContain('lesson.xingshang');
});

test('xingshang does not use legacy top level year-life fields', function () {
    $facts = xingshang_facts(4, 0, 8, extra: ['nianming' => 2, 'xingnian' => 2]);
    expect((new XingshangRule)->match($facts))->toBeNull();
});

test('xingshang ignores middle and final transmissions and does not require initial to be an upper deity', function () {
    $rule = new XingshangRule;
    expect($rule->match(xingshang_facts(4, 0, 5, 8, 8)))->toBeNull()
        ->and($rule->match(xingshang_facts(4, 2, 8, extra: ['tianpan' => range(0, 11)])))->not->toBeNull();
});

test('xingshang does not depend on the riding general', function () {
    $rule = new XingshangRule;
    $a = $rule->match(xingshang_facts(4, 2, 8, extra: ['tianjiang' => array_fill(0, 12, 0)]));
    $b = $rule->match(xingshang_facts(4, 2, 8, extra: ['tianjiang' => array_fill(0, 12, 6)]));
    expect($a?->evidence['matched_routes'])->toBe($b?->evidence['matched_routes']);
});

test('xingshang exposes frozen identity and evidence', function () {
    $match = (new XingshangRule)->match(xingshang_facts(0, 0, 8));
    expect($match?->code)->toBe('lesson.xingshang')->and($match?->name)->toBe('刑伤课')
        ->and($match?->gua)->toBe('讼')->and($match?->guaSymbol)->toBe('䷅')
        ->and($match?->evidence)->toHaveKeys(['initial', 'punished_branch', 'matched_routes', 'routes', 'foundations', 'judgments', 'uncovered'])
        ->and($match?->evidence['judgments'])->toBe([]);
});

test('xingshang is registered exactly once', function () {
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());
    expect(array_values(array_filter($codes, fn (string $code): bool => $code === 'lesson.xingshang')))->toHaveCount(1);
});
