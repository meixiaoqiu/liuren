<?php

/** 文件作用：锁定灾厄课"九神煞 OR 入口 + 不需要克日干/日支/白虎"边界，覆盖九个单发用与多神煞重叠。 */

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Domain\Pan\Rules\ZaieRule;
use App\Domain\Pan\Shensha\ZaieShensha;
use App\Services\PanCalculator;

/**
 * 构造最小可用 PanFacts。
 *
 * - 默认 yuezhi=寅(2)、nianzhi=亥(11)、sanchuan0=未(7)、tianpan 为恒等映射、tianjiang 全部 0。
 * - 寅月 yuezhi=2 时：丧车=未(7)、游魂=亥(11)、伏殃=酉(9)、三丘=丑(1)、五墓=未(7)。
 * - 亥年 nianzhi=11 时：病符=戌(10)、丧门=丑(1)、吊客=酉(9)、岁虎=未(7)。
 * - 默认初传=7=未：正月丧车=未、亥年岁虎=未。
 *   因此默认事实会同时命中 丧车 + 岁虎 两个 key。
 */
function zaie_facts(array $changes = []): PanFacts
{
    $base = [
        'calculationTime' => '2026-02-04 12:00:00',
        'yuezhi' => 2,
        'nianzhi' => 11,
        'sanchuan0' => 7,
        'tianpan' => range(0, 11),
        'tianjiang' => array_fill(0, 12, 0),
    ];

    return PanFacts::from(new PanResult(array_replace($base, $changes)));
}

test('zaie rule code is lesson.zaie and metadata is stable', function () {
    $rule = new ZaieRule;
    expect($rule->code())->toBe('lesson.zaie');
    $registry = new RuleRegistry;
    $codes = array_map(fn ($r) => $r->code(), $registry->rules());
    expect($codes[array_search('lesson.zaie', $codes, true) - 1] ?? null)->toBe('lesson.siqi');
});

test('shensha for yin month hai year matches documented representative values', function () {
    $shensha = ZaieShensha::forPan(2, 11);
    expect(PanCalculator::$dizhi[$shensha['sangche']])->toBe('未')
        ->and(PanCalculator::$dizhi[$shensha['youhun']])->toBe('亥')
        ->and(PanCalculator::$dizhi[$shensha['fuyang']])->toBe('酉')
        ->and(PanCalculator::$dizhi[$shensha['sanqiu']])->toBe('丑')
        ->and(PanCalculator::$dizhi[$shensha['wumu']])->toBe('未');

    expect(PanCalculator::$dizhi[$shensha['bingfu']])->toBe('戌')
        ->and(PanCalculator::$dizhi[$shensha['sangmen']])->toBe('丑')
        ->and(PanCalculator::$dizhi[$shensha['diaoke']])->toBe('酉')
        ->and(PanCalculator::$dizhi[$shensha['suihu']])->toBe('未');
});

test('year shensha for zi year matches documented representative values', function () {
    $shensha = ZaieShensha::forPan(0, 0);
    expect(PanCalculator::$dizhi[$shensha['bingfu']])->toBe('亥')
        ->and(PanCalculator::$dizhi[$shensha['sangmen']])->toBe('寅')
        ->and(PanCalculator::$dizhi[$shensha['diaoke']])->toBe('戌')
        ->and(PanCalculator::$dizhi[$shensha['suihu']])->toBe('申');
});

test('monthly cycle boundary: si month returns to wei for sangche and you for fuyang', function () {
    // 四月（巳，yuezhi=5）：丧车=戌、伏殃=子
    $april = ZaieShensha::forPan(5, 0);
    expect(PanCalculator::$dizhi[$april['sangche']])->toBe('戌')
        ->and(PanCalculator::$dizhi[$april['fuyang']])->toBe('子');

    // 五月（午，yuezhi=6）：丧车重新回到未、伏殃重新回到酉
    $may = ZaieShensha::forPan(6, 0);
    expect(PanCalculator::$dizhi[$may['sangche']])->toBe('未')
        ->and(PanCalculator::$dizhi[$may['fuyang']])->toBe('酉');
});

test('qiu mu uses yuezhi season not day stem burial and ignores soil 18 day period', function () {
    // 春（寅卯辰）→ 三丘丑、五墓未
    foreach ([2, 3, 4] as $branch) {
        $s = ZaieShensha::forPan($branch, 0);
        expect(PanCalculator::$dizhi[$s['sanqiu']])->toBe('丑')
            ->and(PanCalculator::$dizhi[$s['wumu']])->toBe('未');
    }
    // 夏（巳午未）→ 三丘辰、五墓戌
    foreach ([5, 6, 7] as $branch) {
        $s = ZaieShensha::forPan($branch, 0);
        expect(PanCalculator::$dizhi[$s['sanqiu']])->toBe('辰')
            ->and(PanCalculator::$dizhi[$s['wumu']])->toBe('戌');
    }
    // 秋（申酉戌）→ 三丘未、五墓丑
    foreach ([8, 9, 10] as $branch) {
        $s = ZaieShensha::forPan($branch, 0);
        expect(PanCalculator::$dizhi[$s['sanqiu']])->toBe('未')
            ->and(PanCalculator::$dizhi[$s['wumu']])->toBe('丑');
    }
    // 冬（亥子丑）→ 三丘戌、五墓辰
    foreach ([11, 0, 1] as $branch) {
        $s = ZaieShensha::forPan($branch, 0);
        expect(PanCalculator::$dizhi[$s['sanqiu']])->toBe('戌')
            ->and(PanCalculator::$dizhi[$s['wumu']])->toBe('辰');
    }
});

