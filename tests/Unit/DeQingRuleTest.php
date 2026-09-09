<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\DeQingRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;

function deqing_pan_for(string $datetime): PanResult
{
    return (new PanCalculator)->calculate($datetime);
}

function deqing_match(PanResult $pan): mixed
{
    return (new DeQingRule)->match(PanFacts::from($pan));
}

function deqing_engine_match(PanResult $pan): array
{
    return (new PanRuleEngine)->evaluate($pan);
}

/**
 * 把 querent 直接注入到 pan 的 context 字段中（已计算过 nianming/xingnian）。
 */
function deqing_with_querent(PanResult $pan, int $nianming, int $xingnian, int $xingnian_gan = 0, ?string $birth = null, string $gender = 'male'): PanResult
{
    $data = $pan->toArray();
    $data['context'] = ['people' => [[
        'role' => 'querent',
        'birth_datetime' => $birth ?? '1984-06-01T00:00:00+08:00',
        'gender' => $gender,
        'nianming' => $nianming,
        'xingnian' => $xingnian,
        'xingnian_gan' => $xingnian_gan,
    ]]];

    return new PanResult($data);
}

const DEQING_AUSPICIOUS = [0, 3, 5, 8, 10, 11];

/**
 * 2001-11-21 19:00 盘面常量：
 *   日干=戊(4), 日支=子(0), 月支=亥(11)
 *   干德=巳(5), 支德=巳(5), 天德=辰(4), 月德=寅(2)
 *   三传=巳 戌 卯
 *   tianpan: 巳(0) 午(1) 未(2) 申(3) 酉(4) 戌(5) 亥(6) 子(7) 丑(8) 寅(9) 卯(10) 辰(11)
 *   tianjiang: 太阴(0) 天后(1) 贵人(2) 螣蛇(3) 朱雀(4) 六合(5) 勾陈(6) 青龙(7) 天空(8) 白虎(9) 太常(10) 玄武(11)
 *   索引为 0=子, 1=丑, ..., 11=亥
 *   tianjiang 位置 (吉将索引): 0(子=太阴), 1(丑=天后), 2(寅=贵人), 5(巳=六合), 7(未=青龙), 10(戌=太常)
 *
 * 初传=巳(5) 乘太阴(10) → 吉将。
 * 命宫上神=initial：地盘子(0)上神=巳(5) → 本命=子 时命宫上神=initial；
 * 行年=未(7) → 宫上神=丑(8) ≠ initial。
 *
 * 天盘支 = 索引位置（地盘支）的上神：
 *   tianpan[0=子]=巳(5), tianpan[1=丑]=午(6), tianpan[2=寅]=未(7), tianpan[3=卯]=申(8),
 *   tianpan[4=辰]=酉(9), tianpan[5=巳]=戌(10), tianpan[6=午]=亥(11), tianpan[7=未]=子(0),
 *   tianpan[8=申]=丑(1), tianpan[9=酉]=寅(2), tianpan[10=戌]=卯(3), tianpan[11=亥]=辰(4)
 */
test('STEM_VIRTUES table matches expected [2,8,5,11,5,2,8,5,11,5]', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('STEM_VIRTUES');
    expect($const->getValue())->toBe([2, 8, 5, 11, 5, 2, 8, 5, 11, 5]);
});

test('BRANCH_VIRTUES table matches expected [5,6,7,8,9,10,11,0,1,2,3,4]', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('BRANCH_VIRTUES');
    expect($const->getValue())->toBe([5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4]);
});

test('HEAVENLY_VIRTUES table matches expected [5,8,7,8,11,10,11,2,1,2,5,4]', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('HEAVENLY_VIRTUES');
    expect($const->getValue())->toBe([5, 8, 7, 8, 11, 10, 11, 2, 1, 2, 5, 4]);
});

test('MONTHLY_VIRTUES table matches expected [11,8,5,2,11,8,5,2,11,8,5,2]', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('MONTHLY_VIRTUES');
    expect($const->getValue())->toBe([11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5, 2]);
});

test('STEM_VIRTUES enumerates 10 day stems', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('STEM_VIRTUES');
    expect($const->getValue())->toHaveCount(10);
});

test('BRANCH_VIRTUES enumerates 12 day branches', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('BRANCH_VIRTUES');
    expect($const->getValue())->toHaveCount(12);
});

test('HEAVENLY_VIRTUES enumerates 12 month branches', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('HEAVENLY_VIRTUES');
    expect($const->getValue())->toHaveCount(12);
});

test('MONTHLY_VIRTUES enumerates 12 month branches', function () {
    $reflection = new ReflectionClass(DeQingRule::class);
    $const = $reflection->getReflectionConstant('MONTHLY_VIRTUES');
    expect($const->getValue())->toHaveCount(12);
});

