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
        'quzhi_fire_day_shengqi',
        'quzhi_water_day_daoqi',
        'quzhi_ji_rooted',
        'quzhi_ding_withered',
        'quzhi_xin_material',
        'quzhi_wei_on_hai',
        'quzhi_hai_on_wei',
        'quzhi_mao_on_hai',
        'quzhi_mao_on_wei',
        'congge_water_day_shengqi',
        'congge_earth_day_daoqi',
        'congge_with_qi_advance',
        'congge_without_qi_retreat',
        'jiase_wuji_harder',
        'jiase_rengui_release',
        'jiase_thunder_god',
    );

    $seasonalJudgments = collect($definition['judgments'])
        ->filter(static fn (array $judgment): bool =>
            str_starts_with($judgment['code'], 'grid_seasonal_state_')
            || str_starts_with($judgment['code'], 'initial_seasonal_state_')
        )
        ->values();

    expect($seasonalJudgments)->toHaveCount(10);
    foreach ($seasonalJudgments as $judgment) {
        expect($judgment['effect'])->toBe('neutral');
    }
});

test('quanju detail page lists all five grid definitions and marks the canonical matched grid', function () {
    $response = $this->get(route('kejing.show', ['lesson' => 'quanju']))
        ->assertOk()
        ->assertSee('五格任一成立')
        ->assertSee('以下为本课正式规则定义的全部格')
        ->assertSee('润下格')
        ->assertSee('炎上格')
        ->assertSee('曲直格')
        ->assertSee('从革格')
        ->assertSee('稼穑格')
        ->assertSee('三传完整构成申子辰水局。')
        ->assertSee('三传完整构成寅午戌火局。')
        ->assertSee('三传完整构成亥卯未木局。')
        ->assertSee('三传完整构成巳酉丑金局。')
        ->assertSee('三传全部属于辰、戌、丑、未四季土。')
        ->assertSee('标准课例当前命中：炎上格')
        ->assertSee('三传戌→午→寅完整构成三合火局，成炎上格。')
        ->assertSee('润下·木日得生气')
        ->assertSee('炎上·庚辛日带杀')
        ->assertSee('曲直·火日得生气')
        ->assertSee('曲直·己日根固')
        ->assertSee('从革·水日得生气')
        ->assertSee('从革·有气革而进')
        ->assertSee('从革·无气革而退')
        ->assertSee('尚未按“《六壬大全》完整原文”结构化录入')
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
