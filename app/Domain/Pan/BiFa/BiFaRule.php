<?php

namespace App\Domain\Pan\BiFa;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：规定所有毕法规则必须提供唯一编码、定义、匹配结果与人读顺序。
 *
 * 该接口与 App\Domain\Pan\Rules\PanRule 同形但独立——
 * 课经 PanRule 通过 PanRuleEngine 注入 RuleRegistry，会出现在排盘页"解盘信息"中；
 * 毕法 BiFaRule 走 BiFaRuleEngine + BiFaRuleRegistry，单独呈现于"毕法"区块。
 *
 * 两种体系的 rule 不得互相注册到对方的 registry，也不得在同一位置展示。
 */
interface BiFaRule
{
    public function code(): string;

    /**
     * 返回该法的目录元数据：法号、法名、code、slug、summary、研究文档等。
     *
     * 与 PanRule::definition() 不同，本方法返回的是页面渲染所需的页面元数据，
     * 而不是成课条件定义。毕法的"成法条件"通过 match() 返回的 BiFaRuleMatch.evidence 体现。
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
     * @return array<string, mixed>
     */
    public function definition(): array;

    public function match(PanFacts $facts): ?BiFaRuleMatch;
}