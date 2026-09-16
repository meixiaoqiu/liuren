<?php

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ConggeRule;
use App\Domain\Pan\Rules\JiaseRule;
use App\Domain\Pan\Rules\QuanjuRule;
use App\Domain\Pan\Rules\QuzhiRule;
use App\Domain\Pan\Rules\RunxiaRule;
use App\Domain\Pan\Rules\YanshangRule;

/** @param array<string,mixed> $overrides */
function quanju_pan(array $overrides = []): PanResult
{
    return new PanResult(array_replace([
        'sanchuan0' => 8,
        'sanchuan1' => 0,
        'sanchuan2' => 4,
        'rigan' => 2,
        'rizhi' => 8,
    ], $overrides));
}

function quanju_match(string $rule, array $overrides = []): mixed
{
    return (new $rule)->match(PanFacts::from(quanju_pan($overrides)));
}

test('quanju matches all four complete sanhe grids and exposes existing grid rules', function () {
    $cases = [
        [[8, 0, 4], RunxiaRule::class, '润下格'],
        [[2, 6, 10], YanshangRule::class, '炎上格'],
        [[11, 3, 7], QuzhiRule::class, '曲直格'],
        [[5, 9, 1], ConggeRule::class, '从革格'],
    ];

    foreach ($cases as [$triple, $rule, $name]) {
        $overrides = ['sanchuan0' => $triple[0], 'sanchuan1' => $triple[1], 'sanchuan2' => $triple[2]];
        expect(quanju_match(QuanjuRule::class, $overrides))->not->toBeNull();
        $grid = quanju_match($rule, $overrides);
        expect($grid)->not->toBeNull()
            ->and($grid->name)->toBe($name)
            ->and($grid->group)->toBe('全局课体')
            ->and($grid->marker)->toBe('格');
    }
});

test('quanju accepts reverse sanhe without rejecting the lesson', function () {
    $match = quanju_match(QuanjuRule::class, ['sanchuan0' => 0, 'sanchuan1' => 8, 'sanchuan2' => 4]);

    expect($match)->not->toBeNull()
        ->and($match->evidence['grid_name'])->toBe('润下格')
        ->and($match->evidence['direction'])->toBe('reverse')
        ->and(collect($match->evidence['judgments'])->pluck('code'))->toContain('sanhe_reverse');
});

test('jiase means every transmission is one of the four season earth branches and does not require distinct branches', function () {
    $overrides = ['sanchuan0' => 4, 'sanchuan1' => 1, 'sanchuan2' => 4];

    $lesson = quanju_match(QuanjuRule::class, $overrides);
    $grid = quanju_match(JiaseRule::class, $overrides);

    expect($lesson)->not->toBeNull()
        ->and($lesson->evidence['grid_name'])->toBe('稼穑格')
        ->and($grid)->not->toBeNull()
        ->and($grid->marker)->toBe('格');
});

test('quanju rejects the four fangju extensions from ding-e', function () {
    foreach ([[2, 3, 4], [5, 6, 7], [8, 9, 10], [11, 0, 1]] as $triple) {
        expect(quanju_match(QuanjuRule::class, [
            'sanchuan0' => $triple[0],
            'sanchuan1' => $triple[1],
            'sanchuan2' => $triple[2],
        ]))->toBeNull();
    }
});

test('quanju rejects incomplete sanhe and mixed non-season-earth transmissions', function () {
    expect(quanju_match(QuanjuRule::class, ['sanchuan0' => 8, 'sanchuan1' => 0, 'sanchuan2' => 5]))->toBeNull();
    expect(quanju_match(QuanjuRule::class, ['sanchuan0' => 1, 'sanchuan1' => 4, 'sanchuan2' => 5]))->toBeNull();
});
