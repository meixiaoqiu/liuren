<?php

use App\Data\PanResult;
use App\Domain\Pan\BiFa\Rules\LianMuGuiRenRule;
use App\Domain\Pan\Facts\PanFacts;

function lmgr_match(array $overrides = []): mixed
{
    $base = [
        'rigan' => 0, 'rizhi' => 0, 'nianzhi' => 0, 'guirenPeriod' => 'day',
        'sanchuan0' => 0, 'sanchuan1' => 1, 'sanchuan2' => 2,
        'tianpan' => range(0, 11), 'tianjiang' => range(0, 11),
        'context' => ['people' => [['role' => 'querent', 'nianming' => 3, 'xingnian' => 4, 'xingnian_gan' => 0, 'birth_datetime' => null, 'gender' => null]]],
    ];
    $pan = array_replace($base, $overrides);

    return (new LianMuGuiRenRule)->match(PanFacts::from(new PanResult($pan)));
}

function lmgr_route(mixed $match, string $code): array
{
    return collect($match?->subMatches ?? [])->firstWhere('code', $code) ?? [];
}

test('third law exposes eight ordered routes and catalog metadata', function () {
    $rule = new LianMuGuiRenRule;
    expect($rule->code())->toBe('bifa.03')->and($rule->law()['name'])->toBe('帘幕贵人高甲第')
        ->and(array_column($rule->definition()['foundations'], 'title'))->toBe([
            '帘幕贵人临干年命', '旬首作帘幕', '辰戌旬首临干年命', '斗鬼相加', '亚魁临干年命', '德入天门', '真朱雀', '昼夜二贵拱年命',
        ]);
});

test('curtain noble reverses current period and handles stem and pending paths', function () {
    $day = lmgr_match(['tianpan' => [0, 1, 7, 3, 4, 5, 6, 2, 8, 9, 10, 11], 'context' => ['people' => []]]);
    expect($day->evidence['curtain_noble'])->toBe(7)->and(lmgr_route($day, 'curtain_noble_on_stem_or_fate')['matched'])->toBeTrue();
    $night = lmgr_match(['guirenPeriod' => 'night', 'tianpan' => [0, 1, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11], 'context' => ['people' => []]]);
    expect($night->evidence['curtain_noble'])->toBe(1)->and(lmgr_route($night, 'curtain_noble_on_stem_or_fate')['matched'])->toBeTrue();
    $pending = lmgr_match(['context' => ['people' => []]]);
    expect($pending->pendingRoutes)->toContain('curtain_noble_on_stem_or_fate');
});

test('curtain route accepts nianming only and xingnian only', function () {
    $t = range(0, 11);
    $t[3] = 7;
    $n = lmgr_match(['tianpan' => $t, 'context' => ['people' => [['role' => 'querent', 'nianming' => 3, 'xingnian' => null]]]]);
    $t = range(0, 11);
    $t[4] = 7;
    $x = lmgr_match(['tianpan' => $t, 'context' => ['people' => [['role' => 'querent', 'nianming' => null, 'xingnian' => 4]]]]);
    expect($n->matchedRoutes)->toContain('curtain_noble_on_stem_or_fate')->and($x->matchedRoutes)->toContain('curtain_noble_on_stem_or_fate');
});

test('xun head as curtain is derived for legal yi ji xin structures without a whitelist', function (int $stem, int $branch, int $lodging, int $curtain) {
    $tianpan = range(0, 11);
    $tianpan[$lodging] = $curtain;
    $match = lmgr_match(['rigan' => $stem, 'rizhi' => $branch, 'guirenPeriod' => 'day', 'tianpan' => $tianpan]);
    expect($match->matchedRoutes)->toContain('xun_head_as_curtain_noble');
})->with([
    '乙酉日' => [1, 9, 4, 8],
    '己丑日' => [5, 1, 7, 8],
    '辛酉日' => [7, 9, 10, 2],
]);

test('xun head on stem is rejected when xun head is not curtain noble', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 0;
    $match = lmgr_match(['rigan' => 0, 'rizhi' => 0, 'tianpan' => $tianpan]);

    expect($match?->matchedRoutes ?? [])->not->toContain('xun_head_as_curtain_noble');
});