test('de-qing heavenly virtue table: 12 month branches each produce a match when initial=that virtue and rides auspicious and lands on nianming', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $tianpan = $pan->get('tianpan');

    for ($i = 0; $i < 12; $i++) {
        $expected = [5, 8, 7, 8, 11, 10, 11, 2, 1, 2, 5, 4][$i];

        // 找 tianpan 中等于 expected 的位置
        $pos = array_search($expected, $tianpan, true);
        expect($pos)->not->toBeFalse("tianpan should contain virtue $expected at month $i");

        // 强制该位置上神乘吉将（青龙）
        $custom = $pan->toArray();
        $custom['sanchuan0'] = $expected;
        $custom['sanchuan1'] = 0;
        $custom['sanchuan2'] = 0;
        $custom['yuezhi'] = $i;
        $custom['tianjiang'] = $pan->get('tianjiang');
        $custom['tianjiang'][$pos] = 5; // 强制乘青龙
        $newPan = deqing_with_querent(new PanResult($custom), $pos, 0);

        $match = deqing_match($newPan);
        expect($match)->not->toBeNull("month-branch index $i should produce a de-qing match")
            ->and($match->evidence['heavenly_virtue'])->toBe($expected)
            ->and($match->evidence['virtue_types'])->toContain('heavenly')
            ->and($match->evidence['matched_via'])->toContain('nianming');
    }
});

test('de-qing monthly virtue table: 12 month branches each produce a match when initial=that virtue and rides auspicious and lands on xingnian', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $tianpan = $pan->get('tianpan');

    for ($i = 0; $i < 12; $i++) {
        $expected = [11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5, 2][$i];

        $pos = array_search($expected, $tianpan, true);
        expect($pos)->not->toBeFalse("tianpan should contain virtue $expected at month $i");

        $custom = $pan->toArray();
        $custom['sanchuan0'] = $expected;
        $custom['sanchuan1'] = 0;
        $custom['sanchuan2'] = 0;
        $custom['yuezhi'] = $i;
        $custom['tianjiang'] = $pan->get('tianjiang');
        $custom['tianjiang'][$pos] = 5; // 强制乘青龙
        // nianming 取另一个位置使 tianpan[nm] != expected（避免本命同时命中）
        $nm = ($pos + 1) % 12;
        $newPan = deqing_with_querent(new PanResult($custom), $nm, $pos);

        $match = deqing_match($newPan);
        expect($match)->not->toBeNull("month-branch index $i should produce a de-qing match via xingnian")
            ->and($match->evidence['monthly_virtue'])->toBe($expected)
            ->and($match->evidence['virtue_types'])->toContain('monthly')
            ->and($match->evidence['matched_via'])->toContain('xingnian')
            ->and($match->evidence['matched_via'])->not->toContain('nianming');
    }
});

test('de-qing rejects when initial is not any virtue branch', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $data = $pan->toArray();
    $data['sanchuan0'] = 0; // 子（不在四德）
    $newPan = deqing_with_querent(new PanResult($data), 0, 0);

    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing rejects when initial is virtue but rides inauspicious general', function () {
    // 2001-11-21 19:00: 干德=支德=巳(5)
    // 巳(5) 的天将固定为太阴(10) 吉将；要构造 乘凶将版需改盘面
    // 改用 月德=寅(2)：寅的天将固定为贵人(2) 吉将，不行
    // 用天德=辰(4)：辰上神=亥(11)，天将=玄武(11) 不吉
    // 改 yuezhi=4 让天德=辰，initial=辰
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $data = $pan->toArray();
    $data['sanchuan0'] = 4; // 辰
    $data['yuezhi'] = 4; // 辰月 → 天德=辰
    $newPan = deqing_with_querent(new PanResult($data), 0, 0);

    // 辰(4) 乘玄武(11) → 不吉
    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing rejects when initial is virtue, rides auspicious, but does not land on nianming or xingnian', function () {
    // 用 /kejing 盘: initial=巳(5) 乘太阴(10) 吉将；
    // 找一个 nianming 位置使 tianpan[nm] != 5，且行年=同一个；
    // 辰(4) → 上神=酉(9) ≠ 5；戌(10) → 上神=卯(3) ≠ 5
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 4, 10);

    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing matches via nianming only (initial=巳, nianming=子, xingnian=辰)', function () {
    // 2001-11-21 19:00: initial=巳(5) 乘太阴(10) 吉将
    // tianpan[0=子]=巳(5) = initial → 命中本命路径
    // tianpan[4=辰]=酉(9) ≠ initial → 不命中行年路径
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 0, 4);

    $match = deqing_match($newPan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['matched_via'])->toBe(['nianming'])
        ->and($match->evidence['nianming']['initial_is_upper'])->toBeTrue()
        ->and($match->evidence['xingnian']['initial_is_upper'])->toBeFalse();
});

