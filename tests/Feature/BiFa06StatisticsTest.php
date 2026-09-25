<?php

use App\Domain\Pan\BiFa\Rules\LiuYinXiangJiRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

test('六十日十二时辰生产样本只产生大全所载四种严格源消根断组合', function () {
    $calculator = new PanCalculator;
    $rule = new LiuYinXiangJiRule;
    $found = [];
    $seen = [];

    for ($date = new DateTimeImmutable('2031-01-01'); $date <= new DateTimeImmutable('2031-03-01'); $date = $date->modify('+1 day')) {
        foreach ([1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23] as $hour) {
            $facts = PanFacts::from($calculator->calculate($date->setTime($hour, 0)->format('Y-m-d H:i:s')));
            $sike = $facts->get('sike');
            $key = implode('/', [$facts->get('rigan'), $facts->get('rizhi'), $sike[1] ?? '?']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $match = $rule->match($facts);
            if (in_array('source_exhausted_root_severed', $match?->matchedRoutes ?? [], true)) {
                $found[] = PanCalculator::$tiangan[$facts->get('rigan')]
                    .PanCalculator::$dizhi[$facts->get('rizhi')]
                    .'/'.PanCalculator::$dizhi[$sike[1]];
            }
        }
    }

    sort($found);
    $expected = ['癸卯/卯', '癸巳/卯', '癸未/卯', '辛卯/子'];
    sort($expected);
    expect($seen)->toHaveCount(717)
        ->and($found)->toBe($expected);
});

test('生产排盘复现己卯六阴但排除严格源消根断', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2031-02-08 19:00:00'));
    $match = (new LiuYinXiangJiRule)->match($facts);

    expect($facts->get('sanchuan0'))->toBe(11)
        ->and($facts->get('sanchuan1'))->toBe(1)
        ->and($facts->get('sanchuan2'))->toBe(3)
        ->and($match?->matchedRoutes)->toContain('six_yin')
        ->and($match?->matchedRoutes)->not->toContain('source_exhausted_root_severed')
        ->and($match?->evidence['lesson_generations'])->toBe([true, true, true, true])
        ->and($match?->evidence['transmission_generations'])->toBe([false, false]);
});

test('甲辰干上午满足后世四课口径但不满足大全严格路线', function () {
    $facts = PanFacts::from((new PanCalculator)->calculate('2031-01-04 17:00:00'));
    $sike = $facts->get('sike');
    $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
    $generates = static fn (?int $source, ?int $target): bool => $source !== null && $target !== null && ($source + 1) % 5 === $target;
    $lessonGenerations = [
        $generates($facts->stemElement($sike[0]), $facts->branchElement($sike[1])),
        $generates($facts->branchElement($sike[2]), $facts->branchElement($sike[3])),
        $generates($facts->branchElement($sike[4]), $facts->branchElement($sike[5])),
        $generates($facts->branchElement($sike[6]), $facts->branchElement($sike[7])),
    ];
    $transmissionGenerations = [
        $generates($facts->branchElement($transmissions[0]), $facts->branchElement($transmissions[1])),
        $generates($facts->branchElement($transmissions[1]), $facts->branchElement($transmissions[2])),
    ];
    $match = (new LiuYinXiangJiRule)->match($facts);

    expect([$facts->get('rigan'), $facts->get('rizhi'), $sike[1]])->toBe([0, 4, 6])
        ->and($transmissions)->toBe([8, 0, 4])
        ->and($lessonGenerations)->toBe([true, true, true, true])
        ->and($transmissionGenerations)->toBe([true, false])
        ->and($match?->matchedRoutes ?? [])->not->toContain('source_exhausted_root_severed');
});
