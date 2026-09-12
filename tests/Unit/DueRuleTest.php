<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\DueRule;
use App\Services\PanCalculator;

function due_fixture(array $shengke, array $sike = [0, 9, 9, 4, 0, 7, 7, 2]): PanFacts
{
    return PanFacts::from(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sike' => $sike,
        'wuxingShengke0' => [$shengke[0]], 'wuxingShengke1' => [$shengke[1]],
        'wuxingShengke2' => [$shengke[2]], 'wuxingShengke3' => [$shengke[3]],
    ]));
}

function due_production(string $datetime): array
{
    $pan = (new PanCalculator)->calculate($datetime);

    return [$pan, (new DueRule)->match(PanFacts::from($pan))];
}

test('due reproduces the daquan jia-zi young due example', function () {
    [$pan, $match] = due_production('2026-06-19 01:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([0, 0, 1, 8])
        ->and(array_map(fn (int $index): int => $pan->get('wuxingShengke'.$index)[0], range(0, 3)))->toBe([1, 2, 1, 1])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([2, 9, 4])
        ->and($match)->not->toBeNull()->and($match->evidence['up_restrain_count'])->toBe(3)
        ->and($match->evidence['down_restrain_count'])->toBe(0)->and($match->evidence['subtype'])->toBe('幼度厄');
});

test('due reproduces the daquan ren-shen long due example', function () {
    [$pan, $match] = due_production('2026-06-27 00:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([8, 8, 0, 7])
        ->and(array_map(fn (int $index): int => $pan->get('wuxingShengke'.$index)[0], range(0, 3)))->toBe([-1, -2, -1, -1])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([6, 1, 8])
        ->and($match)->not->toBeNull()->and($match->evidence['up_restrain_count'])->toBe(0)
        ->and($match->evidence['down_restrain_count'])->toBe(3)->and($match->evidence['subtype'])->toBe('长度厄');
});

test('due matches exactly three upper restraints', function () {
    $match = (new DueRule)->match(due_fixture([1, 1, 0, 1]));
    expect($match)->not->toBeNull()->and($match->evidence['is_you_due'])->toBeTrue();
});

test('due matches exactly three lower restraints', function () {
    $match = (new DueRule)->match(due_fixture([-1, -1, 0, -1]));
    expect($match)->not->toBeNull()->and($match->evidence['is_chang_due'])->toBeTrue();
});

test('due rejects only two restraints', function (array $relations) {
    expect((new DueRule)->match(due_fixture($relations)))->toBeNull();
})->with(['two upper restraints' => [[1, 1, 0, 0]], 'two lower restraints' => [[-1, -1, 0, 0]]]);

test('due rejects four restraints because they belong to the next lesson', function (array $relations) {
    expect((new DueRule)->match(due_fixture($relations)))->toBeNull();
})->with(['four upper restraints' => [[1, 1, 1, 1]], 'four lower restraints' => [[-1, -1, -1, -1]]]);

test('due rejects mixed two upper and one lower restraint', function () {
    expect((new DueRule)->match(due_fixture([1, 1, -1, 0])))->toBeNull();
});

test('due counts repeated canonical lesson structures as four raw positions', function () {
    $match = (new DueRule)->match(due_fixture([1, 1, 0, 1], [0, 9, 9, 4, 0, 7, 0, 9]));
    expect($match)->not->toBeNull()->and($match->evidence['up_restrain_count'])->toBe(3)
        ->and(count($match->evidence['raw_lesson_relations']))->toBe(4);
});

test('due returns null when a necessary shengke fact is missing', function () {
    $facts = PanFacts::from(new PanResult([
        'rigan' => 0, 'rizhi' => 0, 'sike' => [0, 9, 9, 4, 0, 7, 7, 2],
        'wuxingShengke0' => [1], 'wuxingShengke1' => [1], 'wuxingShengke2' => [1],
    ]));
    expect((new DueRule)->match($facts))->toBeNull();
});

test('due exposes frozen metadata', function () {
    $match = (new DueRule)->match(due_fixture([1, 1, 0, 1]));
    expect($match->code)->toBe('lesson.due')->and($match->name)->toBe('度厄课')
        ->and($match->gua)->toBe('剥')->and($match->guaSymbol)->toBe('䷖');
});

test('due evidence distinguishes young and long due and names the real day stem', function () {
    $young = (new DueRule)->match(due_fixture([1, 1, 0, 1]));
    $long = (new DueRule)->match(due_fixture([-1, -1, 0, -1]));
    expect($young->evidence['subtype'])->toBe('幼度厄')
        ->and($young->evidence['judgments'])->toBe([])
        ->and($young->evidence['foundations'][0]['detail'])->toContain('甲木受酉金克')
        ->and($young->evidence['foundations'][0]['detail'])->not->toContain('寅木受酉金克')
        ->and($long->evidence['subtype'])->toBe('长度厄')
        ->and($long->evidence['judgments'])->toBe([])
        ->and($long->evidence['is_you_due'])->toBeFalse()->and($long->evidence['is_chang_due'])->toBeTrue();
});