test('de-qing matches via xingnian only (initial=巳, nianming=辰, xingnian=子)', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 4, 0);

    $match = deqing_match($newPan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['matched_via'])->toBe(['xingnian'])
        ->and($match->evidence['nianming']['initial_is_upper'])->toBeFalse()
        ->and($match->evidence['xingnian']['initial_is_upper'])->toBeTrue();
});

test('de-qing matches when both nianming and xingnian initial=upper', function () {
    // 找一个 xingnian 位置使 tianpan[nm]=initial 且 tianpan[xn]=initial
    // tianpan 是 tianpan[i] 单值；不能两处都=initial 在同一盘面上。
    // 但 matched_via 应同时含 nianming 和 xingnian
    // 这里直接覆盖：nianming=子 → 上神=巳=initial；xingnian 也指=子 → 同样命中
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 0, 0);

    $match = deqing_match($newPan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['matched_via'])->toContain('nianming')
        ->and($match->evidence['matched_via'])->toContain('xingnian');
});

test('de-qing rejects when nianming upper rides auspicious but is NOT the initial', function () {
    // 找一个 nm 使 tianpan[nm] 乘吉将但 ≠ initial=巳
    // 寅(2) → 上神=未(7) → 青龙(5) 吉将 ≠ 5
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 2, 0);

    // 本命寅：上神=未，≠ initial=巳，且 initial 不在 寅宫 上；
    // 行年=子：上神=巳 = initial，命中 → 应仍匹配
    // 调整：xingnian 选一个 tianpan[xn] ≠ initial 的位置且上神不吉
    $newPan = deqing_with_querent($pan, 2, 4); // nm=寅, xn=辰；xg=酉(9) 不吉
    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing rejects when xingnian upper rides auspicious but is NOT the initial', function () {
    // 找一个 xn 使 tianpan[xn] 乘吉将但 ≠ initial=巳
    // 子(0) → 上神=巳(5)=initial；戌(10) → 上神=卯(3) 朱雀(4) 不吉；未(7) → 上神=丑(1) 后(1) 吉将 ≠ 5
    // nm=辰(4) → 上神=酉(9) ≠ 5
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 4, 7);

    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing rejects when 命支 本身 rides auspicious in some other position - only nianming upper matters', function () {
    // nianming=辰(4) → 上神=酉(9) 朱雀 不吉
    // xingnian=戌(10) → 上神=卯(3) 朱雀 不吉
    // 即便本盘 tianpan 任何位置=辰 乘 阴(10)（地盘子位 tianpan[0]=巳 不是辰），
    // 也只取 tianpan[辰]=酉 这个判定（不吉）
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $newPan = deqing_with_querent($pan, 4, 10);

    expect(deqing_match($newPan))->toBeNull();
});

test('de-qing not_evaluated when context is empty', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $pan = new PanResult([...$pan->toArray(), 'context' => ['people' => []]]);

    $codes = array_column(deqing_engine_match($pan), 'code');
    expect($codes)->not->toContain('lesson.de_qing');

    $engine = new PanRuleEngine;
    $notEvaluated = $engine->notEvaluated($pan);
    $found = false;
    foreach ($notEvaluated as $entry) {
        if ($entry['code'] === 'lesson.de_qing') {
            $found = true;
            expect($entry['notice'])->toContain('本命');
        }
    }
    expect($found)->toBeTrue();
});

test('de-qing not_evaluated when querent exists but nianming/xingnian fields are missing', function () {
    $pan = deqing_pan_for('2001-11-21 19:00:00');
    $data = $pan->toArray();
    $data['context'] = ['people' => [[
        'role' => 'querent', 'birth_datetime' => '1984-06-01T00:00:00+08:00', 'gender' => 'male',
    ]]];

    $codes = array_column(deqing_engine_match(new PanResult($data)), 'code');
    expect($codes)->not->toContain('lesson.de_qing');

    $engine = new PanRuleEngine;
    $notEvaluated = $engine->notEvaluated(new PanResult($data));
    $found = false;
    foreach ($notEvaluated as $entry) {
        if ($entry['code'] === 'lesson.de_qing') {
            $found = true;
        }
    }
    expect($found)->toBeTrue();
});

