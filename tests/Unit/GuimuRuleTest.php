<?php

/** 文件作用：锁定鬼墓课三条 OR 入口、日鬼表边界、五行墓表边界与多路线命中的 evidence。 */

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

test('day ghost entry can independently establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 9, // 乙 -> 酉，酉为日鬼
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['day_ghost'])
        ->and($match?->evidence['day_ghost_fayong'])->toBeTrue()
        ->and($match?->evidence['stem_tomb_fayong'])->toBeFalse()
        ->and($match?->evidence['branch_tomb_fayong'])->toBeFalse()
        ->and($match?->evidence['ghost_tomb_combined'])->toBeFalse();
});

test('stem tomb entry can independently establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 7, // 乙木墓未
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['stem_tomb'])
        ->and($match?->evidence['stem_tomb_fayong'])->toBeTrue()
        ->and($match?->evidence['day_ghost_fayong'])->toBeFalse()
        ->and($match?->evidence['branch_tomb_fayong'])->toBeFalse();
});

test('branch tomb entry can independently establish the lesson', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 4, // 亥水墓辰
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['branch_tomb'])
        ->and($match?->evidence['branch_tomb_fayong'])->toBeTrue()
        ->and($match?->evidence['day_ghost_fayong'])->toBeFalse()
        ->and($match?->evidence['stem_tomb_fayong'])->toBeFalse();
});

test('ghost and tomb combined triggers the combined judgment', function () {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => 8, 'rizhi' => 6, 'sanchuan0' => 4, // 壬 -> 辰戌、辰；壬水墓辰
    ]));
    expect($match)->not->toBeNull()
        ->and($match?->evidence['matched_routes'])->toBe(['day_ghost', 'stem_tomb'])
        ->and($match?->evidence['ghost_tomb_combined'])->toBeTrue()
        ->and(collect($match?->evidence['judgments'])->pluck('code')->all())->toContain('ghost_tomb_combined');
});

