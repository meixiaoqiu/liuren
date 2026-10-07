<?php

use App\Support\EvidenceDomId;

test('Evidence DOM ID 对同一引用保持稳定', function () {
    $first = EvidenceDomId::fromRef('transmission:initial');

    expect($first)
        ->toBe(EvidenceDomId::fromRef('transmission:initial'))
        ->toMatch('/^evidence-transmission-initial-[a-f0-9]{10}$/');
});

test('Evidence DOM ID 对相同 slug 的不同引用保持唯一', function () {
    $ids = array_map(EvidenceDomId::fromRef(...), [
        'kejing:lesson.foo_bar',
        'kejing:lesson.foo-bar',
        'kejing:lesson.foo.bar',
    ]);

    expect(array_unique($ids))->toHaveCount(3);
});

test('Evidence DOM ID 的 slug 忽略大小写但 hash 保留引用大小写语义', function () {
    $upper = EvidenceDomId::fromRef('  ABC  ');
    $lower = EvidenceDomId::fromRef('abc');

    expect($upper)->toStartWith('evidence-abc-')
        ->and($lower)->toStartWith('evidence-abc-')
        ->and($upper)->not->toBe($lower);
});

test('Evidence DOM ID 在 slug 为空时使用 ref 占位', function () {
    expect(EvidenceDomId::fromRef('中文：证据'))
        ->toMatch('/^evidence-ref-[a-f0-9]{10}$/')
        ->not->toStartWith('evidence--');
});
