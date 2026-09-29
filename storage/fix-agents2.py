#!/usr/bin/env python3
# -*- coding: utf-8 -*-
path = '/var/www/html/liuren/AGENTS.md'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

replacements = [
    (
        '- 内部实现名称必须转换为用户语言后再展示，例如将上述相关标识转换为\u201c引从天干\u201d\u201c二贵拱年命\u201d\u201c干支并初中拱地盘贵人\u201d\u201c命中\u201d\u201c待评估\u201d等中文名称或状态文本。',
        '- 内部实现名称必须转换为用户语言后再展示，对应的中文名称与状态文本由插件或具体领域模块负责定义；本仓库仅在通用提示层面禁止泄漏这些标识。'
    ),
    (
        '- 例如，内部标识 `yin_gan` 必须配套用户名称\u201c引从天干\u201d和用户说明\u201c初传居日干前，末传居日干后。\u201d。',
        '- 例如，新增任何课经、毕法、神煞或格局规则时，必须同时给出内部标识、用户名称和用户说明；中文用户名称与说明的具体内容由领域模块自行定义。'
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