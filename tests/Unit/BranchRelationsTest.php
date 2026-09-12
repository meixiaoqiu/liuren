<?php

use App\Domain\Pan\BranchRelations;

test('six-harm pairs are complete symmetric and canonical', function () {
    expect(BranchRelations::HAI_PAIRS)->toBe([
        [0, 7], [1, 6], [2, 5], [3, 4], [8, 11], [9, 10],
    ]);

    foreach (BranchRelations::HAI_PAIRS as $pair) {
        expect(BranchRelations::isHai($pair[0], $pair[1]))->toBeTrue()
            ->and(BranchRelations::isHai($pair[1], $pair[0]))->toBeTrue()
            ->and(BranchRelations::haiPair($pair[1], $pair[0]))->toBe($pair);
    }
});

test('six-harm rejects self and unrelated branches', function () {
    expect(BranchRelations::isHai(0, 0))->toBeFalse()
        ->and(BranchRelations::isHai(0, 6))->toBeFalse()
        ->and(BranchRelations::haiPair(-1, 7))->toBeNull()
        ->and(BranchRelations::haiPair(0, 12))->toBeNull();
});

test('chong and po mappings contain all twelve canonical relations', function () {
    expect(BranchRelations::CHONG)->toBe([6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5])
        ->and(BranchRelations::PO)->toBe([9, 4, 11, 6, 1, 8, 3, 10, 5, 0, 7, 2]);

    foreach (range(0, 11) as $branch) {
        expect(BranchRelations::clashOf($branch))->toBe(BranchRelations::CHONG[$branch])
            ->and(BranchRelations::breakOf($branch))->toBe(BranchRelations::PO[$branch]);
    }

    expect(BranchRelations::clashOf(12))->toBeNull()
        ->and(BranchRelations::breakOf(12))->toBeNull();
});

test('liuhe contains exactly the six orthodox unordered pairs', function () {
    expect(BranchRelations::LIUHE_PAIRS)->toBe([
        [0, 1], [2, 11], [3, 10], [4, 9], [5, 8], [6, 7],
    ]);
});

test('liuhe accepts all twelve directions and rejects every other ordered pair', function () {
    $expected = ['0,1', '1,0', '2,11', '11,2', '3,10', '10,3', '4,9', '9,4', '5,8', '8,5', '6,7', '7,6'];

    foreach (range(0, 11) as $a) {
        foreach (range(0, 11) as $b) {
            expect(BranchRelations::isLiuhe($a, $b))
                ->toBe(in_array("{$a},{$b}", $expected, true), "六合有序对 {$a},{$b} 判断错误");
        }
    }
});

test('liuhe pair returns canonical order in either direction', function () {
    foreach (BranchRelations::LIUHE_PAIRS as $pair) {
        expect(BranchRelations::liuhePair($pair[0], $pair[1]))->toBe($pair)
            ->and(BranchRelations::liuhePair($pair[1], $pair[0]))->toBe($pair);
    }
});

test('sanhe contains exactly four canonical triples and accepts every permutation', function () {
    expect(BranchRelations::SANHE_TRIPLES)->toBe([[0, 4, 8], [1, 5, 9], [2, 6, 10], [3, 7, 11]]);

    foreach (BranchRelations::SANHE_TRIPLES as $triple) {
        [$a, $b, $c] = $triple;
        foreach ([[$a, $b, $c], [$a, $c, $b], [$b, $a, $c], [$b, $c, $a], [$c, $a, $b], [$c, $b, $a]] as $order) {
            expect(BranchRelations::isSanhe(...$order))->toBeTrue();
        }
    }
});

test('sanhe rejects every non-canonical combination including repeated branches', function () {
    foreach (range(0, 11) as $a) {
        foreach (range($a, 11) as $b) {
            foreach (range($b, 11) as $c) {
                $expected = in_array([$a, $b, $c], BranchRelations::SANHE_TRIPLES, true);
                expect(BranchRelations::isSanhe($a, $b, $c))->toBe($expected, "三合组合 {$a},{$b},{$c} 判断错误");
            }
        }
    }
});

test('share sanhe group accepts distinct members only', function () {
    foreach (range(0, 11) as $a) {
        foreach (range(0, 11) as $b) {
            $expected = $a !== $b && collect(BranchRelations::SANHE_TRIPLES)
                ->contains(fn (array $triple): bool => in_array($a, $triple, true) && in_array($b, $triple, true));
            expect(BranchRelations::shareSanheGroup($a, $b))->toBe($expected);
        }
    }
});
