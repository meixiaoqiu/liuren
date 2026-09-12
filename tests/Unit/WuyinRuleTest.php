<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\WuyinRule;
use App\Services\PanCalculator;

function wuyin_fixture(int $stem, int $branch, array $sike, array $shengke = [0, 0, 0, 0]): PanFacts
{
    return PanFacts::from(new PanResult([
        'rigan' => $stem, 'rizhi' => $branch, 'sike' => $sike,
        'wuxingShengke0' => [$shengke[0]], 'wuxingShengke1' => [$shengke[1]],
        'wuxingShengke2' => [$shengke[2]], 'wuxingShengke3' => [$shengke[3]],
    ]));
}

function wuyin_production(string $datetime): array
{
    $pan = (new PanCalculator)->calculate($datetime);

    return [$pan, (new WuyinRule)->match(PanFacts::from($pan))];
}

test('wuyin reproduces the daquan yi-mao path A yang-bubei example', function () {
    [$pan, $match] = wuyin_production('2019-07-17 11:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([1, 3, 6, 7])
        ->and($pan->get('sike'))->toBe([1, 5, 5, 6, 3, 4, 4, 5])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([4, 5, 6])
        ->and($match)->not->toBeNull()
        ->and($match->evidence['is_bubei'])->toBeTrue()
        ->and($match->evidence['bubei_type'])->toBe('阳不备')
        ->and($match->evidence['has_ke'])->toBeTrue()
        ->and($match->evidence['bubei_path'])->toBeTrue()
        ->and($match->evidence['cross_path'])->toBeFalse()
        ->and($match->evidence['judgments'])->toBe([]);
});

test('wuyin reproduces the corrected daquan yi-hai path A yin-bubei example', function () {
    [$pan, $match] = wuyin_production('2020-02-02 09:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([1, 11, 5, 0])
        ->and($pan->get('sike'))->toBe([1, 11, 11, 6, 11, 6, 6, 1])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([6, 1, 8])
        ->and($match->evidence['unique_lesson_count'])->toBe(3)
        ->and($match->evidence['bubei_type'])->toBe('阴不备')
        ->and(array_column($match->evidence['raw_lesson_relations'], 'relation'))->toBe(['上生下', '下贼上', '下贼上', '下生上'])
        ->and($match->evidence['bubei_path'])->toBeTrue()
        ->and($match->evidence['cross_path'])->toBeFalse();
});

test('wuyin reproduces the daquan jia-zi complete-four-lessons path B example', function () {
    [$pan, $match] = wuyin_production('2023-03-07 05:00:00');
    expect([$pan->get('rigan'), $pan->get('rizhi'), $pan->get('shizhi'), $pan->get('yuejiang')])->toBe([0, 0, 3, 11])
        ->and($pan->get('sike'))->toBe([0, 10, 10, 6, 0, 8, 8, 4])
        ->and([$pan->get('sanchuan0'), $pan->get('sanchuan1'), $pan->get('sanchuan2')])->toBe([10, 6, 2])
        ->and($match->evidence['is_bubei'])->toBeFalse()
        ->and($match->evidence['bubei_path'])->toBeFalse()
        ->and($match->evidence['stem_upper_cross_restrains_branch'])->toBeTrue()
        ->and($match->evidence['branch_upper_cross_restrains_stem'])->toBeTrue()
        ->and($match->evidence['cross_path'])->toBeTrue();
});

test('wuyin records both paths when both independently hold', function () {
    [, $match] = wuyin_production('2000-03-16 13:00:00'); // pointer-04_day-09
    expect($match)->not->toBeNull()
        ->and($match->evidence['bubei_path'])->toBeTrue()
        ->and($match->evidence['cross_path'])->toBeTrue()
        ->and($match->evidence['paths'])->toBe(['三课不备且有克', '日辰上神交互相克']);
});

test('wuyin rejects three lessons when all four raw positions have no restraint', function () {
    expect((new WuyinRule)->match(wuyin_fixture(0, 0, [0, 2, 2, 2, 0, 0, 1, 1])))->toBeNull();
});

test('wuyin rejects complete lessons with ordinary restraint but no cross path', function () {
    expect((new WuyinRule)->match(wuyin_fixture(0, 0, [0, 8, 8, 0, 0, 0, 1, 1], [1, 0, 0, 0])))->toBeNull();
});

test('wuyin path B requires both cross restraints', function (array $sike) {
    expect((new WuyinRule)->match(wuyin_fixture(0, 0, $sike)))->toBeNull();
})->with([
    'only stem upper restrains branch' => [[0, 4, 4, 0, 0, 0, 1, 1]],
    'only branch upper restrains stem' => [[0, 0, 1, 1, 0, 8, 8, 0]],
]);

test('wuyin cross path does not require day stem and branch generation', function () {
    $match = (new WuyinRule)->match(wuyin_fixture(0, 4, [0, 2, 2, 0, 4, 8, 8, 1]));
    expect($match)->not->toBeNull()->and($match->evidence['cross_path'])->toBeTrue();
});

test('wuyin checks raw positions before deduplication', function () {
    [, $match] = wuyin_production('2000-03-08 15:00:00'); // pointer-03_day-01
    expect($match)->not->toBeNull()
        ->and([$match->evidence['canonical_lessons'][0]['lower'], $match->evidence['canonical_lessons'][0]['upper']])
        ->toBe([$match->evidence['canonical_lessons'][3]['lower'], $match->evidence['canonical_lessons'][3]['upper']])
        ->and($match->evidence['raw_lesson_relations'][0]['has_ke'])->toBeTrue()
        ->and(collect(array_slice($match->evidence['raw_lesson_relations'], 1))->contains('has_ke', true))->toBeFalse()
        ->and($match->evidence['is_bubei'])->toBeTrue()
        ->and($match->evidence['has_ke'])->toBeTrue();
});

test('wuyin keeps stem lodging in canonical lesson but names the day stem in first raw relation', function () {
    [, $match] = wuyin_production('2000-03-08 15:00:00');

    expect($match->evidence['canonical_lessons'][0]['lower'])->toBe(4)
        ->and($match->evidence['raw_lesson_relations'][0]['lower'])->toBe(1)
        ->and($match->evidence['raw_lesson_relations'][0]['display'])->toStartWith('乙木')
        ->and($match->evidence['raw_lesson_relations'][0]['display'])->not->toContain('辰木')
        ->and($match->evidence['foundations'][0]['detail'])->toContain('乙木')
        ->and($match->evidence['foundations'][0]['detail'])->not->toContain('辰木');
});

test('wuyin returns null when necessary facts are missing', function (array $data) {
    expect((new WuyinRule)->match(PanFacts::from(new PanResult($data))))->toBeNull();
})->with([
    'day stem' => [['rizhi' => 0, 'sike' => range(0, 7)]],
    'day branch' => [['rigan' => 0, 'sike' => range(0, 7)]],
    'four lessons' => [['rigan' => 0, 'rizhi' => 0]],
]);

test('wuyin exposes frozen metadata', function () {
    [, $match] = wuyin_production('2023-03-07 05:00:00');
    expect($match->code)->toBe('lesson.wuyin')->and($match->name)->toBe('芜淫课')
        ->and($match->group)->toBe('六十四课')->and($match->gua)->toBe('小畜')->and($match->guaSymbol)->toBe('䷈');
});
