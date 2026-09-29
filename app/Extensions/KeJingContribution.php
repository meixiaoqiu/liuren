<?php

namespace App\Extensions;

/**
 * 文件作用：描述外部插件一次性贡献给课经体系的规则、摘要、案例、旁证与研究文档。
 */
final readonly class KeJingContribution
{
    public function __construct(
        public array $rules = [],
        public array $summaries = [],
        public array $cases = [],
        public array $sourceExamples = [],
        public array $researchDocuments = [],
        public array $lessonMetadata = [],
        public array $traceViews = [],
    ) {}
}
