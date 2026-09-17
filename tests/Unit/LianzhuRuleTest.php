<?php

/** 文件作用：锁定第60课连珠课两条主体路线及进退、岁月日顺逆、三奇联珠边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LianzhuRule;
use App\Domain\Pan\Rules\RuleRegistry;

function lianzhu_facts(array $changes = []): PanFacts
{
    $base = [
        'nianzhi' => 8,
        'yuezhi' => 9,
        'rizhi' => 10,
        'sanchuan0' => 2,
        'sanchuan1' => 3,
        'sanchuan2' => 4,
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('lianzhu metadata and registry order are stable', function () {
    $rule = new LianzhuRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect($rule->code())->toBe('lesson.lianzhu')
        ->and([$rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['连珠课', '复', '䷗'])
        ->and(array_search('lesson.lianzhu', $codes, true))
        ->toBe(array_search('lesson.xuantai', $codes, true) + 1);
});

test('static definition keeps the two alternative routes in one foundation', function () {
    $definition = (new LianzhuRule)->definition();

    expect($definition['description'])->toBe(LianzhuRule::DESCRIPTION)
        ->and($definition['xiang'])->toBe(LianzhuRule::XIANG)
        ->and(array_column($definition['foundations'], 'code'))->toBe(['lianzhu_route'])
        ->and(array_column($definition['judgments'], 'code'))->toBe([
            'progressive_lianzhu',
            'retrograde_lianzhu',
            'year_month_day_forward',
            'day_month_year_reverse',
            'sanqi_lianzhu',
        ]);
});

test('all twelve forward and twelve reverse continuous triples establish lianzhu', function () {
    $rule = new LianzhuRule;

    for ($start = 0; $start < 12; $start++) {
        $forward = [$start, ($start + 1) % 12, ($start + 2) % 12];
        $forwardMatch = $rule->match(lianzhu_facts([
            'nianzhi' => 4,
            'yuezhi' => 8,
            'rizhi' => 0,
            'sanchuan0' => $forward[0],
            'sanchuan1' => $forward[1],
            'sanchuan2' => $forward[2],
        ]));

        expect($forwardMatch)->not->toBeNull()
            ->and($forwardMatch?->evidence['transmissions'])->toBe($forward)
            ->and($forwardMatch?->evidence['route_flags']['forward'])->toBeTrue();

        $reverse = [$start, ($start + 11) % 12, ($start + 10) % 12];
        $reverseMatch = $rule->match(lianzhu_facts([
            'nianzhi' => 4,
            'yuezhi' => 8,
            'rizhi' => 0,
            'sanchuan0' => $reverse[0],
            'sanchuan1' => $reverse[1],
            'sanchuan2' => $reverse[2],
        ]));

        expect($reverseMatch)->not->toBeNull()
            ->and($reverseMatch?->evidence['transmissions'])->toBe($reverse)
            ->and($reverseMatch?->evidence['route_flags']['reverse'])->toBeTrue();
    }
});

test('year month day forward route independently establishes lianzhu', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 8,
        'yuezhi' => 3,
        'rizhi' => 11,
        'sanchuan0' => 8,
        'sanchuan1' => 3,
        'sanchuan2' => 11,
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($match?->evidence['route_flags']['year_month_day_forward'])->toBeTrue()
        ->and($judgments['year_month_day_forward']['effect'])->toBe('neutral')
        ->and($judgments['year_month_day_forward']['matched'])->toBeTrue()
        ->and($judgments['progressive_lianzhu']['matched'])->toBeFalse();
});

test('day month year reverse route independently establishes lianzhu', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 8,
        'yuezhi' => 3,
        'rizhi' => 11,
        'sanchuan0' => 11,
        'sanchuan1' => 3,
        'sanchuan2' => 8,
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($match?->evidence['route_flags']['day_month_year_reverse'])->toBeTrue()
        ->and($judgments['day_month_year_reverse']['effect'])->toBe('neutral')
        ->and($judgments['day_month_year_reverse']['matched'])->toBeTrue();
});

test('calendar route permits repeated year month day branches because the sources do not forbid it', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 5,
        'yuezhi' => 5,
        'rizhi' => 9,
        'sanchuan0' => 5,
        'sanchuan1' => 5,
        'sanchuan2' => 9,
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['route_flags']['year_month_day_forward'])->toBeTrue();
});

test('hai zi chou additionally marks sanqi lianzhu', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'sanchuan0' => 11,
        'sanchuan1' => 0,
        'sanchuan2' => 1,
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($match?->evidence['route_flags']['forward'])->toBeTrue()
        ->and($judgments['sanqi_lianzhu']['matched'])->toBeTrue();
});

test('one pan may hit both the continuous route and the calendar route', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 2,
        'yuezhi' => 3,
        'rizhi' => 4,
        'sanchuan0' => 2,
        'sanchuan1' => 3,
        'sanchuan2' => 4,
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toContain('forward', 'year_month_day_forward');
});

test('invalid or unrelated transmissions do not establish lianzhu', function (array $changes) {
    expect((new LianzhuRule)->match(lianzhu_facts($changes)))->toBeNull();
})->with([
    'unrelated triple' => [[
        'nianzhi' => 0, 'yuezhi' => 4, 'rizhi' => 8,
        'sanchuan0' => 2, 'sanchuan1' => 6, 'sanchuan2' => 10,
    ]],
    'missing middle transmission' => [['sanchuan1' => null]],
    'string branch rejected' => [['sanchuan0' => '2']],
    'out of range branch rejected' => [['sanchuan2' => 12]],
]);
