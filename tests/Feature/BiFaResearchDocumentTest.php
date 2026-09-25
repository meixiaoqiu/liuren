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
    // 第 9..100 法尚未研究（前 8 法已研究）
    $page = BiFaPageCatalog::findByCode('bifa.09');
    expect($page)->not->toBeNull();
    expect($page['researched'])->toBeFalse();

    $result = (new BiFaResearchDocument)->original($page);
    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});

test('seventh law original extraction contains only verified original text', function () {
    $page = BiFaPageCatalog::findByCode('bifa.07');
    expect($page)->not->toBeNull()->and($page['researched'])->toBeTrue();

    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];

    expect($result['status'])->toBe('complete')
        ->and($content)->toContain('旺禄临身徒妄作第七')
        ->and($content)->toContain('谓日之禄神，又作日之旺神，临于干上者')
        ->and($content)->toContain('虽不系己土旺神，亦可用也')
        ->and($content)->toContain('禄被元夺格')
        ->and($content)->not->toContain('## 三')
        ->and($content)->not->toContain('程序语义')
        ->and($content)->not->toContain('工程裁决')
        ->and($content)->not->toContain('正式算法')
        ->and($content)->not->toContain('阴干白名单');
});

test('seventh law research keeps source ownership and white tiger reduction outside original text', function () {
    $page = BiFaPageCatalog::findByCode('bifa.07');
    $document = (string) file_get_contents(base_path($page['researchPath']));
    $original = (new BiFaResearchDocument)->original($page);

    expect($document)->toContain('维基文库《六壬大全》卷十一《毕法赋》上')
        ->and($document)->toContain('识典古籍《御定六壬直指·毕法赋》')
        ->and($document)->toContain('康立波国学《御定六壬直指》第六十三卷')
        ->and($document)->toContain('此格单就六阴日干说')
        ->and($document)->toContain('白虎乘禄只作减损，不独立取消「宜守旺禄」')
        ->and($document)->toContain('白虎受制、仍以守成为主')
        ->and((string) $original['content'])->not->toContain('白虎乘禄只作减损')
        ->and((string) $original['content'])->not->toContain('御定六壬直指');
});

test('sixth law 古籍原文 sections are the full BiFa article and liuchun side-evidence stays separate', function () {
    $page = BiFaPageCatalog::findByCode('bifa.06');
    expect($page)->not->toBeNull()->and($page['researched'])->toBeTrue();

    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];
    $document = (string) file_get_contents(base_path($page['researchPath']));

    expect($result['status'])->toBe('complete')
        ->and($result['heading'])->toContain('古籍原文')
        // 完整《毕法赋》第六法正文的四个关键句必须在 古籍原文 节中。
        ->and($content)->toContain('六阴相继尽昏迷第六')
        ->and($content)->toContain('六阴格 谓课传皆居六阴之位是也')
        ->and($content)->toContain('五阴格 课传止五阴者')
        ->and($content)->toContain('源消根断格 如癸卯、癸未、癸巳')
        ->and($content)->toContain('又如辛卯日干上子')
        ->and($content)->toContain('凡占利私不利公，利小人不利君子')
        // 课经 六纯课 旁证 不得混入 古籍原文 节。
        ->and($content)->not->toContain('丑卯巳为出户')
        ->and($content)->not->toContain('课经')
        ->and($content)->not->toContain('旁证')
        ->and($content)->not->toContain('甲辰日干上午')
        // 课经 六纯课 旁证 必须作为独立章节存在于文档中（但不进入 original()）。
        ->and($document)->toContain('## 三、课经')
        ->and($document)->toContain('不是《毕法赋》第六法正文')
        // 「止四日四课」正式出处改为《六壬大全》卷一《补论》。
        ->and($document)->toContain('《六壬大全》卷一《补论》')
        ->and($document)->toContain('止四日四课')
        // 后续章节标题不得泄漏到 古籍原文 节。
        ->and($content)->not->toContain('## 三')
        ->and($content)->not->toContain('## 四');
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

test('fifth law separates bifa original text from the liuchun corroboration section', function () {
    $page = BiFaPageCatalog::findByCode('bifa.05');
    expect($page)->not->toBeNull()->and($page['researched'])->toBeTrue();

    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];
    $document = (string) file_get_contents(base_path($page['researchPath']));

    expect($result['status'])->toBe('complete')
        ->and($content)->toContain('《六壬大全·毕法赋》第五法')
        ->and($content)->toContain('庚子日')
        ->and($content)->toContain('五阳格')
        ->and($content)->not->toContain('课经“六纯课”旁证')
        ->and($document)->toContain('## 三、课经“六纯课”旁证')
        ->and($document)->toContain('不是《毕法赋》第五法正文');
});

test('BiFaResearchDocument returns missing when researchPath does not exist', function () {
    $result = (new BiFaResearchDocument)->original([
        'researchPath' => 'docs/毕法/non-existent.md',
    ]);

    expect($result['status'])->toBe('missing')
        ->and($result['content'])->toBeNull();
});

test('eighth law original extraction contains only verified original text without program semantics', function () {
    $page = BiFaPageCatalog::findByCode('bifa.08');
    expect($page)->not->toBeNull()->and($page['researched'])->toBeTrue();

    $result = (new BiFaResearchDocument)->original($page);
    $content = (string) $result['content'];

    expect($result['status'])->toBe('complete')
        ->and($content)->toContain('权摄不正禄临支第八')
        ->and($content)->toContain('日干禄神加临支辰')
        ->and($content)->toContain('禄被支墓克脱')
        ->and($content)->not->toContain('## 三')
        ->and($content)->not->toContain('程序语义')
        ->and($content)->not->toContain('工程裁决')
        ->and($content)->not->toContain('DAY_LU')
        ->and($content)->not->toContain('lu_on_branch');
});
