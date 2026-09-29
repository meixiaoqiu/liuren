<?php

namespace App\Domain\Pan\Rules;

/**
 * 文件作用：为尚未结构化录入静态课义的 PanRule 提供 definition() 默认骨架。
 *
 * 已经具备结构化定义的具体课经规则会直接覆盖 definition()，
 * 返回完整结构化字段。
 *
 * 其余规则使用本 trait，详情页会通过 definition() 的 foundations 与 judgments
 * 字段判断该课是否已经迁移到静态定义。空数组仅表示"尚未结构化录入"，
 * 绝不表示该课没有成立条件或增益、减损、例外条件。
 */
trait LessonDefinitionDefaults
{
    public function definition(): array
    {
        return [
            'description' => '',
            'xiang' => null,
            'foundations' => [],
            'judgments' => [],
        ];
    }
}
