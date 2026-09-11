<?php

use App\Domain\Pan\BranchRelations;
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
        ->assertSee('十二天将');
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

test('quick reference page does not expose internal representations', function () {
    $this->get(route('reference'))
        ->assertOk()
        ->assertDontSee('PanCalculator')
        ->assertDontSee('BranchRelations')
        ->assertDontSee('0 =&gt; 子', false)
        ->assertDontSee('index')
        ->assertDontSee('数组索引');
});
