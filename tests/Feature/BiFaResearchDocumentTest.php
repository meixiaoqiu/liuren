<?php

use App\Support\BiFaPageCatalog;
use App\Support\BiFaResearchDocument;

/**
 * 验证详情页只展示 "古籍原文" 节，绝不把后续的 "原文分格 / 现代汉语解释 / 程序语义 / 工程裁决 / 案例"
 * 等整理章节混入。
 */
test('BiFaResearchDocument extracts the 古籍原文 section for the researched first law', function () {
    $page = BiFaPageCatalog::findByCode('bifa.01');
    expect($page)->not->toBeNull();
    expect($page['researched'])->toBeTrue();

    $result = (new BiFaResearchDocument)->original($page);

    expect($result['status'])->toBe('complete')
        ->and($result['heading'])->toContain('古籍原文')
        ->and($result['content'])->not->toBeNull();

    $content = (string) $result['content'];

    // 古籍原文主体应当出现（"前引后从" / 5 等结构 / 庚辰日 等案例）
    expect($content)->toContain('前引后从')
        ->and($content)->toContain('庚辰日')
        ->and($content)->toContain('壬子日')
        ->and($content)->toContain('象曰');

    // 后续章节标题不允许泄漏到古籍原文区域
    expect($content)->not->toContain('## 三')
        ->and($content)->not->toContain('## 四')
        ->and($content)->not->toContain('## 五')
        ->and($content)->not->toContain('## 六')
        ->and($content)->not->toContain('## 七')
        // "现代汉语解释" 章节标题内的字节"四个
        ->and($content)->not->toContain('现代汉语解释')
        ->and($content)->not->toContain('程序语义')
        ->and($content)->not->toContain('工程裁决');
});

test('BiFaResearchDocument returns missing for unresearched law', function () {
    // 第 2..100 法都尚未研究
    $page = BiFaPageCatalog::findByCode('bifa.02');
    expect($page)->not->toBeNull();
    expect($page['researched'])->toBeFalse();

    $result = (new BiFaResearchDocument)->original($page);
    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});

test('BiFaResearchDocument returns missing when researchPath does not exist', function () {
    $result = (new BiFaResearchDocument)->original([
        'researchPath' => 'docs/毕法/non-existent.md',
    ]);

    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});
