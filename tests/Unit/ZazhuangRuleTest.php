<?php

/** 文件作用：锁定第63课杂状课的普遍入口、纯杂体系、固定物色/太玄数与五态修正。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\ZazhuangRule;

function zazhuang_facts(array $changes = []): PanFacts
{
    $initial = array_key_exists('sanchuan0', $changes) ? $changes['sanchuan0'] : 0;
    $ground = $changes['ground'] ?? 0;
    unset($changes['ground']);
    $offset = is_int($initial) ? ($initial - $ground + 12) % 12 : 0;
    $tianpan = array_map(fn (int $position): int => ($position + $offset) % 12, range(0, 11));

    return PanFacts::from(new PanResult(array_replace([
        'calculationTime' => '2031-03-01 12:00:00',
        'rigan' => 0,
        'sanchuan0' => $initial,
        'tianpan' => $tianpan,
    ], $changes)));
}

test('zazhuang metadata and registry order are stable', function () {
    $rule = new ZazhuangRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    $match = $rule->match(zazhuang_facts());

    expect([$rule->code(), $rule::NAME, $rule::GROUP])->toBe(['lesson.zazhuang', '杂状课', '六十四课'])
        ->and($rule->definition()['xiang'])->toBe(ZazhuangRule::XIANG)
        ->and($match?->xiang)->toBe(ZazhuangRule::XIANG)
        ->and([$match?->gua, $match?->guaSymbol])->toBe([null, null])
        ->and(array_search('lesson.zazhuang', $codes, true))->toBe(array_search('lesson.liuchun', $codes, true) + 1);
});

test('legal initial always matches while missing malformed or out of range initial is rejected', function ($initial) {
    $match = (new ZazhuangRule)->match(zazhuang_facts(['sanchuan0' => $initial]));
    if (is_int($initial) && $initial >= 0 && $initial <= 11) {
        expect($match)->not->toBeNull();
    } else {
        expect($match)->toBeNull();
    }
})->with([0, 1, 11, null, '6', -1, 12]);

test('the four cardinal branches are pure and the other eight are mixed', function (int $branch, string $purity) {
    $match = (new ZazhuangRule)->match(zazhuang_facts(['sanchuan0' => $branch, 'rigan' => 0]));
    expect($match?->evidence['purity'])->toBe($purity)
        ->and($match?->evidence['mixed_subtype'])->toBe($purity === 'pure' ? null : ($branch === 11 ? 'birth_mixed' : ($branch === 7 ? 'death_mixed' : 'ordinary_mixed')));
})->with([
    '子纯' => [0, 'pure'], '午纯' => [6, 'pure'], '卯纯' => [3, 'pure'], '酉纯' => [9, 'pure'],
    '丑杂' => [1, 'mixed'], '寅杂' => [2, 'mixed'], '辰杂' => [4, 'mixed'], '巳杂' => [5, 'mixed'],
    '未杂' => [7, 'mixed'], '申杂' => [8, 'mixed'], '戌杂' => [10, 'mixed'], '亥杂' => [11, 'mixed'],
]);

test('birth mixed covers all ten stems', function (int $stem, int $branch) {
    $match = (new ZazhuangRule)->match(zazhuang_facts(['rigan' => $stem, 'sanchuan0' => $branch]));
    expect($match?->evidence['mixed_subtype'])->toBe('birth_mixed')
        ->and($match?->evidence['mixed_subtype_label'])->toBe('生杂');
})->with(array_map(null, range(0, 9), ZazhuangRule::BIRTH_BRANCHES_BY_STEM));

test('death mixed covers all ten stems', function (int $stem, int $branch) {
    $match = (new ZazhuangRule)->match(zazhuang_facts(['rigan' => $stem, 'sanchuan0' => $branch]));
    expect($match?->evidence['mixed_subtype'])->toBe('death_mixed')
        ->and($match?->evidence['mixed_subtype_label'])->toBe('死杂');
})->with(array_map(null, range(0, 9), ZazhuangRule::DEATH_BRANCHES_BY_STEM));

test('ordinary mixed does not overlap birth or death mixed', function () {
    $match = (new ZazhuangRule)->match(zazhuang_facts(['rigan' => 0, 'sanchuan0' => 2]));
    expect($match?->evidence['mixed_subtype'])->toBe('ordinary_mixed');
});

test('all twelve fixed branch colors are preserved including compound colors', function (int $branch, array $colors) {
    expect(ZazhuangRule::BRANCH_COLORS[$branch])->toBe($colors);
})->with([
    [0, ['黑']], [1, ['黄']], [2, ['绯', '碧']], [3, ['青']], [4, ['黄']], [5, ['斑点绿']],
    [6, ['赤']], [7, ['黄']], [8, ['白', '黑']], [9, ['白']], [10, ['黄']], [11, ['淡青']],
]);

test('all twelve tai xuan numbers are fixed', function () {
    expect(ZazhuangRule::TAI_XUAN_NUMBERS)->toBe([9, 8, 7, 6, 5, 4, 9, 8, 7, 6, 5, 4]);
});

test('five seasonal states use distinct frozen multipliers without rounding halves', function (string $state, int|float $multiplier, int|float $adjusted) {
    expect(ZazhuangRule::NUMBER_MULTIPLIERS[$state])->toBe($multiplier)
        ->and(ZazhuangRule::adjustedNumber(55, $multiplier))->toBe($adjusted);
})->with([
    '旺' => ['旺', 10, 550], '相' => ['相', 2, 110], '休' => ['休', 1, 55], '囚' => ['囚', 0.5, 27.5], '死' => ['死', 0.5, 27.5],
]);

test('lower deity is the ground position under initial rather than day time or month branches', function () {
    $match = (new ZazhuangRule)->match(zazhuang_facts([
        'sanchuan0' => 6, 'ground' => 9, 'rizhi' => 1, 'shizhi' => 2, 'yuejiang' => 11,
    ]));
    expect($match?->evidence['ground'])->toBe(9)
        ->and($match?->evidence['ground_name'])->toBe('酉')
        ->and($match?->evidence['lower_element_name'])->toBe('金')
        ->and($match?->evidence['lower_colors'])->toBe(['白'])
        ->and($match?->evidence['lower_number'])->toBe(6);
});
