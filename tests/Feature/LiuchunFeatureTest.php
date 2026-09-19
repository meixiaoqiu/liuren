<?php

/** 文件作用：以生产 PanCalculator 固定复现六阳、六阴，并验证正式规则引擎接入。 */

use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;

test('production pan reproduces fixed six-yang example through the rule engine', function () {
    $pan = app(PanCalculator::class)->calculate('2031-01-04 05:00:00');
    $data = $pan->toArray();
    $match = collect(app(PanRuleEngine::class)->evaluate($pan))->firstWhere('code', 'lesson.liuchun');

    expect([$data['sike'][1], $data['sike'][3], $data['sike'][5], $data['sike'][7]])->toBe([0, 10, 2, 0])
        ->and([$data['sanchuan0'], $data['sanchuan1'], $data['sanchuan2']])->toBe([2, 0, 10])
        ->and($match)->not->toBeNull()
        ->and($match?->evidence['type'])->toBe('liuyang');
});

test('production pan reproduces fixed six-yin example through the rule engine', function () {
    $pan = app(PanCalculator::class)->calculate('2031-01-03 05:00:00');
    $data = $pan->toArray();
    $match = collect(app(PanRuleEngine::class)->evaluate($pan))->firstWhere('code', 'lesson.liuchun');

    expect([$data['sike'][1], $data['sike'][3], $data['sike'][5], $data['sike'][7]])->toBe([11, 9, 1, 11])
        ->and([$data['sanchuan0'], $data['sanchuan1'], $data['sanchuan2']])->toBe([11, 9, 7])
        ->and($match)->not->toBeNull()
        ->and($match?->evidence['type'])->toBe('liuyin');
});