test('chen and xu xun head routes are independent from curtain', function () {
    $t = range(0, 11);
    $t[7] = 4;
    $chen = lmgr_match(['rigan' => 0, 'rizhi' => 4, 'tianpan' => $t]);
    expect($chen->matchedRoutes)->toContain('chen_xu_xun_head_on_stem_or_fate')->not->toContain('xun_head_as_curtain_noble');
    $t = range(0, 11);
    $t[2] = 10;
    $xu = lmgr_match(['rigan' => 0, 'rizhi' => 10, 'tianpan' => $t]);
    expect($xu->matchedRoutes)->toContain('chen_xu_xun_head_on_stem_or_fate');
    $other = lmgr_match(['rigan' => 0, 'rizhi' => 0]);
    expect($other?->matchedRoutes ?? [])->not->toContain('chen_xu_xun_head_on_stem_or_fate');
});

test('dou gui and ya kui match stem or fate and reject other structures', function () {
    $t = range(0, 11);
    $t[7] = 1;
    expect(lmgr_match(['rigan' => 3, 'rizhi' => 1, 'tianpan' => $t])->matchedRoutes)->toContain('dou_gui_on_stem_or_fate');
    $t = range(0, 11);
    $t[1] = 7;
    expect(lmgr_match(['tianpan' => $t, 'context' => ['people' => [['role' => 'querent', 'nianming' => 1, 'xingnian' => null]]]])->matchedRoutes)->toContain('dou_gui_on_stem_or_fate');
    $t = range(0, 11);
    $t[2] = 9;
    expect(lmgr_match(['tianpan' => $t])->matchedRoutes)->toContain('ya_kui_you_on_stem_or_fate');
    $t = range(0, 11);
    $t[3] = 9;
    expect(lmgr_match(['tianpan' => $t])->matchedRoutes)->toContain('ya_kui_you_on_stem_or_fate');
    $t = range(0, 11);
    $t[4] = 9;
    expect(lmgr_match(['tianpan' => $t])->matchedRoutes)->toContain('ya_kui_you_on_stem_or_fate');
    expect(lmgr_match()?->matchedRoutes ?? [])->not->toContain('dou_gui_on_stem_or_fate')->not->toContain('ya_kui_you_on_stem_or_fate');
});

test('day virtue must both enter hai and be initial without requiring fuyin', function () {
    $t = range(0, 11);
    $t[11] = 2;
    $yes = lmgr_match(['tianpan' => $t, 'sanchuan0' => 2]);
    expect($yes->matchedRoutes)->toContain('day_virtue_enters_heaven_gate');
    expect(lmgr_match(['tianpan' => $t, 'sanchuan0' => 3])?->matchedRoutes ?? [])->not->toContain('day_virtue_enters_heaven_gate')
        ->and(lmgr_match(['sanchuan0' => 2])?->matchedRoutes ?? [])->not->toContain('day_virtue_enters_heaven_gate');
});

function lmgr_trueVermilionBirdBase(): array
{
    $t = [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5];
    $g = [];
    for ($i = 0; $i < 12; $i++) {
        $g[$i] = (2 - $i + 12) % 12;
    }

    return ['rigan' => 5, 'rizhi' => 5, 'nianzhi' => 4, 'guirenPeriod' => 'night', 'tianpan' => $t, 'tianjiang' => $g];
}

test('true vermilion bird matches on a four-season year (chen) with all four structural conditions', function () {
    $base = lmgr_trueVermilionBirdBase();
    $match = lmgr_match($base);
    $route = lmgr_route($match, 'true_vermilion_bird');
    expect($match->matchedRoutes)->toContain('true_vermilion_bird')
        ->and($match->evidence['nianzhi'])->toBe(4)
        ->and($match->evidence['nobleman_moving_backward'])->toBeTrue()
        ->and($match->evidence['general_riding_wu'])->toBe(2)
        ->and($match->evidence['true_vermilion_bird_generates_taisui'])->toBeTrue()
        ->and($match->evidence['true_vermilion_bird_controls_taisui'])->toBeFalse()
        ->and($route['detail'])->toContain('己日')->toContain('太岁为辰')->toContain('夜占')->toContain('贵人逆行')->toContain('午乘朱雀');
});

test('true vermilion bird still matches when nianzhi is shen (controls taisui)', function () {
    $base = array_replace(lmgr_trueVermilionBirdBase(), ['nianzhi' => 8]);
    $match = lmgr_match($base);
    expect($match->matchedRoutes)->toContain('true_vermilion_bird')
        ->and($match->evidence['nianzhi'])->toBe(8)
        ->and($match->evidence['true_vermilion_bird_controls_taisui'])->toBeTrue()
        ->and($match->evidence['true_vermilion_bird_generates_taisui'])->toBeFalse();
});

