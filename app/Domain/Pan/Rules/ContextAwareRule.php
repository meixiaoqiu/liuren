<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：声明规则依赖的占测上下文（如人物角色），使规则引擎能在信息不足时区分“未评估”与“不成立”。 */
interface ContextAwareRule extends PanRule
{
    /**
     * 规则成课所需的上下文标识列表，如 ['people.querent', 'people.spouse']。
     *
     * @return list<string>
     */
    public function requiredContext(): array;

    /**
     * 上下文缺失、规则未执行时前台展示的信息。
     *
     * @return array{name: string, notice: string}
     */
    public function notEvaluatedInfo(): array;
}
