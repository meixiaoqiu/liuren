<?php

/** 文件作用：锁定第62课六纯课的六阳、六阴冻结边界。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\LiuchunRule;
use App\Domain\Pan\Rules\RuleRegistry;

function liuchun_facts(array $changes = []): PanFacts
{
    return PanFacts::from(new PanResult(array_replace([
        'sike' => [0, 0, 1, 2, 3, 4, 5, 6],
        'sanchuan0' => 0, 'sanchuan1' => 2, 'sanchuan2' => 4,
    ], $changes)));
}

test('liuchun metadata and registry order are stable', function () {
    $rule = new LiuchunRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect([$rule->code(), $rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['lesson.liuchun', '六纯课', '革', '䷰'])
        ->and($codes)->toContain('lesson.liuchun');
});

test('six yang matches with repeated branches and initial drawn from sike upper branch', function () {
    $match = (new LiuchunRule)->match(liuchun_facts([
        'sike' => [0, 0, 1, 2, 3, 0, 5, 2],
        'sanchuan0' => 0, 'sanchuan1' => 2, 'sanchuan2' => 0,
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['type'])->toBe('liuyang')
        ->and($match?->evidence['initial_from_sike_upper'])->toBeTrue();
});

test('six yin matches', function () {
    $match = (new LiuchunRule)->match(liuchun_facts([
        'sike' => [0, 1, 1, 3, 3, 5, 5, 7],
        'sanchuan0' => 3, 'sanchuan1' => 9, 'sanchuan2' => 11,
    ]));
    expect($match)->not->toBeNull()->and($match?->evidence['type'])->toBe('liuyin');
});

test('non-uniform sike upper branches do not match either route', function () {
    expect((new LiuchunRule)->match(liuchun_facts(['sike' => [0, 0, 1, 2, 3, 0, 5, 3]])))->toBeNull()
        ->and((new LiuchunRule)->match(liuchun_facts(['sike' => [0, 1, 1, 3, 3, 5, 5, 6]])))->toBeNull();
});

test('uniform sike is rejected when middle or final transmission has the opposite parity', function () {
    $rule = new LiuchunRule;
    expect($rule->match(liuchun_facts(['sanchuan1' => 1])))->toBeNull()
        ->and($rule->match(liuchun_facts(['sanchuan2' => 1])))->toBeNull()
        ->and($rule->match(liuchun_facts(['sike' => [0, 1, 1, 3, 3, 5, 5, 7], 'sanchuan0' => 3, 'sanchuan1' => 2, 'sanchuan2' => 7])))->toBeNull()
        ->and($rule->match(liuchun_facts(['sike' => [0, 1, 1, 3, 3, 5, 5, 7], 'sanchuan0' => 3, 'sanchuan1' => 7, 'sanchuan2' => 2])))->toBeNull();
});

test('initial transmission must be a sike upper branch and five yang yin is not an entry', function () {
    $rule = new LiuchunRule;
    expect($rule->match(liuchun_facts(['sanchuan0' => 8])))->toBeNull();
});

test('unimplemented post-lesson readings are uncovered rather than judgments', function () {
    $rule = new LiuchunRule;
    $definition = $rule->definition();
    $match = $rule->match(liuchun_facts());
    $uncovered = implode('；', $match?->evidence['uncovered'] ?? []);

    expect($definition['judgments'])->toBe([])
        ->and($match?->evidence['judgments'])->toBe([])
        ->and($uncovered)->toContain('五阳、五阴', '退间传', '源消根断', '初传、中传逢空');
});