test('de-qing executable /kejing case 2001-11-21 19:00 with 1984-06-01 00:00 male matches via nianming', function () {
    $calc = new PanCalculator;
    $pan = $calc->calculate('2001-11-21 19:00:00');
    $fateBirth = $calc->calculate('1984-06-01 00:00:00');
    $fateCalc = new FateCalculator;
    $fate = $fateCalc->calculate(
        $fateBirth->get('nian_index'),
        $pan->get('nian_index'),
        'male'
    );

    $context = ['people' => [[
        'role' => 'querent', 'birth_datetime' => '1984-06-01T00:00:00+08:00', 'gender' => 'male',
        'nianming' => $fate['nianming'],
        'xingnian' => $fate['xingnian'],
        'xingnian_gan' => $fate['xingnian_gan'],
    ]]];
    $fullPan = new PanResult([...$pan->toArray(), 'context' => $context]);

    $match = deqing_match($fullPan);
    expect($match)->not->toBeNull()
        ->and($match->evidence['virtue_types'])->toContain('stem')
        ->and($match->evidence['virtue_types'])->toContain('branch')
        ->and($match->evidence['matched_via'])->toContain('nianming')
        ->and($match->evidence['nianming']['initial_is_upper'])->toBeTrue();
});

test('de-qing rule engine integration: 1984 male /kejing case → codes contains lesson.de_qing', function () {
    $calc = new PanCalculator;
    $pan = $calc->calculate('2001-11-21 19:00:00');
    $fateBirth = $calc->calculate('1984-06-01 00:00:00');
    $fateCalc = new FateCalculator;
    $fate = $fateCalc->calculate(
        $fateBirth->get('nian_index'),
        $pan->get('nian_index'),
        'male'
    );
    $context = ['people' => [[
        'role' => 'querent', 'birth_datetime' => '1984-06-01T00:00:00+08:00', 'gender' => 'male',
        'nianming' => $fate['nianming'],
        'xingnian' => $fate['xingnian'],
        'xingnian_gan' => $fate['xingnian_gan'],
    ]]];
    $fullPan = new PanResult([...$pan->toArray(), 'context' => $context]);

    $codes = array_column((new PanRuleEngine)->evaluate($fullPan), 'code');
    expect($codes)->toContain('lesson.de_qing');
});

test('de-qing evidence shape includes all four virtue fields plus initial_general, initial_rides_auspicious, nianming/xingnian detail, matched_via', function () {
    $calc = new PanCalculator;
    $pan = $calc->calculate('2001-11-21 19:00:00');
    $fateBirth = $calc->calculate('1984-06-01 00:00:00');
    $fateCalc = new FateCalculator;
    $fate = $fateCalc->calculate(
        $fateBirth->get('nian_index'),
        $pan->get('nian_index'),
        'male'
    );
    $context = ['people' => [[
        'role' => 'querent', 'birth_datetime' => '1984-06-01T00:00:00+08:00', 'gender' => 'male',
        'nianming' => $fate['nianming'],
        'xingnian' => $fate['xingnian'],
        'xingnian_gan' => $fate['xingnian_gan'],
    ]]];
    $fullPan = new PanResult([...$pan->toArray(), 'context' => $context]);
    $match = deqing_match($fullPan);

    expect($match->evidence)->toHaveKeys([
        'virtue_types', 'initial_branch', 'initial_general', 'initial_rides_auspicious',
        'day_stem', 'day_branch', 'month_branch',
        'stem_virtue', 'branch_virtue', 'heavenly_virtue', 'monthly_virtue',
        'nianming', 'xingnian', 'matched_via', 'uncovered',
    ]);
    expect($match->evidence['nianming'])->toHaveKeys([
        'ground', 'upper', 'general', 'auspicious', 'initial_is_upper',
    ]);
    expect($match->evidence['xingnian'])->toHaveKeys([
        'ground', 'upper', 'general', 'auspicious', 'initial_is_upper',
    ]);
});

test('de-qing rule engine still runs other 64-lesson rules alongside 1984 male /kejing case', function () {
    $calc = new PanCalculator;
    $pan = $calc->calculate('2001-11-21 19:00:00');
    $fateBirth = $calc->calculate('1984-06-01 00:00:00');
    $fateCalc = new FateCalculator;
    $fate = $fateCalc->calculate(
        $fateBirth->get('nian_index'),
        $pan->get('nian_index'),
        'male'
    );
    $context = ['people' => [[
        'role' => 'querent', 'birth_datetime' => '1984-06-01T00:00:00+08:00', 'gender' => 'male',
        'nianming' => $fate['nianming'],
        'xingnian' => $fate['xingnian'],
        'xingnian_gan' => $fate['xingnian_gan'],
    ]]];
    $fullPan = new PanResult([...$pan->toArray(), 'context' => $context]);

    $codes = array_column((new PanRuleEngine)->evaluate($fullPan), 'code');
    expect($codes)->toBeArray();
    expect(count($codes))->toBeGreaterThan(1);
    expect($codes)->toContain('lesson.de_qing');
});
