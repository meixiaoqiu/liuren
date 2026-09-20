<?php

/** 文件作用：锁定第64课物类课的普遍入口，以及三传五行、六亲、旺衰、天将的生产事实复用。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\WuleiRule;
use App\Services\PanCalculator;

function wulei_facts(array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'calculationTime' => '2031-03-01 12:00:00',
        'sanchuan0' => 2,
        'sanchuan1' => 6,
        'sanchuan2' => 10,
        'liuqin0' => 2,
        'liuqin1' => -2,
        'liuqin2' => -1,
        'tianpan' => range(0, 11),
        'tianjiang' => range(0, 11),
    ], $changes)));
}

test('wulei metadata exact xiang and registry order are stable', function () {
    $rule = new WuleiRule;
    $definition = $rule->definition();
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());
    $match = $rule->match(wulei_facts());

    expect([$rule->code(), $rule::NAME, $rule::GROUP])->toBe(['lesson.wulei', '物类课', '六十四课'])
        ->and($definition['xiang'])->toBe('物以声应，方以类萃。六亲俱现，以用为主。旺相吉言，休囚凶语。始终吉凶，神将分取。')
        ->and($definition['foundations'])->toHaveCount(1)
        ->and($definition['foundations'][0]['code'])->toBe('valid_initial')
        ->and($definition['judgments'])->toBe([])
        ->and([$match?->gua, $match?->guaSymbol])->toBe(['节', '䷻'])
        ->and($match?->xiang)->toBe(WuleiRule::XIANG)
        ->and($match?->evidence['judgments'])->toBe([])
        ->and(array_search('lesson.wulei', $codes, true))->toBe(array_search('lesson.zazhuang', $codes, true) + 1);
});

test('all twelve legal initial branches match', function (int $initial) {
    expect((new WuleiRule)->match(wulei_facts(['sanchuan0' => $initial])))->not->toBeNull();
})->with(range(0, 11));

test('missing malformed and out of range initial branches are rejected', function (mixed $initial) {
    expect((new WuleiRule)->match(wulei_facts(['sanchuan0' => $initial])))->toBeNull();
})->with([null, '6', -1, 12]);

test('three transmissions reuse production element liuqin seasonal state and riding general facts', function () {
    $match = (new WuleiRule)->match(wulei_facts());

    expect($match?->evidence['initial'])->toBe([
        'branch' => 2, 'branch_name' => '寅', 'element' => 0, 'element_name' => '木',
        'liuqin' => 2, 'liuqin_name' => '父母', 'seasonal_state' => '旺',
        'general' => 2, 'general_name' => '朱雀',
    ])->and($match?->evidence['middle'])->toBe([
        'branch' => 6, 'branch_name' => '午', 'element' => 1, 'element_name' => '火',
        'liuqin' => -2, 'liuqin_name' => '子孙', 'seasonal_state' => '相',
        'general' => 6, 'general_name' => '天空',
    ])->and($match?->evidence['final'])->toBe([
        'branch' => 10, 'branch_name' => '戌', 'element' => 2, 'element_name' => '土',
        'liuqin' => -1, 'liuqin_name' => '妻财', 'seasonal_state' => '死',
        'general' => 10, 'general_name' => '太阴',
    ]);
});

test('liuqin names are the shared PanCalculator mapping', function (int $relation, string $name) {
    $match = (new WuleiRule)->match(wulei_facts(['liuqin0' => $relation]));
    expect($match?->evidence['initial']['liuqin_name'])->toBe($name)
        ->and(PanCalculator::$liuqin[$relation])->toBe($name);
})->with([
    [-2, '子孙'], [-1, '妻财'], [0, '兄弟'], [1, '官鬼'], [2, '父母'],
]);

test('uncovered records all frozen research boundaries', function () {
    $uncovered = (new WuleiRule)->match(wulei_facts())?->evidence['uncovered'];
    expect($uncovered)->toHaveCount(8)
        ->and(implode('\n', $uncovered))->toContain('具体族类')
        ->toContain('德合')
        ->toContain('未来、过去')
        ->toContain('新物、旧物')
        ->toContain('大型传统类神表')
        ->toContain('全局吉将／凶将')
        ->toContain('始终吉凶算法')
        ->toContain('附录扩展');
});

test('a real production plate matches wulei through the registry engine', function () {
    $pan = app(PanCalculator::class)->calculate('2024-03-01 03:00:00');
    $rule = collect(app(RuleRegistry::class)->rules())->first(fn ($item) => $item->code() === 'lesson.wulei');
    $match = $rule?->match(PanFacts::from($pan));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['initial']['branch'])->toBe($pan->get('sanchuan0'))
        ->and($match?->evidence['initial']['liuqin'])->toBe($pan->get('liuqin0'));
});

test('wulei statistics script closes at 4380 for 2031 and 4392 for the leap year 2032', function () {
    $script = realpath(__DIR__.'/../Support/Statistics/WuleiStatistics.php');
    expect($script)->not->toBeFalse();

    // 这里显式断言 WuleiStatistics.php 必须按年动态计算分母：
    // 2031 年平年 365 天 × 12 时辰 = 4380；2032 年闰年 366 天 × 12 时辰 = 4392。
    $run = static function (int $year) use ($script): array {
        $command = sprintf('php %s %d 2>&1', escapeshellarg($script), $year);
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        expect($exitCode)->toBe(0);

        $json = trim(implode("\n", $output));
        $decoded = json_decode($json, true);
        expect($decoded)->not->toBeNull();

        return $decoded;
    };

    $y2031 = $run(2031);
    expect($y2031['year'])->toBe(2031)
        ->and($y2031['is_leap_year'])->toBeFalse()
        ->and($y2031['days_in_year'])->toBe(365)
        ->and($y2031['denominator'])->toBe(4380)
        ->and($y2031['matched'])->toBe(4380)
        ->and(array_sum($y2031['initial_branch_distribution']))->toBe(4380)
        ->and(array_sum($y2031['initial_liuqin_distribution']))->toBe(4380)
        ->and(array_sum($y2031['initial_seasonal_state_distribution']))->toBe(4380);

    $y2032 = $run(2032);
    expect($y2032['year'])->toBe(2032)
        ->and($y2032['is_leap_year'])->toBeTrue()
        ->and($y2032['days_in_year'])->toBe(366)
        ->and($y2032['denominator'])->toBe(4392)
        ->and($y2032['matched'])->toBe(4392)
        ->and(array_sum($y2032['initial_branch_distribution']))->toBe(4392)
        ->and(array_sum($y2032['initial_liuqin_distribution']))->toBe(4392)
        ->and(array_sum($y2032['initial_seasonal_state_distribution']))->toBe(4392);
});
