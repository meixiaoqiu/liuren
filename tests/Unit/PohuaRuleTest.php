<?php

/** 文件作用：锁定魄化课“白虎乘死神/死气 AND 四路 OR”及课义判断边界，尤其保护临辰不克且不发用仍成立。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\PohuaRule;
use App\Domain\Pan\Rules\RuleRegistry;

function pohua_facts(array $changes = [], int $tigerBranch = 10, int $ground = 2, ?int $xingnian = null): PanFacts
{
    $tianpan = range(0, 11);
    [$tianpan[$ground], $tianpan[$tigerBranch]] = [$tianpan[$tigerBranch], $tianpan[$ground]];
    $tianjiang = array_fill(0, 12, 0);
    $tianjiang[$ground] = 7;
    $data = array_replace([
        'calculationTime' => '2026-06-15 12:00:00',
        'yuezhi' => 7, // 六月未：死神戌、死气亥。
        'rigan' => 0, 'rizhi' => 4, 'sanchuan0' => 0,
        'tianpan' => $tianpan, 'tianjiang' => $tianjiang,
    ], $changes);
    if ($xingnian !== null) {
        $data['context'] = ['people' => [['role' => 'querent', 'xingnian' => $xingnian]]];
    }

    return PanFacts::from(new PanResult($data));
}

test('death spirit independently matches each of the four OR routes', function (string $route, array $changes, int $ground, ?int $xingnian) {
    $match = (new PohuaRule)->match(pohua_facts($changes, ground: $ground, xingnian: $xingnian));
    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toContain($route);
})->with([
    '临日' => ['day', [], 2, null],
    '临辰' => ['branch', ['rizhi' => 4], 4, null],
    '临行年' => ['xingnian', [], 6, 6],
    '发用' => ['initial', ['sanchuan0' => 10], 0, null],
]);

test('death qi matches through any one route', function () {
    $match = (new PohuaRule)->match(pohua_facts(['sanchuan0' => 11], tigerBranch: 11, ground: 0));
    expect($match)->not->toBeNull()->and($match->evidence['tiger_type'])->toBe('death_qi')
        ->and($match->evidence['matched_routes'])->toBe(['initial']);
});

test('all simultaneous routes are preserved', function () {
    $match = (new PohuaRule)->match(pohua_facts(['rizhi' => 2, 'sanchuan0' => 10], ground: 2, xingnian: 2));
    expect($match?->evidence['matched_routes'])->toBe(['day', 'branch', 'xingnian', 'initial']);
});

test('tiger on death spirit without any route does not match', function () {
    expect((new PohuaRule)->match(pohua_facts(ground: 6)))->toBeNull();
});

test('invalid required branch indexes safely reject', function (array $changes) {
    expect((new PohuaRule)->match(pohua_facts($changes)))->toBeNull();
})->with([
    [['yuezhi' => 12]], [['rigan' => 10]], [['rizhi' => 12]], [['sanchuan0' => 12]],
]);

test('death spirit at a route without white tiger does not match', function () {
    expect((new PohuaRule)->match(pohua_facts(['tianjiang' => array_fill(0, 12, 0)])))->toBeNull();
});

test('ordinary seasonal dead or imprisoned tiger branch cannot establish pohua', function (string $datetime, int $ordinaryBranch) {
    expect((new PohuaRule)->match(pohua_facts(['calculationTime' => $datetime], tigerBranch: $ordinaryBranch, ground: 2)))->toBeNull();
})->with([
    '时令死而非月神死神死气' => ['2026-03-01 12:00:00', 7],
    '时令囚而非月神死神死气' => ['2026-03-01 12:00:00', 8],
]);

test('non restraining branch route still matches without initial transmission', function () {
    // 六月死神戌土临辰午火：土不克火；初传子也不是戌。
    $match = (new PohuaRule)->match(pohua_facts(['rizhi' => 6, 'sanchuan0' => 0], ground: 6));
    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toBe(['branch'])
        ->and(collect($match->evidence['judgments'])->pluck('code'))->not->toContain('tiger_restrains_branch')
        ->and($match->evidence['foundations'][1]['detail'])->toContain('虽未发用，仍符合魄化课');
});

test('missing person only skips xingnian and other routes still evaluate', function () {
    $match = (new PohuaRule)->match(pohua_facts());
    expect($match)->not->toBeNull()->and($match->evidence['matched_routes'])->toBe(['day'])
        ->and($match->evidence['xingnian'])->toBeNull()
        ->and($match->evidence['uncovered'])->toContain('当前缺占人行年，只跳过“临行年”这一可选成立路线，其余盘面路线仍正常判断。');
});

test('missing person cannot invent the xingnian route', function () {
    expect((new PohuaRule)->match(pohua_facts(ground: 6)))->toBeNull();
});

test('restraining day stem branch and xingnian judgments show concrete relations', function () {
    $match = (new PohuaRule)->match(pohua_facts(['rigan' => 8, 'rizhi' => 0], ground: 0, xingnian: 0));
    $codes = collect($match?->evidence['judgments'])->pluck('code');
    expect($codes)->toContain('tiger_restrains_day', 'tiger_restrains_branch', 'tiger_restrains_xingnian');
});

test('seasonal qiu and si are judgments only', function (string $datetime, string $code) {
    $match = (new PohuaRule)->match(pohua_facts(['calculationTime' => $datetime]));
    expect(collect($match?->evidence['judgments'])->pluck('code'))->toContain($code);
})->with([
    '囚' => ['2026-01-01 12:00:00', 'tiger_seasonal_qiu'],
    '死' => ['2026-03-01 12:00:00', 'tiger_seasonal_si'],
]);

test('upper and lower restraint judgments follow actual heaven ground direction', function (int $ground, string $code) {
    $match = (new PohuaRule)->match(pohua_facts(['rizhi' => $ground], ground: $ground));
    expect(collect($match?->evidence['judgments'])->pluck('code'))->toContain($code);
})->with([
    '上克下' => [0, 'upper_restrains_lower'], // 戌土克子水
    '下克上' => [3, 'lower_restrains_upper'], // 卯木克戌土
]);

test('yang and yin tiger branches use neutral object judgments', function (int $tigerBranch, string $code) {
    $match = (new PohuaRule)->match(pohua_facts(['sanchuan0' => $tigerBranch], tigerBranch: $tigerBranch, ground: 0));
    $judgment = collect($match?->evidence['judgments'])->firstWhere('code', $code);
    expect($judgment)->not->toBeNull()->and($judgment['effect'])->toBe('neutral');
})->with([
    '阳支死神戌' => [10, 'tiger_yang'],
    '阴支死气亥' => [11, 'tiger_yin'],
]);

test('metadata evidence and registry order are stable', function () {
    $match = (new PohuaRule)->match(pohua_facts());
    $codes = array_map(fn ($rule): string => $rule->code(), (new RuleRegistry)->rules());
    expect([$match?->code, $match?->name, $match?->gua, $match?->guaSymbol])->toBe(['lesson.pohua', '魄化课', '蛊', '䷑'])
        ->and($match?->evidence)->toHaveKeys(['month_branch', 'death_spirit', 'death_qi', 'tiger_branch', 'tiger_type', 'tiger_ground_position', 'day_stem', 'day_stem_lodging', 'day_branch', 'xingnian', 'initial', 'matched_routes', 'foundations', 'judgments', 'uncovered'])
        ->and(array_search('lesson.pohua', $codes, true))->toBe(array_search('lesson.tianwang', $codes, true) + 1);
});
