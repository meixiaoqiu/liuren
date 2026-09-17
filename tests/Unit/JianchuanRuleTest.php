<?php

/** 文件作用：锁定第61课间传课的±2主体边界、二十四格唯一映射及日用旺相/休囚修证。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\JianchuanGridRule;
use App\Domain\Pan\Rules\JianchuanRule;
use App\Domain\Pan\Rules\RuleRegistry;

function jianchuan_facts(array $changes = []): PanFacts
{
    $base = [
        'calculationTime' => '2031-03-01 12:00:00',
        'rigan' => 0,
        'sanchuan0' => 4,
        'sanchuan1' => 6,
        'sanchuan2' => 8,
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('jianchuan metadata and registry order are stable', function () {
    $rule = new JianchuanRule;
    $codes = array_map(fn ($item) => $item->code(), (new RuleRegistry)->rules());

    expect($rule->code())->toBe('lesson.jianchuan')
        ->and([$rule::NAME, $rule::GUA, $rule::GUA_SYMBOL])->toBe(['间传课', '巽', '䷸'])
        ->and(array_search('lesson.jianchuan', $codes, true))
        ->toBe(array_search('lesson.lianzhu', $codes, true) + 1);
});

test('definition exposes one foundation directions and seasonal corrections while grids use the existing grid mechanism', function () {
    $definition = (new JianchuanRule)->definition();
    $codes = array_column($definition['judgments'], 'code');
    $gridDefinitions = array_map(
        static fn (JianchuanGridRule $rule): array => $rule->gridDefinition(),
        JianchuanGridRule::all(),
    );

    expect(array_column($definition['foundations'], 'code'))->toBe(['jianchuan_step'])
        ->and($codes)->toContain(
            'forward_jianchuan',
            'reverse_jianchuan',
            'day_initial_wang_xiang',
            'day_initial_xiu_qiu',
        )
        ->and(array_filter($codes, fn (string $code): bool => str_starts_with($code, 'subtype_')))->toBe([])
        ->and($gridDefinitions)->toHaveCount(24)
        ->and(array_column($gridDefinitions, 'group'))->each->toBe('间传课体')
        ->and(array_unique(array_column($gridDefinitions, 'code')))->toHaveCount(24);
});

test('all twenty four textual sequences establish jianchuan and map to the unique subtype', function (array $transmissions, string $subtype, string $direction) {
    $match = (new JianchuanRule)->match(jianchuan_facts([
        'sanchuan0' => $transmissions[0],
        'sanchuan1' => $transmissions[1],
        'sanchuan2' => $transmissions[2],
    ]));

    expect($match)->not->toBeNull()
        ->and($match?->evidence['transmissions'])->toBe($transmissions)
        ->and($match?->evidence['subtype'])->toBe($subtype)
        ->and($match?->evidence['direction'])->toBe($direction);

    $matchedSubtype = collect(JianchuanGridRule::all())
        ->filter(fn (JianchuanGridRule $grid): bool => $grid->match(jianchuan_facts([
            'sanchuan0' => $transmissions[0],
            'sanchuan1' => $transmissions[1],
            'sanchuan2' => $transmissions[2],
        ])) !== null)
        ->values();

    expect($matchedSubtype)->toHaveCount(1)
        ->and($matchedSubtype->first()?->code())->toBe('structure.jianchuan_'.$subtype);
})->with([
    '辰午申·登三天' => [[4, 6, 8], 'deng_santian', 'forward'],
    '午申戌·出三天' => [[6, 8, 10], 'chu_santian', 'forward'],
    '申戌子·涉三渊' => [[8, 10, 0], 'she_sanyuan', 'forward'],
    '戌子寅·入三渊' => [[10, 0, 2], 'ru_sanyuan', 'forward'],
    '子寅辰·向阳' => [[0, 2, 4], 'xiangyang', 'forward'],
    '寅辰午·出阳' => [[2, 4, 6], 'chuyang', 'forward'],
    '丑卯巳·出户' => [[1, 3, 5], 'chuhu', 'forward'],
    '卯巳未·盈阳' => [[3, 5, 7], 'yingyang', 'forward'],
    '巳未酉·变盈' => [[5, 7, 9], 'bianying', 'forward'],
    '未酉亥·入冥' => [[7, 9, 11], 'ruming', 'forward'],
    '酉亥丑·凝阴' => [[9, 11, 1], 'ningyin', 'forward'],
    '亥丑卯·溟濛' => [[11, 1, 3], 'mingmeng', 'forward'],
    '寅子戌·冥阴' => [[2, 0, 10], 'mingyin', 'reverse'],
    '子戌申·偃蹇' => [[0, 10, 8], 'yanjian', 'reverse'],
    '戌申午·悖戾' => [[10, 8, 6], 'beili', 'reverse'],
    '申午辰·凝阳' => [[8, 6, 4], 'ningyang', 'reverse'],
    '午辰寅·顾祖' => [[6, 4, 2], 'guzu', 'reverse'],
    '辰寅子·涉疑' => [[4, 2, 0], 'sheyi', 'reverse'],
    '丑亥酉·极阴' => [[1, 11, 9], 'jiyin', 'reverse'],
    '亥酉未·时遁' => [[11, 9, 7], 'shidun', 'reverse'],
    '酉未巳·励明' => [[9, 7, 5], 'liming', 'reverse'],
    '未巳卯·回明' => [[7, 5, 3], 'huiming', 'reverse'],
    '巳卯丑·转悖' => [[5, 3, 1], 'zhuanbei', 'reverse'],
    '卯丑亥·断涧' => [[3, 1, 11], 'duanjian', 'reverse'],
]);

test('mixed direction wrong step and invalid transmissions do not establish jianchuan', function (array $changes) {
    expect((new JianchuanRule)->match(jianchuan_facts($changes)))->toBeNull();
})->with([
    'only first step is plus two' => [['sanchuan0' => 4, 'sanchuan1' => 6, 'sanchuan2' => 9]],
    'plus two then minus two' => [['sanchuan0' => 4, 'sanchuan1' => 6, 'sanchuan2' => 4]],
    'minus two then plus two' => [['sanchuan0' => 4, 'sanchuan1' => 2, 'sanchuan2' => 4]],
    '辰未申 neither step is an interval transmission' => [['sanchuan0' => 4, 'sanchuan1' => 7, 'sanchuan2' => 8]],
    'ordinary lianzhu is not jianchuan' => [['sanchuan0' => 2, 'sanchuan1' => 3, 'sanchuan2' => 4]],
    'missing middle' => [['sanchuan1' => null]],
    'string branch rejected' => [['sanchuan0' => '4']],
    'out of range rejected' => [['sanchuan2' => 12]],
]);

test('day and initial wang xiang correction uses day stem plus initial transmission', function () {
    $match = (new JianchuanRule)->match(jianchuan_facts([
        'calculationTime' => '2031-03-01 12:00:00',
        'rigan' => 0,
        'sanchuan0' => 2,
        'sanchuan1' => 4,
        'sanchuan2' => 6,
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($match?->evidence['day_seasonal_state'])->toBe('旺')
        ->and($match?->evidence['initial_seasonal_state'])->toBe('旺')
        ->and($judgments['day_initial_wang_xiang']['matched'])->toBeTrue()
        ->and($judgments['day_initial_xiu_qiu']['matched'])->toBeFalse();
});

test('day and initial xiu qiu does not silently include si', function () {
    $rule = new JianchuanRule;

    $xiuQiu = $rule->match(jianchuan_facts([
        'calculationTime' => '2031-03-01 12:00:00',
        'rigan' => 6,
        'sanchuan0' => 0,
        'sanchuan1' => 2,
        'sanchuan2' => 4,
    ]));
    $xiuQiuJudgments = collect($xiuQiu?->evidence['judgments'] ?? [])->keyBy('code');

    expect($xiuQiu?->evidence['day_seasonal_state'])->toBe('囚')
        ->and($xiuQiu?->evidence['initial_seasonal_state'])->toBe('休')
        ->and($xiuQiuJudgments['day_initial_xiu_qiu']['matched'])->toBeTrue();

    $hasDeath = $rule->match(jianchuan_facts([
        'calculationTime' => '2031-03-01 12:00:00',
        'rigan' => 6,
        'sanchuan0' => 4,
        'sanchuan1' => 6,
        'sanchuan2' => 8,
    ]));
    $deathJudgments = collect($hasDeath?->evidence['judgments'] ?? [])->keyBy('code');

    expect($hasDeath?->evidence['initial_seasonal_state'])->toBe('死')
        ->and($deathJudgments['day_initial_xiu_qiu']['matched'])->toBeFalse();
});

test('day and initial seasonal corrections use the shared five-state system without widening xiu qiu', function (int $dayStem, array $transmissions, string $dayState, string $initialState, string $judgmentCode, bool $matched) {
    $match = (new JianchuanRule)->match(jianchuan_facts([
        'rigan' => $dayStem,
        'sanchuan0' => $transmissions[0],
        'sanchuan1' => $transmissions[1],
        'sanchuan2' => $transmissions[2],
    ]));
    $judgments = collect($match?->evidence['judgments'] ?? [])->keyBy('code');

    expect($match)->not->toBeNull()
        ->and($match?->evidence['day_seasonal_state'])->toBe($dayState)
        ->and($match?->evidence['initial_seasonal_state'])->toBe($initialState)
        ->and($judgments[$judgmentCode]['matched'])->toBe($matched);
})->with([
    '旺 + 旺' => [0, [2, 4, 6], '旺', '旺', 'day_initial_wang_xiang', true],
    '相 + 旺' => [2, [2, 4, 6], '相', '旺', 'day_initial_wang_xiang', true],
    '休 + 休' => [8, [0, 2, 4], '休', '休', 'day_initial_xiu_qiu', true],
    '囚 + 休' => [6, [0, 2, 4], '囚', '休', 'day_initial_xiu_qiu', true],
    '死 + 死' => [4, [1, 3, 5], '死', '死', 'day_initial_xiu_qiu', false],
]);
