<?php

namespace App\Domain\Pan\BiFa;

use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 文件作用：规定所有毕法规则必须提供唯一编码、目录定位、定义、匹配结果。
 *
 * 该接口与 App\Domain\Pan\Rules\PanRule 同形但独立——
 * 课经 PanRule 通过 PanRuleEngine 注入 RuleRegistry，会出现在排盘页"解盘信息"中；
 * 毕法 BiFaRule 走 BiFaRuleEngine + BiFaRuleRegistry，单独呈现于"毕法"区块。
 *
 * 两种体系的 rule 不得互相注册到对方的 registry，也不得在同一位置展示。
 */
interface BiFaRule
{
    /**
     * 程序唯一编码；按目录约定为 `bifa.NN`，由 `BiFaCatalog::codeFor(number)` 生成。
     */
    public function code(): string;

    /**
     * 返回该法的目录元数据：法号、法名、code、slug、summary。
     *
     * 实现必须从 `BiFaCatalog` 找到对应目录项；找不到视为编程错误，抛出 LogicException。
     * 禁止用 fallback 数组掩盖 catalog 错误。
     *
     * @return array{
     *     number: int,
     *     name: string,
     *     code: string,
     *     slug: string,
     *     summary: string
     * }
     */
    public function law(): array;

    /**
     * 返回该法的程序定义：
     *
     *  - description  :string    现代汉语总纲；
     *  - foundations  :list      古籍分格定义（9 类古籍分格）；
     *  - judgments    :list      命中后的吉凶判断（多数毕法不在程序里硬定）；
     *
     * @return array{description: string, foundations: list<array{code: string, title: string, description: string}>, judgments: list<array<string, mixed>>}
     */
    public function definition(): array;

    /**
     * 正常排盘只返回"已命中"或"存在待评估路线"的 BiFaRuleMatch；
     * 全部 route 既不命中也无待评估路线时返回 null。
     */
    public function match(PanFacts $facts): ?BiFaRuleMatch;
}
