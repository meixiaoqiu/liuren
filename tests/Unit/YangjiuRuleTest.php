<?php

/** 文件作用：锁定殃咎课七条 OR 路线、各路线内部 AND 边界、墓表及十二天将固定五行。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\YangjiuRule;

function yangjiu_facts(array $changes = []): PanFacts
{
    $base = [
        'rigan' => 6, 'rizhi' => 0,
        'sanchuan0' => 0, 'sanchuan1' => 3, 'sanchuan2' => 6,
        'sanchuan0tianjiang' => 0, 'sanchuan1tianjiang' => 0, 'sanchuan2tianjiang' => 0,
        'sike' => [8, 0, 0, 3, 0, 0, 0, 6],
        'tianpan' => range(0, 11),
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

function yangjiu_route(array $changes, string $route): bool
{
    return (new YangjiuRule)->analyze(yangjiu_facts($changes))['routes'][$route]['matched'];
}

function yangjiu_swap_tianpan(array $pairs): array
{
    $plate = range(0, 11);
    foreach ($pairs as [$a, $b]) {
        [$plate[$a], $plate[$b]] = [$plate[$b], $plate[$a]];
    }

    return $plate;
}

test('metadata and registry order are stable', function () {
    $rule = new YangjiuRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());
    expect($rule->code())->toBe('lesson.yangjiu')
        ->and($codes[array_search('lesson.yangjiu', $codes, true) - 1])->toBe('lesson.zaie');
});

test('twelve generals use their fixed elements independently of position', function () {
    $facts = yangjiu_facts();
    expect(array_map(fn ($general) => $facts->generalElement($general), range(0, 11)))
        ->toBe([2, 1, 1, 0, 2, 0, 2, 3, 2, 4, 3, 4]);
});

test('forward recursive overcoming requires all three ordered links', function () {
    $base = ['rigan' => 5, 'rizhi' => 5, 'sanchuan0' => 5, 'sanchuan1' => 8, 'sanchuan2' => 2];
    expect(yangjiu_route($base, 'forward_recursive_overcoming'))->toBeTrue();
    foreach ([['sanchuan0' => 0], ['sanchuan1' => 0], ['sanchuan2' => 0]] as $break) {
        expect(yangjiu_route(array_replace($base, $break), 'forward_recursive_overcoming'))->toBeFalse();
    }
});

test('reverse recursive overcoming requires all three ordered links', function () {
    $base = ['rigan' => 2, 'rizhi' => 0, 'sanchuan0' => 0, 'sanchuan1' => 7, 'sanchuan2' => 2];
    expect(yangjiu_route($base, 'reverse_recursive_overcoming'))->toBeTrue();
    foreach ([['sanchuan2' => 0], ['sanchuan1' => 3], ['sanchuan0' => 3]] as $break) {
        expect(yangjiu_route(array_replace($base, $break), 'reverse_recursive_overcoming'))->toBeFalse();
    }
});

test('mutual transmission overcoming without overcoming stem and wrong directions reject recursive routes', function () {
    expect(yangjiu_route(['rigan' => 0, 'sanchuan0' => 5, 'sanchuan1' => 8, 'sanchuan2' => 2], 'forward_recursive_overcoming'))->toBeFalse()
        ->and(yangjiu_route(['rigan' => 5, 'sanchuan0' => 8, 'sanchuan1' => 5, 'sanchuan2' => 2], 'forward_recursive_overcoming'))->toBeFalse();
});

test('initial sandwiched overcoming needs ground and general to overcome the same initial', function () {
    $plate = yangjiu_swap_tianpan([[6, 11]]);
    $base = ['sanchuan0' => 6, 'sanchuan0tianjiang' => 9, 'tianpan' => $plate];
    expect(yangjiu_route($base, 'initial_transmission_sandwiched_overcoming'))->toBeTrue()
        ->and(yangjiu_route(array_replace($base, ['sanchuan0tianjiang' => 5]), 'initial_transmission_sandwiched_overcoming'))->toBeFalse()
        ->and(yangjiu_route(array_replace($base, ['tianpan' => range(0, 11)]), 'initial_transmission_sandwiched_overcoming'))->toBeFalse();
});

test('middle sandwiched overcoming never substitutes for initial', function () {
    $plate = yangjiu_swap_tianpan([[6, 11]]);
    expect(yangjiu_route([
        'sanchuan0' => 3, 'sanchuan1' => 6, 'sanchuan1tianjiang' => 9,
        'sanchuan0tianjiang' => 5, 'tianpan' => $plate,
    ], 'initial_transmission_sandwiched_overcoming'))->toBeFalse();
});

test('external battle requires all three generals to overcome their gods', function () {
    $base = ['sanchuan0' => 10, 'sanchuan1' => 6, 'sanchuan2' => 2,
        'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7];
    expect(yangjiu_route($base, 'all_three_external_battle'))->toBeTrue();
    foreach ([0, 1, 2] as $index) {
        $changes = $base;
        $changes['sanchuan'.$index.'tianjiang'] = $index === 0 ? 7 : 0;
        expect(yangjiu_route($changes, 'all_three_external_battle'))->toBeFalse();
    }
});

test('external battle rejects one internal direction and equality', function () {
    $base = ['sanchuan0' => 10, 'sanchuan1' => 6, 'sanchuan2' => 2,
        'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7];
    expect(yangjiu_route(array_replace($base, ['sanchuan2tianjiang' => 3]), 'all_three_external_battle'))->toBeFalse()
        ->and(yangjiu_route(array_replace($base, ['sanchuan2tianjiang' => 5]), 'all_three_external_battle'))->toBeFalse();
});

test('internal battle requires all three gods to overcome their generals', function () {
    $base = ['sanchuan0' => 9, 'sanchuan1' => 1, 'sanchuan2' => 5,
        'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7];
    expect(yangjiu_route($base, 'all_three_internal_battle'))->toBeTrue();
    foreach ([0, 1, 2] as $index) {
        $changes = $base;
        $changes['sanchuan'.$index.'tianjiang'] = $index === 0 ? 0 : 2;
        expect(yangjiu_route($changes, 'all_three_internal_battle'))->toBeFalse();
    }
});

test('internal battle rejects one external direction and equality', function () {
    $base = ['sanchuan0' => 9, 'sanchuan1' => 1, 'sanchuan2' => 5,
        'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7];
    expect(yangjiu_route(array_replace($base, ['sanchuan2tianjiang' => 11]), 'all_three_internal_battle'))->toBeFalse()
        ->and(yangjiu_route(array_replace($base, ['sanchuan2tianjiang' => 1]), 'all_three_internal_battle'))->toBeFalse();
});

test('riding tomb requires both stem and branch upper gods', function () {
    $base = ['rigan' => 2, 'rizhi' => 2, 'sike' => [5, 10, 0, 3, 2, 7, 0, 6]];
    expect(yangjiu_route($base, 'stem_branch_riding_tombs'))->toBeTrue()
        ->and(yangjiu_route(['rigan' => 2, 'rizhi' => 2, 'sike' => [5, 10, 0, 3, 2, 6, 0, 6]], 'stem_branch_riding_tombs'))->toBeFalse()
        ->and(yangjiu_route(['rigan' => 2, 'rizhi' => 2, 'sike' => [5, 9, 0, 3, 2, 7, 0, 6]], 'stem_branch_riding_tombs'))->toBeFalse();
});

test('riding tomb uses stem five element tomb rather than ten stem tomb', function (int $stem, int $correctTomb, int $wrongTenStemTomb) {
    $correct = [0, $correctTomb, 0, 3, 2, 7, 0, 6];
    $wrong = [0, $wrongTenStemTomb, 0, 3, 2, 7, 0, 6];
    expect(yangjiu_route(['rigan' => $stem, 'rizhi' => 2, 'sike' => $correct], 'stem_branch_riding_tombs'))->toBeTrue()
        ->and(yangjiu_route(['rigan' => $stem, 'rizhi' => 2, 'sike' => $wrong], 'stem_branch_riding_tombs'))->toBeFalse();
})->with([
    '乙木墓未非十干墓戌' => [1, 7, 10], '丁火墓戌非十干墓丑' => [3, 10, 1],
    '戊土墓辰非十干墓戌' => [4, 4, 10], '己土墓辰非十干墓丑' => [5, 4, 1],
    '辛金墓丑非十干墓辰' => [7, 1, 4], '癸水墓辰非十干墓未' => [9, 4, 7],
]);

test('daquan ji-wei stem and branch both riding chen tomb matches', function () {
    $analysis = (new YangjiuRule)->analyze(yangjiu_facts([
        'rigan' => 5, 'rizhi' => 7, 'sike' => [7, 4, 0, 3, 7, 4, 0, 6],
    ]));
    expect($analysis['routes']['stem_branch_riding_tombs']['matched'])->toBeTrue();
});

test('sitting tomb requires both heaven branches on their tomb ground palaces', function () {
    $plate = yangjiu_swap_tianpan([[4, 11], [1, 8]]);
    expect(yangjiu_route(['rigan' => 8, 'rizhi' => 8, 'tianpan' => $plate], 'stem_branch_sitting_on_tombs'))->toBeTrue()
        ->and(yangjiu_route(['rigan' => 8, 'rizhi' => 8, 'tianpan' => yangjiu_swap_tianpan([[4, 11]])], 'stem_branch_sitting_on_tombs'))->toBeFalse()
        ->and(yangjiu_route(['rigan' => 8, 'rizhi' => 8, 'tianpan' => yangjiu_swap_tianpan([[1, 8]])], 'stem_branch_sitting_on_tombs'))->toBeFalse();
});

test('daquan ding-chou wei on xu and chou on chen sitting tomb matches', function () {
    $plate = yangjiu_swap_tianpan([[10, 7], [4, 1]]);
    $analysis = (new YangjiuRule)->analyze(yangjiu_facts(['rigan' => 3, 'rizhi' => 1, 'tianpan' => $plate]));
    expect($analysis['routes']['stem_branch_sitting_on_tombs']['matched'])->toBeTrue();
});

test('riding and sitting tombs cannot be confused', function () {
    $riding = ['rigan' => 2, 'rizhi' => 2, 'sike' => [5, 10, 0, 3, 2, 7, 0, 6]];
    $sitting = ['rigan' => 8, 'rizhi' => 8, 'tianpan' => yangjiu_swap_tianpan([[4, 11], [1, 8]])];
    expect(yangjiu_route($riding, 'stem_branch_sitting_on_tombs'))->toBeFalse()
        ->and(yangjiu_route($sitting, 'stem_branch_riding_tombs'))->toBeFalse();
});

test('each of seven routes can independently establish yangjiu', function (array $changes, string $route) {
    $analysis = (new YangjiuRule)->analyze(yangjiu_facts($changes));
    expect($analysis['matched_routes'])->toBe([$route])
        ->and((new YangjiuRule)->match(yangjiu_facts($changes)))->not->toBeNull();
})->with([
    'A' => [['rigan' => 5, 'rizhi' => 5, 'sanchuan0' => 5, 'sanchuan1' => 8, 'sanchuan2' => 2], 'forward_recursive_overcoming'],
    'B' => [['rigan' => 2, 'rizhi' => 0, 'sanchuan0' => 0, 'sanchuan1' => 7, 'sanchuan2' => 2], 'reverse_recursive_overcoming'],
    'C' => [['sanchuan0' => 6, 'sanchuan0tianjiang' => 9, 'tianpan' => yangjiu_swap_tianpan([[6, 11]])], 'initial_transmission_sandwiched_overcoming'],
    'D' => [['sanchuan0' => 10, 'sanchuan1' => 6, 'sanchuan2' => 2, 'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7], 'all_three_external_battle'],
    'E' => [['sanchuan0' => 9, 'sanchuan1' => 1, 'sanchuan2' => 5, 'sanchuan0tianjiang' => 3, 'sanchuan1tianjiang' => 11, 'sanchuan2tianjiang' => 7], 'all_three_internal_battle'],
    'F' => [['rigan' => 2, 'rizhi' => 2, 'sike' => [5, 10, 0, 3, 2, 7, 0, 6]], 'stem_branch_riding_tombs'],
    'G' => [['rigan' => 8, 'rizhi' => 8, 'tianpan' => yangjiu_swap_tianpan([[4, 11], [1, 8]])], 'stem_branch_sitting_on_tombs'],
]);

test('judgments and uncovered notes do not affect matcher', function () {
    $match = (new YangjiuRule)->match(yangjiu_facts(['rigan' => 5, 'rizhi' => 5, 'sanchuan0' => 5, 'sanchuan1' => 8, 'sanchuan2' => 2]));
    expect($match?->evidence['judgments'])->toBe([])
        ->and($match?->evidence['uncovered'])->not->toBeEmpty()
        ->and($match?->evidence['matched_routes'])->toBe(['forward_recursive_overcoming']);
});