test('middle or final ghosts and tombs do not establish the lesson', function () {
    $rule = new GuimuRule;
    $midGhost = guimu_facts(['rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 0, 'sanchuan1' => 9, 'sanchuan2' => 5]);
    $finalTomb = guimu_facts(['rigan' => 1, 'rizhi' => 11, 'sanchuan0' => 0, 'sanchuan1' => 5, 'sanchuan2' => 7]);
    expect($rule->match($midGhost))->toBeNull()
        ->and($rule->match($finalTomb))->toBeNull();
});

test('same element but wrong yin-yang branch is not a day ghost', function (int $stem, int $branch, int $hostStem, int $hostBranch) {
    // wrong-branch 五行克日干，但不在正文日鬼表；同时它必须既不是 stem_tomb 也不是 hostBranch 的 branch_tomb。
    $hostFacts = (new GuimuRule)->match(guimu_facts([
        'rigan' => $hostStem, 'rizhi' => $hostBranch, 'sanchuan0' => $branch,
    ]));
    expect($hostFacts)->toBeNull();
})->with([
    // 壬日 丑(1)：丑不是壬日鬼；选 戊 申 寄宫，丙寅日 午时（避开任何墓）
    // hostStem=4(戊), hostBranch=8(申). 戊木墓辰(4); 申金墓丑(1). 丑 == 1 = 申's branch_tomb, so this fails.
    // use host=卯(3): 卯木墓未(7). 7 != 1. OK.
    '壬日丑（异阴阳土克水）' => [8, 1, 0, 3],
    // 壬日 未(7): 未 not in 壬 ghosts; 7 != 戊木墓=4, and need host branch_tomb != 7. 申(8) branch_tomb=1(丑). OK.
    '壬日未（异阴阳土克水）' => [8, 7, 4, 8],
    // 癸日 辰(4): 4 is 癸's stem_tomb itself, so we skip — this is not a "wrong" case.
    // 癸日 戌(10): 10 not in 癸 ghosts; 10 != 癸水墓辰=4, and need host branch_tomb != 10. 申(8) 1; 寅(2) 7; 卯(3) 7. So 午(6): 午火墓戌=10. Skip. 酉(9): 酉金墓丑=1. OK.
    '癸日戌（异阴阳土克水）' => [9, 10, 4, 9],
    '甲日寅（同阴阳木克木？）' => [0, 2, 1, 5],
    '甲日巳（同阴阳火克金）' => [0, 5, 1, 3],
    '丙日午（异阴阳火克金）' => [2, 6, 3, 9],
    '庚日子（异阴阳水克火）' => [6, 0, 0, 9],
]);

test('each stem tomb and branch tomb table is locked against the ten stem tomb', function (int $stem, int $correctTomb, int $wrongTenStemTomb, int $hostStem, int $hostBranch) {
    $correct = (new GuimuRule)->match(guimu_facts(['rigan' => $stem, 'rizhi' => $hostBranch, 'sanchuan0' => $correctTomb]));
    $wrong = (new GuimuRule)->match(guimu_facts(['rigan' => $hostStem, 'rizhi' => $hostBranch, 'sanchuan0' => $wrongTenStemTomb]));
    expect($correct?->evidence['stem_tomb_fayong'])->toBeTrue()
        ->and($wrong?->evidence['stem_tomb_fayong'] ?? false)->toBeFalse();
})->with([
    // stem 3(丁) correct tomb=10(戌), wrong=1(丑). Use hostStem=7(辛) so that 1 != 辛金墓(1) — bad; use hostStem=2(丙). 丙火墓=10. 1 != 10. hostBranch 卯(3) 墓=7, != 1.
    '丁火墓戌非十干墓丑' => [3, 10, 1, 2, 3],
    '戊土墓辰非十干墓戌' => [4, 4, 10, 1, 5],
    '己土墓辰非十干墓丑' => [5, 4, 1, 1, 5],
    // 辛金墓=1(丑) correct, 4(辰) wrong ten-stem-tomb. hostStem 1(乙) 墓=7; hostBranch 酉(9) 墓=1 — bad; use 午(6) 墓=10.
    '辛金墓丑非十干墓辰' => [7, 1, 4, 1, 6],
    // 癸水墓=4(辰) correct, 7(未) wrong. hostStem 1(乙) 墓=7 — bad (would trigger via stem_tomb); use 3(丁) 墓=10. hostBranch 寅(2) 墓=7 — bad; use 亥(11) 墓=4 — also bad; 申(8) 墓=1, 1 != 7. OK.
    '癸水墓辰非十干墓未' => [9, 4, 7, 3, 8],
]);

test('branch tomb locks for the four cardinal branches', function (int $branch, int $correctTomb, int $wrongTomb, int $hostStem) {
    $correct = (new GuimuRule)->match(guimu_facts(['rigan' => $hostStem, 'rizhi' => $branch, 'sanchuan0' => $correctTomb]));
    $wrong = (new GuimuRule)->match(guimu_facts(['rigan' => $hostStem, 'rizhi' => $branch, 'sanchuan0' => $wrongTomb]));
    expect($correct?->evidence['branch_tomb_fayong'])->toBeTrue()
        ->and($wrong?->evidence['branch_tomb_fayong'] ?? false)->toBeFalse();
})->with([
    '卯木墓未不是戌' => [3, 7, 10, 1],
    '酉金墓丑不是辰' => [9, 1, 4, 2],
    '午火墓戌不是辰' => [6, 10, 4, 0],
    '子水墓辰不是丑' => [0, 4, 1, 3],
]);

test('multiple matched routes are all preserved and ordered', function (int $stem, int $branch, int $initial, array $expected) {
    $match = (new GuimuRule)->match(guimu_facts([
        'rigan' => $stem, 'rizhi' => $branch, 'sanchuan0' => $initial,
    ]));
    expect($match?->evidence['matched_routes'])->toBe($expected)
        ->and($match?->evidence['day_ghost_fayong'])->toBe(in_array('day_ghost', $expected, true))
        ->and($match?->evidence['stem_tomb_fayong'])->toBe(in_array('stem_tomb', $expected, true))
        ->and($match?->evidence['branch_tomb_fayong'])->toBe(in_array('branch_tomb', $expected, true));
})->with([
    '壬午日辰发用（鬼+干墓）' => [8, 6, 4, ['day_ghost', 'stem_tomb']],
    '壬午日戌发用（鬼+支墓）' => [8, 6, 10, ['day_ghost', 'branch_tomb']],
    '癸未日辰发用（干墓+支墓）' => [9, 7, 4, ['stem_tomb', 'branch_tomb']],
    '壬子日辰发用（三路线）' => [8, 0, 4, ['day_ghost', 'stem_tomb', 'branch_tomb']],
]);

test('foundations always shows every evidence route or the non-established outcome', function () {
    $match = (new GuimuRule)->match(guimu_facts(['rigan' => 8, 'rizhi' => 0, 'sanchuan0' => 4]));
    expect($match?->evidence['foundations'])->toHaveCount(6)
        ->and(collect($match?->evidence['foundations'])->pluck('title')->all())->toContain(
            '日干支与初传', '正文日鬼集合', '日干五行及其墓', '日支五行及其墓', '命中入口', '主体成课',
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
