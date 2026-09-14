<?php

/** 文件作用：锁定励德课宽 matcher、贵前贵后天将口径，以及微服/蹉跎和两种完整阴阳分型。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\CuotuoRule;
use App\Domain\Pan\Rules\LideRule;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\WeifuRule;

function lide_facts(array $fixture): PanFacts
{
    return PanFacts::from(new PanResult($fixture));
}

function lide_fixture(string $name): array
{
    return match ($name) {
        // 戊子日、申时、午将、昼：丑贵加卯。日阳卯朱雀前，日阴丑贵人居中，辰阳戌玄武后，辰阴申白虎后。
        'ordinary' => [
            'rigan' => 4, 'rizhi' => 0,
            'tianpan' => [10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            'tianjiang' => [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8],
            'sike' => [4, 3, 3, 1, 0, 10, 10, 8],
        ],
        // 辛丑日、寅时、丑将、夜：寅贵加卯。四神酉申子亥分别乘白虎、天空、太阴、玄武，全部贵后。
        'weifu' => [
            'rigan' => 7, 'rizhi' => 1,
            'tianpan' => [11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'tianjiang' => [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8],
            'sike' => [7, 9, 9, 8, 1, 0, 0, 11],
        ],
        // 庚申日、寅时、子将、夜：未贵加酉。四神午辰午辰分别乘螣蛇、六合、螣蛇、六合，全部贵前。
        'cuotuo' => [
            'rigan' => 6, 'rizhi' => 8,
            'tianpan' => [10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            'tianjiang' => [9, 8, 7, 6, 5, 4, 3, 2, 1, 0, 11, 10],
            'sike' => [6, 6, 6, 4, 8, 6, 6, 4],
        ],
        // 结构枚举中的完整「阳前阴后」样本：日阳、辰阳在前；日阴、辰阴在后。
        'yang_front_yin_rear' => [
            'rigan' => 1, 'rizhi' => 5,
            'tianpan' => [5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4],
            'tianjiang' => [9, 10, 11, 0, 1, 2, 3, 4, 5, 6, 7, 8],
            'sike' => [1, 9, 9, 2, 5, 10, 10, 3],
        ],
        // 结构枚举中的完整「阴前阳后」样本：日阴、辰阴在前；日阳、辰阳在后。
        'yin_front_yang_rear' => [
            'rigan' => 0, 'rizhi' => 0,
            'tianpan' => [4, 5, 6, 7, 8, 9, 10, 11, 0, 1, 2, 3],
            'tianjiang' => [9, 8, 7, 6, 5, 4, 3, 2, 1, 0, 11, 10],
            'sike' => [0, 6, 6, 10, 0, 4, 4, 8],
        ],
        default => throw new InvalidArgumentException("未知励德测试夹具：{$name}"),
    };
}

test('metadata and registry order are stable', function () {
    $rule = new LideRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect([$rule->code(), $rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])
        ->toBe(['lesson.lide', '励德课', '随', '䷐'])
        ->and(array_search('lesson.lide', $codes, true))->toBe(array_search('lesson.guimu', $codes, true) + 1)
        ->and(array_search('structure.weifu', $codes, true))->toBe(array_search('lesson.lide', $codes, true) + 1)
        ->and(array_search('structure.cuotuo', $codes, true))->toBe(array_search('structure.weifu', $codes, true) + 1);
});

test('nobleman on mao establishes lide even when the four gods do not fit any complete subtype', function () {
    $match = (new LideRule)->match(lide_facts(lide_fixture('ordinary')));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['nobleman_ground'])->toBe(3)
        ->and($match?->evidence['pattern'])->toBe('mixed')
        ->and($match?->evidence['pattern_label'])->toBe('未落入四种完整分型')
        ->and($match?->evidence['judgments'])->toBe([])
        ->and($match?->evidence['four_gods']['day_yin']['side'])->toBe('center');
});

test('nobleman outside mao-you cannot establish lide even if the rest of the plate is unchanged', function () {
    $fixture = lide_fixture('ordinary');
    $fixture['tianjiang'] = [9, 10, 11, 1, 0, 2, 3, 4, 5, 6, 7, 8];

    expect((new LideRule)->match(lide_facts($fixture)))->toBeNull()
        ->and((new WeifuRule)->match(lide_facts($fixture)))->toBeNull()
        ->and((new CuotuoRule)->match(lide_facts($fixture)))->toBeNull();
});

test('weifu requires lide plus all four gods in the rear six generals', function () {
    $facts = lide_facts(lide_fixture('weifu'));
    $lesson = (new LideRule)->match($facts);
    $grid = (new WeifuRule)->match($facts);

    expect($lesson)->not->toBeNull()
        ->and($lesson?->evidence['pattern'])->toBe('weifu')
        ->and($grid)->not->toBeNull()
        ->and($grid?->marker)->toBe('格')
        ->and(collect($grid?->evidence['four_gods'])->pluck('side')->unique()->values()->all())->toBe(['rear'])
        ->and((new CuotuoRule)->match($facts))->toBeNull();
});

test('cuotuo requires lide plus all four gods in the front five generals', function () {
    $facts = lide_facts(lide_fixture('cuotuo'));
    $lesson = (new LideRule)->match($facts);
    $grid = (new CuotuoRule)->match($facts);

    expect($lesson)->not->toBeNull()
        ->and($lesson?->evidence['nobleman_ground'])->toBe(9)
        ->and($lesson?->evidence['pattern'])->toBe('cuotuo')
        ->and($grid)->not->toBeNull()
        ->and(collect($grid?->evidence['four_gods'])->pluck('side')->unique()->values()->all())->toBe(['front'])
        ->and((new WeifuRule)->match($facts))->toBeNull();
});

test('complete yang-front yin-rear is a judgment but not a separate lesson matcher', function () {
    $match = (new LideRule)->match(lide_facts(lide_fixture('yang_front_yin_rear')));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['pattern'])->toBe('yang_front_yin_rear')
        ->and(collect($match?->evidence['judgments'])->pluck('code')->all())->toBe(['yang_front_yin_rear'])
        ->and((new WeifuRule)->match(lide_facts(lide_fixture('yang_front_yin_rear'))))->toBeNull()
        ->and((new CuotuoRule)->match(lide_facts(lide_fixture('yang_front_yin_rear'))))->toBeNull();
});

test('complete yin-front yang-rear is a judgment but not a separate lesson matcher', function () {
    $match = (new LideRule)->match(lide_facts(lide_fixture('yin_front_yang_rear')));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['pattern'])->toBe('yin_front_yang_rear')
        ->and(collect($match?->evidence['judgments'])->pluck('code')->all())->toBe(['yin_front_yang_rear']);
});

test('the nobleman itself is center and never silently counted as front or rear', function () {
    $match = (new LideRule)->match(lide_facts(lide_fixture('ordinary')));
    $dayYin = $match?->evidence['four_gods']['day_yin'];

    expect($dayYin)->toMatchArray(['upper' => 1, 'general' => 0, 'side' => 'center'])
        ->and((new WeifuRule)->match(lide_facts(lide_fixture('ordinary'))))->toBeNull()
        ->and((new CuotuoRule)->match(lide_facts(lide_fixture('ordinary'))))->toBeNull();
});

test('malformed or incomplete nobleman facts do not match', function () {
    expect((new LideRule)->match(PanFacts::from(new PanResult(['tianjiang' => []]))))->toBeNull()
        ->and((new LideRule)->match(PanFacts::from(new PanResult(['tianjiang' => array_fill(0, 12, 1)]))))->toBeNull();
});
