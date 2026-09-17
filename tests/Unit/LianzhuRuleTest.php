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

test('all eight same-quarter meng zhong ji sequences establish lianzhu', function (array $transmissions, string $route) {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'sanchuan0' => $transmissions[0],
        'sanchuan1' => $transmissions[1],
        'sanchuan2' => $transmissions[2],
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['transmissions'])->toBe($transmissions)
        ->and($match?->evidence['route_flags'][$route])->toBeTrue();
})->with([
    '寅卯辰' => [[2, 3, 4], 'meng_forward'],
    '巳午未' => [[5, 6, 7], 'meng_forward'],
    '申酉戌' => [[8, 9, 10], 'meng_forward'],
    '亥子丑' => [[11, 0, 1], 'meng_forward'],
    '辰卯寅' => [[4, 3, 2], 'meng_reverse'],
    '未午巳' => [[7, 6, 5], 'meng_reverse'],
    '戌酉申' => [[10, 9, 8], 'meng_reverse'],
    '丑子亥' => [[1, 0, 11], 'meng_reverse'],
]);

test('mere numerical continuity across quarters does not establish the meng route', function (array $transmissions) {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 0,
        'yuezhi' => 2,
        'rizhi' => 8,
        'sanchuan0' => $transmissions[0],
        'sanchuan1' => $transmissions[1],
        'sanchuan2' => $transmissions[2],
    ]));

    expect($match)->toBeNull();
})->with([
    '辰巳午' => [[4, 5, 6]],
    '子丑寅' => [[0, 1, 2]],
    '午巳辰' => [[6, 5, 4]],
    '寅丑子' => [[2, 1, 0]],
]);

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
        ->and($judgments['progressive_lianzhu']['matched'])->toBeTrue()
        ->and($judgments['sanqi_lianzhu']['matched'])->toBeTrue();
});

test('one pan may hit both the meng route and the calendar route', function () {
    $match = (new LianzhuRule)->match(lianzhu_facts([
        'nianzhi' => 2,
        'yuezhi' => 3,
        'rizhi' => 4,
        'sanchuan0' => 2,
        'sanchuan1' => 3,
        'sanchuan2' => 4,
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toContain('meng_forward', 'year_month_day_forward');
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