test('true vermilion bird still matches when nianzhi is you (controls taisui)', function () {
    $base = array_replace(lmgr_trueVermilionBirdBase(), ['nianzhi' => 9]);
    $match = lmgr_match($base);
    expect($match->matchedRoutes)->toContain('true_vermilion_bird')
        ->and($match->evidence['nianzhi'])->toBe(9)
        ->and($match->evidence['true_vermilion_bird_controls_taisui'])->toBeTrue();
});

test('true vermilion bird still matches when nianzhi is a normal year (zi)', function () {
    $base = array_replace(lmgr_trueVermilionBirdBase(), ['nianzhi' => 0]);
    $match = lmgr_match($base);
    expect($match->matchedRoutes)->toContain('true_vermilion_bird')
        ->and($match->evidence['nianzhi'])->toBe(0)
        ->and($match->evidence['true_vermilion_bird_generates_taisui'])->toBeFalse()
        ->and($match->evidence['true_vermilion_bird_controls_taisui'])->toBeFalse();
});

test('true vermilion bird rejects every structural failure but never blames nianzhi', function () {
    $base = lmgr_trueVermilionBirdBase();
    expect(lmgr_match($base)->matchedRoutes)->toContain('true_vermilion_bird');

    // 非己日：直接失败。
    expect(lmgr_match(array_replace($base, ['rigan' => 4]))?->matchedRoutes ?? [])
        ->not->toContain('true_vermilion_bird');

    // 非夜占：直接失败。
    expect(lmgr_match(array_replace($base, ['guirenPeriod' => 'day']))?->matchedRoutes ?? [])
        ->not->toContain('true_vermilion_bird');

    // 贵人非逆行（用正序天将）：直接失败。
    expect(lmgr_match(array_replace($base, ['tianjiang' => range(0, 11)]))?->matchedRoutes ?? [])
        ->not->toContain('true_vermilion_bird');

    // 午不乘朱雀（朱雀序列号 6 不再落午）：直接失败。
    expect(lmgr_match(array_replace($base, ['tianpan' => range(0, 11)]))?->matchedRoutes ?? [])
        ->not->toContain('true_vermilion_bird');

    // 任何 nianzhi 都不再是真朱雀的门槛——春夏秋冬四年之外也必须命中（连续遍历 0..11）。
    foreach (range(0, 11) as $nz) {
        expect(lmgr_match(array_replace($base, ['nianzhi' => $nz]))->matchedRoutes)
            ->toContain('true_vermilion_bird');
    }
});

test('taisui judgment evidence stays false when true vermilion bird itself is missing (nianzhi chen, shen)', function () {
    // 同样基于真朱雀的"四项主体"基线，但破坏"贵人逆行"——天将换成正序——让真朱雀不成立。
    // 同时保留斗鬼相加的结构，让第三法仍可返回 BiFaRuleMatch，从而能读 evidence。
    $base = lmgr_trueVermilionBirdBase();

    $chen = array_replace($base, ['nianzhi' => 4, 'tianjiang' => range(0, 11)]);
    $shen = array_replace($base, ['nianzhi' => 8, 'tianjiang' => range(0, 11)]);

    $chenMatch = lmgr_match($chen);
    $shenMatch = lmgr_match($shen);

    // 派生 evidence 必须以真朱雀已成立为前提；这里只破坏真朱雀，nianzhi 单独处于生/克分支。
    expect($chenMatch->matchedRoutes)->not->toContain('true_vermilion_bird')
        ->and($chenMatch->evidence['nianzhi'])->toBe(4)
        ->and($chenMatch->evidence['true_vermilion_bird_generates_taisui'])->toBeFalse()
        ->and($chenMatch->evidence['true_vermilion_bird_controls_taisui'])->toBeFalse()
        ->and($shenMatch->matchedRoutes)->not->toContain('true_vermilion_bird')
        ->and($shenMatch->evidence['nianzhi'])->toBe(8)
        ->and($shenMatch->evidence['true_vermilion_bird_generates_taisui'])->toBeFalse()
        ->and($shenMatch->evidence['true_vermilion_bird_controls_taisui'])->toBeFalse();
});

