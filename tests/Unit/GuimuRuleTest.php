<?php

/** 文件作用：锁定鬼墓课 A' matcher（鬼 AND 墓）、日鬼表边界、五行墓表边界与多路线命中的 evidence。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\GuimuRule;
use App\Domain\Pan\Rules\RuleRegistry;

function guimu_facts(array $changes = []): PanFacts
{
    $base = [
        'rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 9, 'sanchuan1' => 7, 'sanchuan2' => 5,
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('metadata and registry order are stable', function () {
    $rule = new GuimuRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());
    expect($rule->code())->toBe('lesson.guimu')
        ->and([$rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['鬼墓课', '困', '䷮'])
        ->and(array_search('lesson.guimu', $codes, true))->toBe(array_search('lesson.jiuchou', $codes, true) + 1);
});

test('only day ghost cannot establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 1, 'rizhi' => 5, 'sanchuan0' => 9, // 乙 -> 酉，己巳日（巳火墓戌，不是 9）
    ]));
    expect($match)->toBeNull();
});

test('only stem tomb cannot establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 1, 'rizhi' => 0, 'sanchuan0' => 7, // 乙木墓未，壬日非壬
    ]));
    expect($match)->toBeNull();
});

test('only branch tomb cannot establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 0, 'rizhi' => 11, 'sanchuan0' => 4, // 亥水墓辰，甲不是壬癸
    ]));
    expect($match)->toBeNull();
});

test('ghost and stem tomb together establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 8, 'rizhi' => 6, 'sanchuan0' => 4, // 壬 -> 辰戌；壬水墓辰
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['day_ghost', 'stem_tomb'])
        ->and($match?->evidence['day_ghost_fayong'])->toBeTrue()
        ->and($match?->evidence['stem_tomb_fayong'])->toBeTrue()
        ->and($match?->evidence['branch_tomb_fayong'])->toBeFalse()
        ->and($match?->evidence['ghost_tomb_combined'])->toBeTrue();
});

test('ghost and branch tomb together establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 8, 'rizhi' => 6, 'sanchuan0' => 10, // 壬 -> 辰戌；午火墓戌
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['day_ghost', 'branch_tomb'])
        ->and($match?->evidence['day_ghost_fayong'])->toBeTrue()
        ->and($match?->evidence['branch_tomb_fayong'])->toBeTrue()
        ->and($match?->evidence['stem_tomb_fayong'])->toBeFalse()
        ->and(collect($match?->evidence['foundations'])->firstWhere('code', 'ghost_tomb_combined')['detail'])
        ->toContain('《六壬大全》', '本项目对「日辰墓神」采用 A\' 的程序解释')
        ->not->toContain('既作日鬼，又作日墓');
});

test('all three routes together establish the lesson with triple judgment', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 8, 'rizhi' => 0, 'sanchuan0' => 4, // 壬 -> 辰戌；壬水墓辰；子水墓辰
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['day_ghost', 'stem_tomb', 'branch_tomb'])
        ->and(collect($match?->evidence['foundations'])->pluck('code')->all())->toContain('ghost_tomb_combined')
        ->and(collect($match?->evidence['judgments'])->pluck('code')->all())->toBe(['triple_ghost_and_tombs']);
});

test('stem tomb and branch tomb without ghost never establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 9, 'rizhi' => 7, 'sanchuan0' => 4, // 癸水墓辰、未土墓辰
    ]));
    expect($match)->toBeNull();
});

test('middle or final ghosts and tombs do not establish the lesson', function () {
    $rule = new GuimuRule;
    $midGhost = guimu_facts(['rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 0, 'sanchuan1' => 9, 'sanchuan2' => 5]);
    $finalTomb = guimu_facts(['rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 0, 'sanchuan1' => 5, 'sanchuan2' => 7]);
    expect($rule->match($midGhost))->toBeNull()
        ->and($rule->match($finalTomb))->toBeNull();
});

test('same element but wrong yin-yang branch is not a day ghost', function (int $stem, int $dayBranch, int $initial) {
    expect((new GuimuRule)->match(guimu_facts([
        'rigan' => $stem, 'rizhi' => $dayBranch, 'sanchuan0' => $initial,
    ])))->toBeNull();
})->with([
    // 三盘的初传都是日支墓；若错误地把普通五行相克当日鬼，就会误命中 A'。
    '壬申日丑发用' => [8, 8, 1],
    '壬寅日未发用' => [8, 2, 7],
    '癸午日戌发用' => [9, 6, 10],
]);

test('each stem element tomb is locked against the ten-stem lifecycle tomb', function (int $stem, int $correctTomb, int $wrongTenStemTomb) {
    $table = (new ReflectionClass(GuimuRule::class))->getConstant('STEM_ELEMENT_TOMBS');
    expect($table[$stem])->toBe($correctTomb)
        ->and($table[$stem])->not->toBe($wrongTenStemTomb);
})->with([
    '丁火墓戌非十干墓丑' => [3, 10, 1],
    '戊土墓辰非十干墓戌' => [4, 4, 10],
    '己土墓辰非十干墓丑' => [5, 4, 1],
    '辛金墓丑非十干墓辰' => [7, 1, 4],
    '癸水墓辰非十干墓未' => [9, 4, 7],
]);

test('branch element tombs are locked for the four cardinal branches', function (int $branch, int $correctTomb, int $wrongTomb) {
    $table = (new ReflectionClass(GuimuRule::class))->getConstant('BRANCH_ELEMENT_TOMBS');
    expect($table[$branch])->toBe($correctTomb)
        ->and($table[$branch])->not->toBe($wrongTomb);
})->with([
    '卯木墓未不是戌' => [3, 7, 10],
    '酉金墓丑不是辰' => [9, 1, 4],
    '午火墓戌不是辰' => [6, 10, 4],
    '子水墓辰不是丑' => [0, 4, 1],
]);

test('multiple matched routes are all preserved and ordered', function (int $stem, int $branch, int $initial, array $expected) {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => $stem, 'rizhi' => $branch, 'sanchuan0' => $initial,
    ]));
    expect($match?->evidence['matched_routes'])->toBe($expected)
        ->and($match?->evidence['day_ghost_fayong'])->toBeTrue()
        ->and($match?->evidence['stem_tomb_fayong'])->toBe(in_array('stem_tomb', $expected, true))
        ->and($match?->evidence['branch_tomb_fayong'])->toBe(in_array('branch_tomb', $expected, true));
})->with([
    '壬午日辰发用（鬼+干墓）' => [8, 6, 4, ['day_ghost', 'stem_tomb']],
    '壬午日戌发用（鬼+支墓）' => [8, 6, 10, ['day_ghost', 'branch_tomb']],
    '壬子日辰发用（三路线）' => [8, 0, 4, ['day_ghost', 'stem_tomb', 'branch_tomb']],
]);

test('foundations always shows every evidence route or the non-established outcome', function () {
    $match = (new GuimuRule)->match(guimu_facts(['rigan' => 8, 'rizhi' => 0, 'sanchuan0' => 4]));
    expect($match?->evidence['foundations'])->toHaveCount(6)
        ->and(collect($match?->evidence['foundations'])->pluck('title')->all())->toContain(
            '日干支与初传', '正文日鬼集合', '日干五行及其墓', '日支五行及其墓', '命中入口', '主体成课（鬼墓兼见）',
        );
});

test('no match returns null and produces no evidence', function () {
    expect((new GuimuRule)->match(guimu_facts(['rigan' => 3, 'rizhi' => 7, 'sanchuan0' => 3])))->toBeNull();
});

test('malformed facts cannot match', function (array $changes) {
    expect((new GuimuRule)->match(guimu_facts($changes)))->toBeNull();
})->with([
    'string stem' => [['rigan' => '1']],
    'string branch' => [['rizhi' => '11']],
    'missing initial' => [['sanchuan0' => null]],
    'out of range initial' => [['sanchuan0' => 12]],
    'out of range stem' => [['rigan' => 10]],
]);
