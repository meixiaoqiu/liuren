<?php

/** 文件作用：验证天狱课冻结公式、囚死墓三个入口、斗系日本必要条件及固定长生墓表。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\TianyuRule;

function tianyu_facts(int $stem, int $initial, string $datetime, bool $douXiRiBen = true): PanFacts
{
    $tianpan = range(0, 11);
    $origin = TianyuRule::DAY_ORIGIN[$stem];
    $tianpan[$origin] = $douXiRiBen ? 4 : 5;

    return PanFacts::from(new PanResult([
        'rigan' => $stem,
        'sanchuan0' => $initial,
        'tianpan' => $tianpan,
        'calculationTime' => $datetime,
    ]));
}

test('tianyu matches qiu si and independent grave routes', function (int $stem, int $initial, string $datetime, array $routes) {
    $match = (new TianyuRule)->match(tianyu_facts($stem, $initial, $datetime));

    expect($match)->not->toBeNull()
        ->and($match->code)->toBe('lesson.tianyu')
        ->and($match->name)->toBe('天狱课')
        ->and($match->gua)->toBe('噬嗑')
        ->and($match->guaSymbol)->toBe('䷔')
        ->and($match->evidence['dou_xi_ri_ben'])->toBeTrue()
        ->and($match->evidence['matched_initial_routes'])->toBe($routes)
        ->and($match->evidence['judgments'])->toBe([]);
})->with([
    '囚 + 斗系日本' => [0, 8, '2026-03-01 12:00:00', ['seasonal_qiu']],
    '死 + 斗系日本' => [2, 7, '2026-03-01 12:00:00', ['seasonal_si']],
    '墓独立成立且状态不是囚死' => [1, 7, '2026-06-01 12:00:00', ['day_grave']],
    '死 + 墓 + 斗系日本' => [1, 7, '2026-03-01 12:00:00', ['seasonal_si', 'day_grave']],
]);

test('tianyu requires dou xi ri ben for every initial route', function (int $stem, int $initial, string $datetime) {
    expect((new TianyuRule)->match(tianyu_facts($stem, $initial, $datetime, false)))->toBeNull();
})->with([
    '囚但无斗系日本' => [0, 8, '2026-03-01 12:00:00'],
    '死但无斗系日本' => [2, 7, '2026-03-01 12:00:00'],
    '墓但无斗系日本' => [1, 7, '2026-06-01 12:00:00'],
]);

test('tianyu rejects unsupported seasonal states when initial is not the day grave', function (int $initial, string $datetime) {
    expect((new TianyuRule)->match(tianyu_facts(0, $initial, $datetime)))->toBeNull();
})->with([
    '斗系日本但初传无入口' => [5, '2026-03-01 12:00:00'],
    '休不能命中' => [0, '2026-03-01 12:00:00'],
    '旺不能命中' => [2, '2026-03-01 12:00:00'],
    '相不能命中' => [5, '2026-03-01 12:00:00'],
]);

test('tianyu preserves the frozen liuren origin and five-element grave tables', function () {
    expect(TianyuRule::DAY_ORIGIN)->toBe([11, 11, 2, 2, 8, 8, 5, 5, 8, 8])
        ->and(TianyuRule::DAY_GRAVE)->toBe([7, 7, 10, 10, 4, 4, 1, 1, 4, 4])
        ->and(TianyuRule::DAY_ORIGIN[1])->toBe(11)
        ->and(TianyuRule::DAY_GRAVE[1])->toBe(7)
        ->and(TianyuRule::DAY_ORIGIN[7])->toBe(5)
        ->and(TianyuRule::DAY_GRAVE[7])->toBe(1)
        ->and(TianyuRule::DAY_ORIGIN[8])->toBe(8)
        ->and(TianyuRule::DAY_ORIGIN[9])->toBe(8)
        ->and(TianyuRule::DAY_GRAVE[8])->toBe(4)
        ->and(TianyuRule::DAY_GRAVE[9])->toBe(4);
});

test('tianyu is registered exactly once after tianhuo', function () {
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());

    expect(array_values(array_filter($codes, fn (string $code): bool => $code === 'lesson.tianyu')))->toHaveCount(1)
        ->and(array_search('lesson.tianyu', $codes, true))->toBe(array_search('lesson.tianhuo', $codes, true) + 1);
});
