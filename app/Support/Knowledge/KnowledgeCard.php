<?php

namespace App\Support\Knowledge;

use InvalidArgumentException;

/**
 * 课经、毕法与格共用的纯展示 DTO。
 *
 * 设计原则（避免未来模型被任一知识体系污染）：
 *
 *  - 所有属性只能保存可以直接呈现给用户的内容，不承载规则 code、类名、方法名、
 *    原始证据数组或调试数据；
 *  - `type` 为稳定内部类型键（`bifa` / `kejing` / `pattern` …），用于跨体系识别；
 *    UI 不得基于此字符串做业务分支判断；
 *  - `typeLabel` 为中文展示名（"毕法" / "课经" / "格"），由 Factory 提供，Blade 仅展示；
 *  - `label` 为面向用户的展示编号（"第 1 法" / "第 22 课" / "格·三光"），不是程序 code；
 *  - `status.tone` 必须是以下枚举之一：`success` / `warning` / `info` / `neutral`，
 *    禁止任意字符串。
 */
final readonly class KnowledgeCard
{
    public const TONE_SUCCESS = 'success';

    public const TONE_WARNING = 'warning';

    public const TONE_INFO = 'info';

    public const TONE_NEUTRAL = 'neutral';

    private const VALID_TONES = [self::TONE_SUCCESS, self::TONE_WARNING, self::TONE_INFO, self::TONE_NEUTRAL];

    /**
     * @param  array{label: string, tone: string}|null  $status  tone 必须是 VALID_TONES 之一
     * @param  list<array{title: string, description: string, status: array{label: string, tone: string}|null, detail: ?string, marker?: string}>  $conditions
     * @param  list<array{label: string, detail: string}>  $evidence
     * @param  list<array{title: string, content: string}>  $sections
     * @param  list<array{title: string, description: string, source: string, status: array{label: string, tone: string}, url: ?string}>  $examples
     * @param  list<array{label: string, url: string, icon: ?string, external: bool}>  $actions
     */
    public function __construct(
        public string $type,
        public string $typeLabel,
        public string $label,
        public string $title,
        public string $summary,
        public ?array $status = null,
        public array $conditions = [],
        public array $evidence = [],
        public array $sections = [],
        public array $examples = [],
        public array $actions = [],
    ) {
        if ($this->type === '') {
            throw new InvalidArgumentException('KnowledgeCard type 不能为空。');
        }
        if ($this->typeLabel === '') {
            throw new InvalidArgumentException('KnowledgeCard typeLabel 不能为空（由 Factory 注入展示中文）。');
        }
        // label 可为空字符串：详情页注入 "第 N 法" / "第 N 课"，排盘块省略法序号。
        if ($this->status !== null) {
            self::assertValidStatus($this->status);
        }
        foreach ($this->conditions as $condition) {
            if (($condition['status'] ?? null) !== null) {
                self::assertValidStatus($condition['status']);
            }
        }
        foreach ($this->examples as $example) {
            if (($example['status'] ?? null) !== null) {
                self::assertValidStatus($example['status']);
            }
        }
    }

    /**
     * @param  array{label: string, tone: string}  $status
     */
    public static function assertValidStatus(array $status): void
    {
        if (! isset($status['label'], $status['tone'])) {
            throw new InvalidArgumentException('KnowledgeCard status 必须含 label 与 tone。');
        }
        if (! in_array($status['tone'], self::VALID_TONES, true)) {
            throw new InvalidArgumentException(
                'KnowledgeCard status.tone 必须是 '.implode(' / ', self::VALID_TONES).' 之一，收到：'.$status['tone'],
            );
        }
    }

    /** @return list<string> */
    public static function validTones(): array
    {
        return self::VALID_TONES;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'type_label' => $this->typeLabel,
            'label' => $this->label,
            'title' => $this->title,
            'summary' => $this->summary,
            'status' => $this->status,
            'conditions' => $this->conditions,
            'evidence' => $this->evidence,
            'sections' => $this->sections,
            'examples' => $this->examples,
            'actions' => $this->actions,
        ];
    }
}
