<?php

/** 文件作用：验证天寇课分至整日、离辰、月宿加临、时区、发用边界与越界降级。 */

use App\Data\PanResult;
use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\TiankouRule;
use App\Services\PanCalculator;

function tiankou_lookup(int $palace = 4, ?Throwable $failure = null): MoonPalaceLookup
{
    return new class($palace, $failure) implements MoonPalaceLookup
    {
        public int $calls = 0;

        public ?DateTimeInterface $lastTime = null;

        public function __construct(private int $palace, private ?Throwable $failure) {}

        public function palaceAt(DateTimeInterface $time): int
        {
            $this->calls++;
            $this->lastTime = $time;
            if ($this->failure !== null) {
                throw $this->failure;
            }

            return $this->palace;
        }
    };
}

/** @return list<int> */
function tiankou_plate(int $moonPalace = 4, int $moonGround = 11): array
{
    $offset = ($moonPalace - $moonGround + 12) % 12;

    return array_map(fn (int $ground): int => ($ground + $offset) % 12, range(0, 11));
}

function tiankou_facts(string $datetime, array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'calculationTime' => $datetime,
        'rigan' => 0,
        'rizhi' => 0,
        'tianpan' => tiankou_plate(),
        'sanchuan0' => 1,
        'sanchuan1' => 2,
        'sanchuan2' => 3,
    ], $changes)));
}

test('four fen-zhi days match when moon palace stands on the previous-day branch', function (string $datetime, string $name) {
    $lookup = tiankou_lookup();
    $match = (new TiankouRule($lookup))->match(tiankou_facts($datetime));

    expect($match)->not->toBeNull()
        ->and($match->evidence['fen_zhi_name'])->toBe($name)
        ->and($match->evidence['previous_day'])->toBe('癸亥')
        ->and($match->evidence['li_branch'])->toBe(11)
        ->and($match->evidence['moon_palace_ground'])->toBe(11)
        ->and($lookup->calls)->toBe(1);
})->with([
    ['2026-03-20 12:00:00', '春分'],
    ['2026-06-21 12:00:00', '夏至'],
    ['2026-09-23 12:00:00', '秋分'],
    ['2026-12-22 12:00:00', '冬至'],
]);

test('fen-zhi day rejects a moon palace not standing on li-chen', function () {
    expect((new TiankouRule(tiankou_lookup()))->match(tiankou_facts('2026-03-20 12:00:00', [
        'tianpan' => tiankou_plate(moonGround: 10),
    ])))->toBeNull();
});

test('matching moon structure outside fen-zhi and traditional four-li eve both reject before lookup', function (string $datetime) {
    $lookup = tiankou_lookup();

    expect((new TiankouRule($lookup))->match(tiankou_facts($datetime)))->toBeNull()
        ->and($lookup->calls)->toBe(0);
})->with([
    'ordinary day' => ['2026-03-10 12:00:00'],
    'spring equinox eve' => ['2026-03-19 12:00:00'],
]);

test('moon absence from transmissions and moon use do not change the basic match', function (array $transmissions) {
    $match = (new TiankouRule(tiankou_lookup()))->match(tiankou_facts('2026-03-20 12:00:00', $transmissions));

    expect($match)->not->toBeNull();
})->with([
    'moon absent from all transmissions' => [['sanchuan0' => 1, 'sanchuan1' => 2, 'sanchuan2' => 3]],
    'moon is initial transmission' => [['sanchuan0' => 4, 'sanchuan1' => 2, 'sanchuan2' => 3]],
]);

test('calculation time is passed to moon lookup as fixed UTC plus eight', function () {
    $lookup = tiankou_lookup();
    expect((new TiankouRule($lookup))->match(tiankou_facts('2026-03-20 00:01:02')))->not->toBeNull()
        ->and($lookup->lastTime?->format('Y-m-d H:i:s P'))->toBe('2026-03-20 00:01:02 +08:00');
});

test('missing facts safely reject and moon table range errors degrade to no match', function () {
    expect((new TiankouRule(tiankou_lookup()))->match(tiankou_facts('2026-03-20 12:00:00', ['rigan' => null])))->toBeNull()
        ->and((new TiankouRule(tiankou_lookup(failure: new OutOfRangeException('outside table'))))->match(
            tiankou_facts('2026-03-20 12:00:00')
        ))->toBeNull();
});

test('fen-zhi fact uses the whole precise-term date and excludes adjacent dates', function (string $before, string $term, string $after, string $name) {
    $calculator = new PanCalculator;
    $termFact = PanFacts::from($calculator->calculate($term))->fenZhiDay();

    expect($termFact)->not->toBeNull()
        ->and($termFact['name'])->toBe($name)
        ->and($termFact['term_time'])->toBeGreaterThan(substr($term, 0, 10).' 00:00:00')
        ->and(PanFacts::from($calculator->calculate($before))->fenZhiDay())->toBeNull()
        ->and(PanFacts::from($calculator->calculate($after))->fenZhiDay())->toBeNull();
})->with([
    'spring equinox before exact term' => ['2026-03-19 12:00:00', '2026-03-20 00:00:00', '2026-03-21 00:00:00', '春分'],
    'summer solstice exact second' => ['2026-06-20 12:00:00', '2026-06-21 16:24:30', '2026-06-22 00:00:00', '夏至'],
    'autumn equinox after exact term' => ['2026-09-22 12:00:00', '2026-09-23 23:59:59', '2026-09-24 00:00:00', '秋分'],
    'winter solstice midday' => ['2026-12-21 12:00:00', '2026-12-22 12:00:00', '2026-12-23 00:00:00', '冬至'],
]);

test('metadata evidence and registry order are stable', function () {
    $match = (new TiankouRule(tiankou_lookup()))->match(tiankou_facts('2026-03-20 12:00:00'));
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());

    expect([$match?->code, $match?->name, $match?->gua, $match?->guaSymbol])->toBe(['lesson.tiankou', '天寇课', '蹇', '䷦'])
        ->and($match?->evidence)->toHaveKeys([
            'fen_zhi', 'fen_zhi_key', 'fen_zhi_name', 'fen_zhi_term_time', 'day_stem', 'day_branch',
            'previous_day_stem', 'previous_day_branch', 'li_branch', 'moon_palace', 'moon_palace_ground',
            'moon_palace_on_li_branch', 'moon_palace_source', 'moon_palace_model', 'foundations', 'uncovered',
        ])
        ->and(array_values(array_filter($codes, fn (string $code): bool => $code === 'lesson.tiankou')))->toHaveCount(1)
        ->and(array_search('lesson.tiankou', $codes, true))->toBe(array_search('lesson.tianyu', $codes, true) + 1);
});
