<?php

/** 文件作用：锁定第57课盘珠课详情页已经录入《六壬大全》完整原文，不再回退到摘录警告。 */

test('panzhu detail page exposes complete daquan original text', function () {
    $this->get(route('kejing.show', ['lesson' => 'panzhu']))
        ->assertOk()
        ->assertSee('《六壬大全》完整原文')
        ->assertSee('凡太岁、月建及日、时并三传皆在四课之中，曰盘珠课也')
        ->assertSee('若天空朱雀临太岁，主朝信即动，尤的')
        ->assertSee('此课吉事成福，若占病讼、阴私、生产、忧疑、解释事反凶')
        ->assertDontSee('尚未按“《六壬大全》完整原文”结构化录入')
        ->assertDontSee('当前研究文档尚未结构化录入“《六壬大全》完整原文”');
});
