<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\WuluJuesiRule;
use App\Services\PanCalculator;

function wulu_juesi_fixture(array $shengke, array $sike = [5, 0, 0, 6, 5, 10, 10, 4]): PanFacts
{
    return PanFacts::from(new PanResult([
        'rigan' => 5, 'rizhi' => 5, 'sike' => $sike,
        'wuxingShengke0' => [$shengke[0]], 'wuxingShengke1' => [$shengke[1]],
        'wuxingShengke2' => [$shengke[2]], 'wuxingShengke3' => [$shengke[3]],
    ]));
}

function wulu_juesi_production(string $datetime): array
{
    $pan = (new PanCalculator)->calculate($datetime);

    return [$pan, (new WuluJuesiRule)->match(PanFacts::from($pan))];
}

test('wulu reproduces the daquan ji-si example with four upper restraints', function () {
    [$pan, $match] = wulu_juesi_production('2026-04-25 03:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([5, 5, 2, 9])
        ->and(array_map(fn (int $index): int => $pan->get('wuxingShengke'.$index)[0], range(0, 3)))->toBe([1, 1, 1, 1])
        ->and($match)->not->toBeNull()->and($match->evidence['subtype'])->toBe('无禄');
});

test('juesi reproduces the daquan geng-chen example with four lower restraints', function () {
    [$pan, $match] = wulu_juesi_production('2026-03-07 07:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([6, 4, 4, 11])
        ->and(array_map(fn (int $index): int => $pan->get('wuxingShengke'.$index)[0], range(0, 3)))->toBe([-1, -1, -1, -1])
        ->and($match)->not->toBeNull()->and($match->evidence['subtype'])->toBe('绝嗣');
});

test('wulu-juesi rejects three upper restraints', function () {
    expect((new WuluJuesiRule)->match(wulu_juesi_fixture([1, 1, 0, 1])))->toBeNull();
});

test('wulu-juesi rejects three lower restraints', function () {
    expect((new WuluJuesiRule)->match(wulu_juesi_fixture([-1, -1, 0, -1])))->toBeNull();
});

test('wulu-juesi rejects mixed upper and lower restraints', function () {
    expect((new WuluJuesiRule)->match(wulu_juesi_fixture([1, 1, -1, -1])))->toBeNull();
});

test('wulu-juesi counts repeated canonical structures as four raw positions', function () {
    $match = (new WuluJuesiRule)->match(wulu_juesi_fixture([1, 1, 1, 1], [5, 0, 0, 6, 5, 10, 5, 0]));
    expect($match)->not->toBeNull()->and($match->evidence['up_restrain_count'])->toBe(4)
        ->and($match->evidence['raw_lesson_relations'])->toHaveCount(4);
});

test('wulu-juesi returns null when a necessary shengke fact is missing', function () {
    $facts = PanFacts::from(new PanResult([
        'rigan' => 5, 'rizhi' => 5, 'sike' => [5, 0, 0, 6, 5, 10, 10, 4],
        'wuxingShengke0' => [1], 'wuxingShengke1' => [1], 'wuxingShengke2' => [1],
    ]));
    expect((new WuluJuesiRule)->match($facts))->toBeNull();
});

test('wulu-juesi evidence uses the real day stem and exposes frozen keys', function () {
    $match = (new WuluJuesiRule)->match(wulu_juesi_fixture([1, 1, 1, 1]));
    expect($match->code)->toBe('lesson.wulu_juesi')->and($match->name)->toBe('无禄绝嗣课')
        ->and($match->gua)->toBe('否')->and($match->guaSymbol)->toBe('䷋')
        ->and($match->evidence)->toHaveKeys([
            'raw_lesson_relations', 'up_restrain_count', 'down_restrain_count', 'is_wulu', 'is_juesi',
            'subtype', 'foundations', 'judgments', 'uncovered',
        ])
        ->and($match->evidence['raw_lesson_relations'][0]['lower_display'])->toBe('己土')
        ->and($match->evidence['raw_lesson_relations'][0]['display'])->not->toContain('未土')
        ->and($match->evidence['is_wulu'])->toBeTrue()->and($match->evidence['is_juesi'])->toBeFalse();
});

test('wulu-juesi subtype distinguishes both paths', function () {
    $wulu = (new WuluJuesiRule)->match(wulu_juesi_fixture([1, 1, 1, 1]));
    $juesi = (new WuluJuesiRule)->match(wulu_juesi_fixture([-1, -1, -1, -1]));
    expect($wulu->evidence['subtype'])->toBe('无禄')->and($juesi->evidence['subtype'])->toBe('绝嗣')
        ->and($wulu->evidence['judgments'])->toBe([])->and($juesi->evidence['judgments'])->toBe([]);
});
