<?php

use App\Domain\Pan\Rules\RuleRegistry;
use App\Support\KeJingCatalog;
use App\Support\KeJingPageCatalog;

test('quanju static definition exposes one explicit OR foundation and every programmed judgment', function () {
    $definition = collect((new RuleRegistry)->rules())
        ->first(static fn ($rule): bool => $rule->code() === 'lesson.quanju')
        ?->definition();

    expect($definition)->not->toBeNull()
        ->and($definition['foundations'])->toHaveCount(1)
        ->and($definition['foundations'][0]['code'])->toBe('quanju_any_grid')
        ->and($definition['foundations'][0]['title'])->toBe('五格任一成立')
        ->and($definition['foundations'][0]['description'])->toContain('任一结构成立即成全局课');

    $judgmentCodes = array_column($definition['judgments'], 'code');

    expect($judgmentCodes)->toContain(
        'sanhe_forward',
        'sanhe_reverse',
        'grid_seasonal_state_wang',
        'grid_seasonal_state_si',
        'initial_seasonal_state_wang',
        'initial_seasonal_state_si',
        'liuhe_assists_grid',
        'runxia_wood_day_shengqi',
        'runxia_metal_day_daoqi',
        'yanshang_earth_day_shengqi',
        'yanshang_wood_day_daoqi',
        'yanshang_gengxin_kill',
        'yanshang_rengui_zimugui',
        'yanshang_xu_on_yin',
        'yanshang_wu_on_xu',
        'quzhi_ji_rooted',
        'quzhi_ding_withered',
        'quzhi_xin_material',
        'quzhi_wei_on_hai',
        'quzhi_hai_on_wei',
        'quzhi_mao_on_hai',
        'quzhi_mao_on_wei',
        'congge_water_day_shengqi',
        'congge_earth_day_daoqi',
        'jiase_wuji_harder',
        'jiase_rengui_release',
        'jiase_thunder_god',
    );
});

test('quanju detail page reuses the current grid RuleMatch and shows complete static judgments', function () {
    $response = $this->get(route('kejing.show', ['lesson' => 'quanju']))
        ->assertOk()
        ->assertSee('五格任一成立')
        ->assertSee('以下为标准课例当前实际命中的传统格')
        ->assertSee('炎上格')
        ->assertSee('三传戌→午→寅完整构成三合火局，成炎上格。')
        ->assertSee('润下·木日得生气')
        ->assertSee('炎上·庚辛日带杀')
        ->assertSee('曲直·己日根固')
        ->assertSee('从革·水日得生气')
        ->assertSee('稼穑·戊己日更艰难');

    expect(substr_count($response->getContent(), '古籍相关课例与旁证'))->toBe(1);
});

test('quanju executable reproductions are classified as daquan structure reproductions', function () {
    $catalogLesson = collect(KeJingCatalog::lessons())->firstWhere('code', 'lesson.quanju');
    $pageLesson = KeJingPageCatalog::findByCode('lesson.quanju');

    expect($catalogLesson)->not->toBeNull()
        ->and($pageLesson)->not->toBeNull()
        ->and($catalogLesson['cases'])->toHaveCount(2)
        ->and($pageLesson['daquanCases'])->toHaveCount(2)
        ->and($pageLesson['otherCases'])->toBe([]);

    foreach ($catalogLesson['cases'] as $case) {
        expect($case['source_type'])->toBe('daquan')
            ->and($case['status'])->toBe('executable')
            ->and($case['label'])->toContain('正文结构现代复现');
    }
});
