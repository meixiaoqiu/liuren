<?php

/** 文件作用：以生产 PanCalculator 搜索并复现六纯课，不为课例修改核心排盘。 */

use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LiuchunRule;
use App\Services\PanCalculator;

test('production calculator yields both six-yang and six-yin examples in 2031', function () {
    $calculator = app(PanCalculator::class);
    $rule = new LiuchunRule;
    $found = [];

    for ($day = new DateTimeImmutable('2031-01-01 00:00:00', new DateTimeZone('Asia/Shanghai')); count($found) < 2; $day = $day->modify('+1 day')) {
        expect($day)->toBeLessThan(new DateTimeImmutable('2032-01-01 00:00:00', new DateTimeZone('Asia/Shanghai')));
        foreach ([23, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21] as $hour) {
            $pan = $calculator->calculate($day->setTime($hour, 0)->format('Y-m-d H:i:s'));
            $match = $rule->match(PanFacts::from($pan));
            if ($match !== null) {
                $found[$match->evidence['type']] = [$pan->toArray(), $match];
            }
        }
    }

    expect(array_keys($found))->toContain('liuyang', 'liuyin');
    foreach ($found as [$pan, $match]) {
        expect($match->evidence['initial_from_sike_upper'])->toBeTrue()
            ->and($match->evidence['sike_upper_branches'])->toBe([$pan['sike'][1], $pan['sike'][3], $pan['sike'][5], $pan['sike'][7]]);
    }
});
