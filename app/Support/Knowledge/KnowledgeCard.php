<?php

namespace App\Support\Knowledge;

/**
 * 课经、毕法与格共用的纯展示 DTO。
 *
 * 所有属性只能保存可以直接呈现给用户的内容，不承载规则 code、类名、方法名、
 * 原始证据数组或调试数据。code 表示“第一法”等用户可读编号。
 */
final readonly class KnowledgeCard
{
    /**
     * @param  array{label: string, tone: string}|null  $status
     * @param  list<array{title: string, description: string, status: array{label: string, tone: string}|null, detail: ?string, marker?: string}>  $conditions
     * @param  list<array{label: string, detail: string}>  $evidence
     * @param  list<array{title: string, content: string}>  $sections
     * @param  list<array{title: string, description: string, source: string, status: array{label: string, tone: string}, url: ?string}>  $examples
     * @param  list<array{label: string, url: string, icon: ?string, external: bool}>  $actions
     */
    public function __construct(
        public string $type,
        public string $code,
        public string $title,
        public string $summary,
        public ?array $status = null,
        public array $conditions = [],
        public array $evidence = [],
        public array $sections = [],
        public array $examples = [],
        public array $actions = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'code' => $this->code,
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
