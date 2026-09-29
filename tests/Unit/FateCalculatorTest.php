<?php

use App\Domain\Pan\FateCalculator;
use App\Services\PanCalculator;
use com\tyme\solar\SolarTime;

/**
 * 用 PanCalculator（Tyme）得出干支年序号，再交 FateCalculator 推算行年。
 *
 * @return array{nianming: int, xingnian: int, xingnian_gan: int}
 */
function fateFor(string $birth, string $current, string $gender): array
{
    $calculator = app(PanCalculator::class);

    return (new FateCalculator)->calculate(
        $calculator->calculate($birth)->get('nian_index'),
        $calculator->calculate($current)->get('nian_index'),
        $gender,
    );
}

/** 断言行年天干与地支构成合法六十甲子（不只是单个数字）。 */
function assertValidGanzhi(array $fate): void
{
    expect(in_array([$fate['xingnian_gan'], $fate['xingnian']], PanCalculator::$jiazi2Ganzhi, true))
        ->toBeTrue();
}

/** 把 Tyme SolarTime 格式化为 PanCalculator 可接收的字符串。 */
function formatSolar(SolarTime $time): string
{
    return sprintf(
        '%04d-%02d-%02d %02d:%02d:%02d',
        $time->getYear(),
        $time->getMonth(),
        $time->getDay(),
        $time->getHour(),
        $time->getMinute(),
        $time->getSecond(),
    );
}

test('male 49 years old reaches jia-yin per guanyuejing', function () {
    $fate = fateFor('1952-06-01 00:00:00', '2000-03-15 13:00:00', 'male');

    expect($fate['xingnian_gan'])->toBe(0)
        ->and($fate['xingnian'])->toBe(2)
        ->and($fate['nianming'])->toBe(4);
    assertValidGanzhi($fate);
});

test('female 34 years old reaches ji-hai per guanyuejing', function () {
    $fate = fateFor('1967-06-01 00:00:00', '2000-03-15 13:00:00', 'female');

    expect($fate['xingnian_gan'])->toBe(5)
        ->and($fate['xingnian'])->toBe(11)
        ->and($fate['nianming'])->toBe(7);
    assertValidGanzhi($fate);
});

test('birth and current both after lichun keep a valid ganzhi', function () {
    $fate = fateFor('1986-08-01 00:00:00', '2026-06-01 12:00:00', 'male');

    expect($fate['nianming'])->toBe(2)
        ->and($fate['xingnian'])->toBe(6)
        ->and($fate['xingnian_gan'])->toBe(2);
    assertValidGanzhi($fate);
});

test('birth before lichun and current after lichun yields gui-si', function () {
    // 出生 2000-02-01 立春前为己卯年，起课 2026-06-01 为丙午年，年序差 27，男行年癸巳。
    $fate = fateFor('2000-02-01 12:00:00', '2026-06-01 12:00:00', 'male');

    expect($fate['nianming'])->toBe(3)
        ->and($fate['xingnian_gan'])->toBe(9)
        ->and($fate['xingnian'])->toBe(5);
    assertValidGanzhi($fate);
});

test('birth after lichun and current before lichun keeps a valid ganzhi', function () {
    // 出生 2000-06-01 为庚辰年，起课 2026-01-15 立春前为乙巳年，年序差 25，女行年丁未。
    $fate = fateFor('2000-06-01 12:00:00', '2026-01-15 12:00:00', 'female');

    expect($fate['nianming'])->toBe(4)
        ->and($fate['xingnian_gan'])->toBe(3)
        ->and($fate['xingnian'])->toBe(7);
    assertValidGanzhi($fate);
});

test('annual fate respects the lichun second boundary for both genders', function () {
    $calculator = app(PanCalculator::class);
    $fate = new FateCalculator;
    $birthIndex = $calculator->calculate('2000-06-01 12:00:00')->get('nian_index');

    // 找到 2026 年立春的精确交节时刻（节气 index 为 3）。
    $cursor = SolarTime::fromYmdHms(2026, 2, 1, 0, 0, 0)->getTerm();
    while ($cursor->getIndex() !== 3) {
        $cursor = $cursor->next(1);
    }
    $lichun = $cursor->getJulianDay()->getSolarTime();

    $beforeIndex = $calculator->calculate(formatSolar($lichun->next(-1)))->get('nian_index');
    $atIndex = $calculator->calculate(formatSolar($lichun))->get('nian_index');
    $afterIndex = $calculator->calculate(formatSolar($lichun->next(1)))->get('nian_index');

    // Tyme 以交节当秒进入新干支年：当秒与后一秒同干支年，与前一秒异。
    expect($beforeIndex)->not->toBe($atIndex)
        ->and($atIndex)->toBe($afterIndex);

    // 精确行年结果（出生庚辰 index 16；立春前乙巳 index 41；立春后丙午 index 42）。
    expect($fate->calculate($birthIndex, $beforeIndex, 'male'))
        ->toBe(['nianming' => 4, 'xingnian' => 3, 'xingnian_gan' => 7])
        ->and($fate->calculate($birthIndex, $beforeIndex, 'female'))
        ->toBe(['nianming' => 4, 'xingnian' => 7, 'xingnian_gan' => 3])
        ->and($fate->calculate($birthIndex, $afterIndex, 'male'))
        ->toBe(['nianming' => 4, 'xingnian' => 4, 'xingnian_gan' => 8])
        ->and($fate->calculate($birthIndex, $afterIndex, 'female'))
        ->toBe(['nianming' => 4, 'xingnian' => 6, 'xingnian_gan' => 2]);

    foreach (['male', 'female'] as $gender) {
        assertValidGanzhi($fate->calculate($birthIndex, $beforeIndex, $gender));
        assertValidGanzhi($fate->calculate($birthIndex, $afterIndex, $gender));
    }
});
