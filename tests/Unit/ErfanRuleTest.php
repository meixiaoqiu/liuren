<?php

/** 文件作用：验证二烦课的四正、四平、天地二烦合取、短路与古籍无年份结构。 */

use App\Data\PanResult;
use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ErfanRule;

function erfan_lookup(int $palace = 3, ?Throwable $failure = null): MoonPalaceLookup
{
    return new class($palace, $failure) implements MoonPalaceLookup
    {
        public int $calls = 0;

        public function __construct(private int $palace, private ?Throwable $failure) {}

        public function palaceAt(DateTimeInterface $time): int
        {
            $this->calls++;
            if ($this->failure !== null) {
                throw $this->failure;
            }

            return $this->palace;
        }
    };
}

/** @return list<int> */
function erfan_shifted_plate(int $offset = 9): array
{
    return array_map(fn (int $ground): int => ($ground + $offset) % 12, range(0, 11));
}

function erfan_facts(array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'yuejiang' => 3,
        'rizhi' => 6,
        'tianpan' => erfan_shifted_plate(),
        'calculationTime' => '2026-02-18 12:00:00', // 正月初二，非四正；卯将平午日。
    ], $changes)));
}

test('大全无年份第一例以 fake 月宿验证四平天地二烦结构', function () {
    $lookup = erfan_lookup(3);
    $match = (new ErfanRule($lookup))->match(erfan_facts());

    expect($match)->not->toBeNull()
        ->and($match->evidence['four_ping'])->toBeTrue()
        ->and($match->evidence['four_zheng'])->toBeFalse()
        ->and([$match->evidence['day_lodge_ground'], $match->evidence['moon_lodge_ground'], $match->evidence['dougang_ground']])->toBe([6, 6, 7])
        ->and($match->evidence['tianfan'])->toBeTrue()
        ->and($match->evidence['difan'])->toBeTrue()
        ->and($lookup->calls)->toBe(1);
});

test('大全无年份第二例以 fake 月宿验证十五四正天地二烦结构', function () {
    $lookup = erfan_lookup(3);
    $match = (new ErfanRule($lookup))->match(erfan_facts([
        'yuejiang' => 9, 'rizhi' => 3, 'calculationTime' => '2026-03-03 12:00:00',
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['lunar_day'])->toBe(15)
        ->and($match->evidence['four_zheng_type'])->toBe('full_moon_day')
        ->and($match->evidence['day_lodge_ground'])->toBe(0)
        ->and($match->evidence['moon_lodge_ground'])->toBe(6)
        ->and($match->evidence['dougang_ground'])->toBe(7);
});

test('四正五类日期均成立', function (string $date, int $day, string $type) {
    $match = (new ErfanRule(erfan_lookup()))->match(erfan_facts([
        'rizhi' => 2, 'calculationTime' => $date.' 12:00:00',
    ]));

    expect($match)->not->toBeNull()
        ->and($match->evidence['lunar_day'])->toBe($day)
        ->and($match->evidence['four_zheng_type'])->toBe($type);
})->with([
    ['2026-02-17', 1, 'new_moon_day'],
    ['2026-02-24', 8, 'first_quarter_day'],
    ['2026-03-03', 15, 'full_moon_day'],
    ['2026-03-11', 23, 'last_quarter_day'],
    ['2026-03-18', 30, 'month_end'],
    ['2026-04-16', 29, 'month_end'],
]);

test('非月终二十九不误判晦', function () {
    $lookup = erfan_lookup();
    expect((new ErfanRule($lookup))->match(erfan_facts([
        'rizhi' => 2, 'calculationTime' => '2026-03-17 12:00:00',
    ])))->toBeNull()->and($lookup->calls)->toBe(0);
});

test('四平四种映射全部成立', function (int $monthGeneral, int $dayBranch) {
    $match = (new ErfanRule(erfan_lookup()))->match(erfan_facts([
        'yuejiang' => $monthGeneral, 'rizhi' => $dayBranch,
    ]));
    expect($match)->not->toBeNull()
        ->and($match->evidence['four_ping_expected_branch'])->toBe($dayBranch);
})->with([[0, 3], [3, 6], [6, 9], [9, 0]]);

test('廉价前置条件逐项失败时不查询月宿', function (array $changes) {
    $lookup = erfan_lookup();
    expect((new ErfanRule($lookup))->match(erfan_facts($changes)))->toBeNull()
        ->and($lookup->calls)->toBe(0);
})->with([
    '非四仲月将' => [['yuejiang' => 2]],
    '四正四平均失败' => [['rizhi' => 2]],
    '日宿不临四仲' => [['tianpan' => range(0, 11)]],
    '斗罡不系丑未' => [['tianpan' => erfan_shifted_plate(6)]],
    '字段缺失' => [['calculationTime' => null]],
]);

test('只有天烦而无地烦不得命中', function () {
    $lookup = erfan_lookup(4);
    expect((new ErfanRule($lookup))->match(erfan_facts()))->toBeNull()
        ->and($lookup->calls)->toBe(1);
});

test('只有地烦而无天烦不得命中且被廉价条件短路', function () {
    $lookup = erfan_lookup(6);
    expect((new ErfanRule($lookup))->match(erfan_facts(['tianpan' => range(0, 11)])))->toBeNull()
        ->and($lookup->calls)->toBe(0);
});

test('需要月宿时只查询一次且范围异常原样抛出', function () {
    $lookup = erfan_lookup();
    expect((new ErfanRule($lookup))->match(erfan_facts()))->not->toBeNull()
        ->and($lookup->calls)->toBe(1);

    $failure = new OutOfRangeException('table out of range');
    expect(fn () => (new ErfanRule(erfan_lookup(failure: $failure)))->match(erfan_facts()))
        ->toThrow(OutOfRangeException::class, 'table out of range');
});

test('元数据与 evidence 完整', function () {
    $match = (new ErfanRule(erfan_lookup()))->match(erfan_facts());
    expect([$match?->code, $match?->name, $match?->group, $match?->gua, $match?->guaSymbol])
        ->toBe(['lesson.erfan', '二烦课', '六十四课', '明夷', '䷣'])
        ->and($match?->evidence)->toHaveKeys([
            'month_general', 'four_zhong_month_general', 'lunar_day', 'lunar_month_days',
            'four_zheng', 'four_zheng_type', 'day_branch', 'four_ping', 'four_ping_expected_branch',
            'day_lodge', 'day_lodge_ground', 'day_lodge_on_four_zhong',
            'moon_lodge', 'moon_lodge_ground', 'moon_lodge_on_four_zhong',
            'dougang', 'dougang_ground', 'dougang_on_chou_wei', 'tianfan', 'difan',
            'moon_palace_source', 'moon_palace_model', 'foundations', 'uncovered',
        ]);
});
