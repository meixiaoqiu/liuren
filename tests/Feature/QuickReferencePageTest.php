<?php

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Shensha\ZaieShensha;
use App\Services\PanCalculator;
use App\Support\QuickReferenceCatalog;

test('quick reference page exists and renders the shared navigation', function () {
    $this->get(route('reference'))
        ->assertOk()
        ->assertSee('速查')
        ->assertSee('排盘')
        ->assertSee('课经');

    $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('速查');

    $this->get(route('pan.create'))
        ->assertOk()
        ->assertSee('速查');
});

test('quick reference page contains the required foundation tables', function () {
    $this->get(route('reference'))
        ->assertOk()
        ->assertSee('四孟')
        ->assertSee('四仲')
        ->assertSee('四季')
        ->assertSee('六冲')
        ->assertSee('六破')
        ->assertSee('六合')
        ->assertSee('六害')
        ->assertSee('三合')
        ->assertSee('刑')
        ->assertSee('十干寄宫')
        ->assertSee('旺相休囚死')
        ->assertSee('旬空')
        ->assertSee('十二月将')
        ->assertSee('十二天将')
        ->assertSee('神煞')
        ->assertSee('灾厄课·月神')
        ->assertSee('灾厄课·岁神')
        ->assertSee('三丘五墓');
});

test('catalog display data follows the existing domain definitions', function () {
    $branches = PanCalculator::$dizhi;

    $expectedLodgings = array_map(fn (int $index, int $stem): array => [
        'stem' => PanCalculator::$tiangan[$stem],
        'branch' => $branches[$index],
    ], PanCalculator::$jigong, array_keys(PanCalculator::$jigong));

    $expectedPunishments = [];
    foreach (PanCalculator::$xing as $source => $target) {
        $expectedPunishments[] = $branches[$source].' → '.$branches[$target];
    }
    sort($expectedPunishments);

    $catalogPunishments = collect(QuickReferenceCatalog::punishments())->pluck('relations')->flatten()->all();
    sort($catalogPunishments);

    $expectedClashes = [];
    foreach (BranchRelations::CHONG as $source => $target) {
        if ($source < $target) {
            $expectedClashes[] = $branches[$source].$branches[$target];
        }
    }

    $expectedBreaks = [];
    foreach (BranchRelations::PO as $source => $target) {
        if ($source < $target) {
            $expectedBreaks[] = $branches[$source].$branches[$target];
        }
    }

    $expectedLiuhe = array_map(fn (array $pair): string => $branches[$pair[0]].$branches[$pair[1]], BranchRelations::LIUHE_PAIRS);
    $catalogSanheSets = array_map(function (array $group) use ($branches): array {
        $indexes = array_map(fn (string $branch): int => array_search($branch, $branches, true), mb_str_split($group['branches']));
        sort($indexes);

        return $indexes;
    }, QuickReferenceCatalog::sanhe());
    sort($catalogSanheSets);
    $domainSanheSets = BranchRelations::SANHE_TRIPLES;
    sort($domainSanheSets);

    $expectedMonthGenerals = array_map(function (string $definition): array {
        [$branch, $name, $range] = explode('/', $definition);

        return compact('branch', 'name', 'range');
    }, PanCalculator::$yuejiang);

    expect(QuickReferenceCatalog::stemLodgings())->toBe($expectedLodgings)
        ->and($catalogPunishments)->toBe($expectedPunishments)
        ->and(QuickReferenceCatalog::clashes())->toBe($expectedClashes)
        ->and(QuickReferenceCatalog::breaks())->toBe($expectedBreaks)
        ->and(QuickReferenceCatalog::liuhe())->toBe($expectedLiuhe)
        ->and($catalogSanheSets)->toBe($domainSanheSets)
        ->and(QuickReferenceCatalog::monthGenerals())->toBe($expectedMonthGenerals)
        ->and(QuickReferenceCatalog::heavenlyGenerals())->toBe(PanCalculator::$tianjiang);
});

test('quick reference shensha data shares the same algorithm as ZaieShensha', function () {
    expect(QuickReferenceCatalog::zaieMonthly())->toBe(ZaieShensha::monthlyTable())
        ->and(QuickReferenceCatalog::zaieYearly())->toBe(ZaieShensha::yearlyTable())
        ->and(QuickReferenceCatalog::zaieQiuMu())->toBe(ZaieShensha::qiuMuTable());
});

test('quick reference locks representative shensha values for yin month and hai year', function () {
    $branches = PanCalculator::$dizhi;

    // 寅月代表值：丧车=未、游魂=亥、伏殃=酉、三丘=丑、五墓=未
    $yinRow = collect(QuickReferenceCatalog::zaieMonthly())
        ->firstWhere('month_name', '寅');
    expect($yinRow)->not->toBeNull()
        ->and($yinRow['sangche'])->toBe('未')
        ->and($yinRow['youhun'])->toBe('亥')
        ->and($yinRow['fuyang'])->toBe('酉')
        ->and($yinRow['sanqiu'])->toBe('丑')
        ->and($yinRow['wumu'])->toBe('未');

    // 亥年代表值：病符=戌、丧门=丑、吊客=酉、岁虎=未
    $haiRow = collect(QuickReferenceCatalog::zaieYearly())
        ->firstWhere('year_name', '亥');
    expect($haiRow)->not->toBeNull()
        ->and($haiRow['bingfu'])->toBe('戌')
        ->and($haiRow['sangmen'])->toBe('丑')
        ->and($haiRow['diaoke'])->toBe('酉')
        ->and($haiRow['suihu'])->toBe('未');

    // 周期边界：四月（巳）丧车=戌、伏殃=子；五月（午）丧车=未、伏殃=酉
    $siRow = collect(QuickReferenceCatalog::zaieMonthly())->firstWhere('month_name', '巳');
    $wuRow = collect(QuickReferenceCatalog::zaieMonthly())->firstWhere('month_name', '午');
    expect($siRow['sangche'])->toBe('戌')->and($siRow['fuyang'])->toBe('子')
        ->and($wuRow['sangche'])->toBe('未')->and($wuRow['fuyang'])->toBe('酉');

    // 三丘五墓季节表内容
    expect(QuickReferenceCatalog::zaieQiuMu())->toBe([
        ['season' => '春', 'month_set' => '寅卯辰', 'sanqiu' => '丑', 'wumu' => '未'],
        ['season' => '夏', 'month_set' => '巳午未', 'sanqiu' => '辰', 'wumu' => '戌'],
        ['season' => '秋', 'month_set' => '申酉戌', 'sanqiu' => '未', 'wumu' => '丑'],
        ['season' => '冬', 'month_set' => '亥子丑', 'sanqiu' => '戌', 'wumu' => '辰'],
    ]);
});

test('quick reference page does not expose internal representations', function () {
    $this->get(route('reference'))
        ->assertOk()
        ->assertDontSee('PanCalculator')
        ->assertDontSee('BranchRelations')
        ->assertDontSee('0 =&gt; 子', false)
        ->assertDontSee('index')
        ->assertDontSee('数组索引');
});
