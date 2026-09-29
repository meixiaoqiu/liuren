#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
path = '/var/www/html/liuren/AGENTS.md'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

replacements = [
    (
        '- `yin_gan`、`er_gui_gang_nianming`、`gan_zhi_bing_chu_zhong_gui`、`BiFaRuleEngine`、`QianHouYinCongRule`、`matched_routes`、`pending_routes` 等均属于内部实现名称，不得显示给用户。',
        '- 任何具体内部实现标识（典型如某些 route code、Rule 类名、Match DTO 内部字段等）均属于内部实现名称，不得显示给用户。'
    ),
    (
        '- 内部实现名称必须转换为用户语言后再展示，例如将上述相关标识转换为"引从天干""二贵拱年命""干支并初中拱地盘贵人""命中""待评估"等中文名称或状态文本。',
        '- 内部实现名称必须转换为用户语言后再展示，对应的中文名称与状态文本由插件或具体领域模块负责定义；本仓库仅在通用提示层面禁止泄漏这些标识。'
    ),
    (
        '- 本条不约束的程序内部结构：URL slug（`cui-guan-shi-zhe`）、route name（`bifa.show`）、case_id、JSON API 字段名（`tianpan`、`sanchuan0`、`guirenPeriod` 等）、`BiFaRuleMatch::$evidence` 键、`PanResult::get()` 的 key、测试断言字符串、源代码标识、数据库列名。',
        '- 本条不约束的程序内部结构：URL slug、route name、case_id、JSON API 字段名、Match DTO 内部键、PanResult::get() 的 key、测试断言字符串、源代码标识、数据库列名。'
    ),
    (
        '- 例如，内部标识 `yin_gan` 必须配套用户名称"引从天干"和用户说明"初传居日干前，末传居日干后。"。',
        '- 例如，新增任何课经、毕法、神煞或格局规则时，必须同时给出内部标识、用户名称和用户说明；中文用户名称与说明的具体内容由领域模块自行定义。'
    ),
    (
        '- 不得使用 `assertSee(\'yin_gan\')`、`assertSee(\'BiFaRuleEngine\')` 等断言把内部实现名称固化为用户界面内容。',
        '- 不得使用 `assertSee(...)` 等断言把内部实现名称固化为用户界面内容。'
    ),
    (
        '- 应断言用户语言，例如 `assertSee(\'引从天干\')`、`assertSee(\'二贵拱年命\')`。',
        '- 应断言用户语言，例如 `assertSee(\'命中\')`、`assertSee(\'待评估\')` 等用户可见状态词汇。'
    ),
]

for old, new in replacements:
    if old in text:
        text = text.replace(old, new)
        print(f'replaced: {old[:30]}...')
    else:
        print(f'NOT FOUND: {old[:30]}...')

with open(path, 'w', encoding='utf-8') as f:
    f.write(text)
print('saved')