test('each of the nine shensha independently matches when its branch equals the initial', function (string $key, int $month, int $year, int $initial) {
    $changes = ['yuezhi' => $month, 'nianzhi' => $year, 'sanchuan0' => $initial];
    $match = (new ZaieRule)->match(zaie_facts($changes));
    expect($match)->not->toBeNull();
    expect($match->evidence['matched_keys'])->toContain($key);
})->with([
    'sangche_yin' => ['sangche', 2, 0, 7],
    'youhun_yin' => ['youhun', 2, 0, 11],
    'fuyang_yin' => ['fuyang', 2, 0, 9],
    'bingfu_zi' => ['bingfu', 0, 0, 11],
    'sangmen_zi' => ['sangmen', 0, 0, 2],
    'diaoke_zi' => ['diaoke', 0, 0, 10],
    'sanqiu_chun' => ['sanqiu', 2, 0, 1],
    'wumu_chun' => ['wumu', 2, 0, 7],
    'suihu_zi' => ['suihu', 0, 0, 8],
]);

test('matcher rejects when none of the nine shensha equals the initial', function () {
    // yuezhi=5(巳月, 丧车=戌、游魂=申、伏殃=子、三丘=辰、五墓=戌) +
    // nianzhi=0(子年, 病符=亥、丧门=寅、吊客=戌、岁虎=申) +
    // initial=5 = 巳. 没有 9 煞落在巳。
    $match = (new ZaieRule)->match(zaie_facts(['yuezhi' => 5, 'nianzhi' => 0, 'sanchuan0' => 5]));
    expect($match)->toBeNull();
});

test('matcher does not require tiger or white tiger or any general', function () {
    // 单独让 sanchuan0=7（未）命中丧车、岁虎；tianjiang 全部为青龙（5）。
    $match = (new ZaieRule)->match(zaie_facts(['sanchuan0' => 7, 'tianjiang' => array_fill(0, 12, 5)]));
    expect($match)->not->toBeNull();
    expect($match->evidence['matched_keys'])->toContain('sangche');
});

test('multiple shensha on same initial are all preserved in evidence', function () {
    // 寅月（yuezhi=2）：丧车=未、五墓=未；
    // 亥年（nianzhi=11）：岁虎=未；3 个 key 共享 7=未。
    $match = (new ZaieRule)->match(zaie_facts(['yuezhi' => 2, 'nianzhi' => 11, 'sanchuan0' => 7]));
    expect($match)->not->toBeNull();
    expect($match->evidence['matched_keys'])->toContain('sangche');
    expect($match->evidence['matched_keys'])->toContain('wumu');
    expect($match->evidence['matched_keys'])->toContain('suihu');
    // 4 煞标题必须全部出现
    $titles = array_column($match->evidence['foundations'], 'title');
    expect($titles)->toContain('丧车（又名丧魂）发用');
    expect($titles)->toContain('五墓发用');
    expect($titles)->toContain('岁虎发用');
});

test('month shensha uses yuezhi not yuejiang', function () {
    // 当 yuezhi 给出"丧车=7=未"而 yuejiang 给另外值时，仍按 yuezhi 计算。
    $match = (new ZaieRule)->match(zaie_facts(['yuezhi' => 2, 'yuejiang' => 6, 'nianzhi' => 0, 'sanchuan0' => 7]));
    expect($match)->not->toBeNull();
    expect($match->evidence['matched_keys'])->toContain('sangche');
    // 改变 yuejiang 不应改变判定。
    $match2 = (new ZaieRule)->match(zaie_facts(['yuezhi' => 2, 'yuejiang' => 0, 'nianzhi' => 0, 'sanchuan0' => 7]));
    expect($match2?->evidence['matched_keys'])->toContain('sangche');
});

test('year shensha uses nianzhi not person nianming or xingnian', function () {
    // nianzhi=子(0) → 岁虎=申(8)；只要 initial=8 即可命中，与 querent nianming/xingnian 无关。
    $match = (new ZaieRule)->match(zaie_facts(['yuezhi' => 0, 'nianzhi' => 0, 'sanchuan0' => 8]));
    expect($match)->not->toBeNull();
    expect($match->evidence['matched_keys'])->toContain('suihu');
});

test('evidence structure includes all required keys', function () {
    $match = (new ZaieRule)->match(zaie_facts());
    expect($match?->evidence)->toHaveKeys([
        'month_branch', 'year_branch', 'initial', 'shensha', 'matched_keys', 'foundations', 'judgments', 'uncovered',
    ]);
});

test('invalid required branch indexes safely reject', function (array $changes) {
    expect((new ZaieRule)->match(zaie_facts($changes)))->toBeNull();
})->with([
    'invalid yuezhi' => [['yuezhi' => 12]],
    'invalid nianzhi' => [['nianzhi' => 12]],
    'invalid initial' => [['sanchuan0' => 12]],
]);

test('foundations describe only matched shensha not all nine', function () {
    $match = (new ZaieRule)->match(zaie_facts(['yuezhi' => 2, 'nianzhi' => 0, 'sanchuan0' => 7]));
    expect($match)->not->toBeNull();
    $titles = array_column($match->evidence['foundations'], 'title');
    // 默认每月 5 个未发用煞不应出现"发用"标题。
    foreach (['游魂', '伏殃', '病符', '丧门', '吊客', '三丘'] as $unmatched) {
        foreach ($titles as $title) {
            expect($title)->not->toContain($unmatched.'发用');
        }
    }
});
