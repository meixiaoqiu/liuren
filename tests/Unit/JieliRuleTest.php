<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\JieliRule;
use App\Services\PanCalculator;

function jieli_fixture(int $fu, int $qi, int $pointer, bool $wifeQuerent = false): PanFacts
{
    $tianpan = array_map(fn (int $branch): int => ($branch + $pointer) % 12, range(0, 11));
    $husband = ['role' => $wifeQuerent ? 'spouse' : 'querent', 'gender' => 'male', 'xingnian' => $fu];
    $wife = ['role' => $wifeQuerent ? 'querent' : 'spouse', 'gender' => 'female', 'xingnian' => $qi];

    return PanFacts::from(new PanResult([
        'tianpan' => $tianpan,
        'context' => ['people' => $wifeQuerent ? [$wife, $husband] : [$husband, $wife]],
    ]));
}

function jieli_production(): array
{
    $calculator = new PanCalculator;
    $fateCalculator = new FateCalculator;
    $pan = $calculator->calculate('2026-03-01 05:00:00');
    $husbandBirth = $calculator->calculate('1986-08-01 00:00:00');
    $wifeBirth = $calculator->calculate('1994-08-01 00:00:00');
    $husbandFate = $fateCalculator->calculate($husbandBirth->get('nian_index'), $pan->get('nian_index'), 'male');
    $wifeFate = $fateCalculator->calculate($wifeBirth->get('nian_index'), $pan->get('nian_index'), 'female');
    $result = new PanResult([
        ...$pan->toArray(),
        'context' => ['people' => [
            ['role' => 'querent', 'gender' => 'male', 'birth_datetime' => '1986-08-01T00:00', ...$husbandFate],
            ['role' => 'spouse', 'gender' => 'female', 'birth_datetime' => '1994-08-01T00:00', ...$wifeFate],
        ]],
    ]);

    return [$result, (new JieliRule)->match(PanFacts::from($result))];
}

test('jieli reproduces the daquan husband-wu wife-zi example', function () {
    [$pan, $match] = jieli_production();

    expect([$pan->get('shizhi'), $pan->get('yuejiang')])->toBe([3, 11])
        ->and($pan->get('tianpan'))->toBe([8, 9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7])
        ->and($match)->not->toBeNull()
        ->and([$match->evidence['fu_xingnian'], $match->evidence['qi_xingnian']])->toBe([6, 0])
        ->and([$match->evidence['fu_upper'], $match->evidence['qi_upper']])->toBe([2, 8])
        ->and($match->evidence['xingnian_clash'])->toBeTrue()
        ->and($match->evidence['xingnian_ke'])->toBeTrue()
        ->and($match->evidence['fu_lower_qi_upper_ke'])->toBeTrue()
        ->and($match->evidence['fu_upper_qi_lower_ke'])->toBeFalse()
        ->and($match->evidence['upper_upper_ke'])->toBeTrue();
});

test('jieli accepts clash without elemental restraint', function () {
    $match = (new JieliRule)->match(jieli_fixture(1, 7, 1)); // 丑未冲，土土不克
    expect($match)->not->toBeNull()
        ->and($match->evidence['xingnian_clash'])->toBeTrue()
        ->and($match->evidence['xingnian_ke'])->toBeFalse();
});

test('jieli accepts elemental restraint without clash', function () {
    $match = (new JieliRule)->match(jieli_fixture(0, 1, 0)); // 子水克丑土？按项目五行表为土克水
    expect($match)->not->toBeNull()
        ->and($match->evidence['xingnian_clash'])->toBeFalse()
        ->and($match->evidence['xingnian_ke'])->toBeTrue();
});

test('jieli accepts either cross independently', function (int $pointer, bool $a, bool $b) {
    $match = (new JieliRule)->match(jieli_fixture(0, 1, $pointer));
    expect($match)->not->toBeNull()
        ->and($match->evidence['fu_lower_qi_upper_ke'])->toBe($a)
        ->and($match->evidence['fu_upper_qi_lower_ke'])->toBe($b);
})->with([
    'husband lower to wife upper only' => [4, true, false],
    'husband upper to wife lower only' => [2, false, true],
]);

test('jieli rejects first condition without either cross', function () {
    expect((new JieliRule)->match(jieli_fixture(0, 1, 1)))->toBeNull();
});

test('jieli rejects cross when spouses annual fates neither clash nor restrain', function () {
    expect((new JieliRule)->match(jieli_fixture(0, 2, 1)))->toBeNull();
});

test('jieli does not promote upper-upper restraint into the formal second condition', function () {
    $facts = jieli_fixture(0, 1, 1); // 上上有克，但两条十字均无克
    expect((new JieliRule)->match($facts))->toBeNull();
});

test('jieli identifies husband and wife by gender even when wife is querent', function () {
    $match = (new JieliRule)->match(jieli_fixture(6, 0, 8, true));
    expect($match)->not->toBeNull()
        ->and($match->evidence['fu_xingnian'])->toBe(6)
        ->and($match->evidence['qi_xingnian'])->toBe(0);
});

test('jieli returns null without spouse or opposite-sex pair', function () {
    $tianpan = range(0, 11);
    $missing = PanFacts::from(new PanResult(['tianpan' => $tianpan, 'context' => ['people' => [
        ['role' => 'querent', 'gender' => 'male', 'xingnian' => 6],
    ]]]));
    $sameGender = PanFacts::from(new PanResult(['tianpan' => $tianpan, 'context' => ['people' => [
        ['role' => 'querent', 'gender' => 'male', 'xingnian' => 6],
        ['role' => 'spouse', 'gender' => 'male', 'xingnian' => 0],
    ]]]));

    expect((new JieliRule)->match($missing))->toBeNull()
        ->and((new JieliRule)->match($sameGender))->toBeNull();
});

test('jieli does not require nianming transmissions or generals', function () {
    $match = (new JieliRule)->match(jieli_fixture(6, 0, 8));
    expect($match)->not->toBeNull()
        ->and($match->gua)->toBeNull()
        ->and($match->guaSymbol)->toBeNull();
});