test('true vermilion bird definitions expose both taisui judgments with correct effects', function () {
    $rule = new LianMuGuiRenRule;
    $definition = $rule->definition();
    $labels = array_column($definition['judgments'], 'label');
    $effects = array_column($definition['judgments'], 'effect', 'label');

    expect($labels)->toContain('真朱雀生太岁')
        ->and($labels)->toContain('真朱雀克太岁')
        ->and($effects['真朱雀生太岁'])->toBe('increase')
        ->and($effects['真朱雀克太岁'])->toBe('reduce');
});

test('two nobles may swap and flank either fate target, while missing people is pending', function () {
    $t = range(0, 11);
    $t[2] = 1;
    $t[0] = 7;
    $n = lmgr_match(['tianpan' => $t, 'context' => ['people' => [['role' => 'querent', 'nianming' => 1, 'xingnian' => null]]]]);
    expect($n->matchedRoutes)->toContain('two_nobles_flank_fate');
    $t[2] = 7;
    $t[0] = 1;
    $x = lmgr_match(['tianpan' => $t, 'context' => ['people' => [['role' => 'querent', 'nianming' => null, 'xingnian' => 1]]]]);
    expect($x->matchedRoutes)->toContain('two_nobles_flank_fate');
    $p = lmgr_match(['tianpan' => $t, 'context' => ['people' => []]]);
    expect($p->pendingRoutes)->toContain('two_nobles_flank_fate');
});

test('two nobles do not match without flanking either fate target', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 1;
    $tianpan[0] = 7;
    $match = lmgr_match([
        'tianpan' => $tianpan,
        'context' => ['people' => [['role' => 'querent', 'nianming' => 4, 'xingnian' => 5]]],
    ]);

    expect($match?->matchedRoutes ?? [])->not->toContain('two_nobles_flank_fate');
});

test('two nobles are pending without people only after the two noble prerequisite holds', function () {
    $tianpan = range(0, 11);
    $tianpan[2] = 1;
    $tianpan[0] = 7;
    $possible = lmgr_match(['tianpan' => $tianpan, 'context' => ['people' => []]]);
    $impossible = lmgr_match(['tianpan' => range(0, 11), 'context' => ['people' => []]]);

    expect($possible->pendingRoutes)->toContain('two_nobles_flank_fate')
        ->and($impossible->pendingRoutes)->not->toContain('two_nobles_flank_fate');
});

test('dou gui is pending without people only when a dou gui position exists', function () {
    $tianpan = range(0, 11);
    $tianpan[1] = 7;
    $possible = lmgr_match(['tianpan' => $tianpan, 'context' => ['people' => []]]);
    $impossible = lmgr_match(['tianpan' => range(0, 11), 'context' => ['people' => []]]);

    expect($possible->pendingRoutes)->toContain('dou_gui_on_stem_or_fate')
        ->and($impossible->pendingRoutes)->not->toContain('dou_gui_on_stem_or_fate');
});

test('no hits and no pending returns null, while pending-only returns a match', function () {
    $withPeople = lmgr_match(['rigan' => 2, 'rizhi' => 2, 'nianzhi' => 0, 'tianpan' => range(0, 11), 'context' => ['people' => [['role' => 'querent', 'nianming' => 0, 'xingnian' => 0]]]]);
    expect($withPeople)->toBeNull();
    $missing = lmgr_match(['rigan' => 2, 'rizhi' => 2, 'nianzhi' => 0, 'tianpan' => range(0, 11), 'context' => ['people' => []]]);
    expect($missing)->not->toBeNull()->and($missing->matchedRoutes)->toBe([])->and($missing->pendingRoutes)->not->toBe([]);
});

test('matched and pending route order always follows the eight foundations', function () {
    $t = range(0, 11);
    $t[2] = 7;
    $t[4] = 9;
    $matched = lmgr_match(['tianpan' => $t]);
    expect($matched->matchedRoutes)->toBe(['curtain_noble_on_stem_or_fate', 'ya_kui_you_on_stem_or_fate']);

    $pending = lmgr_match(['rigan' => 2, 'rizhi' => 2, 'context' => ['people' => []]]);
    expect($pending->pendingRoutes)->toBe([
        'curtain_noble_on_stem_or_fate', 'ya_kui_you_on_stem_or_fate',
    ]);
});
