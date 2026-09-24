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

test('BiFaResearchDocument extracts only the verified original text for the second law', function () {
    $page = BiFaPageCatalog::findByCode('bifa.02');
    expect($page)->not->toBeNull();

    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];

    expect($result['status'])->toBe('complete')
        ->and($content)->toContain('谓干上有旬尾，支上有旬首')
        ->and($content)->toContain('惟乙未、辛丑、丙申、壬寅、戊申五日有之')
        ->and($content)->toContain('回还格，乃三传在四课之中')
        ->and($content)->not->toContain('象曰：先凶后吉')
        ->and($content)->not->toContain('结构穷尽')
        ->and($content)->not->toContain('现代生产复现')
        ->and($content)->not->toContain('## 三');
});

test('BiFaResearchDocument returns missing for unresearched law', function () {
    // 第 5..100 法都尚未研究（4 法已研究）
    $page = BiFaPageCatalog::findByCode('bifa.05');
    expect($page)->not->toBeNull();
    expect($page['researched'])->toBeFalse();

    $result = (new BiFaResearchDocument)->original($page);
    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});

test('BiFaResearchDocument extracts verified original text for the third law', function () {
    $page = BiFaPageCatalog::findByCode('bifa.03');
    expect($page)->not->toBeNull()->and($page['researched'])->toBeTrue();
    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];
    expect($result['status'])->toBe('complete')
        ->and($content)->toContain('帘幕官者，如昼占乃夜贵，夜占乃昼贵')
        ->and($content)->toContain('德入天门格')
        ->and($content)->toContain('真朱雀格')
        ->and($content)->toContain('六已日')
        ->and($content)->toContain('源消根断格')
        ->and($content)->toContain('帘幕贵人，尤分喜畏')
        ->and($content)->toContain('占武举法')
        ->and($content)->not->toContain('## 三');
});

test('BiFaResearchDocument returns missing when researchPath does not exist', function () {
    $result = (new BiFaResearchDocument)->original([
        'researchPath' => 'docs/毕法/non-existent.md',
    ]);

    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});
