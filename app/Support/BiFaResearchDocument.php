<?php

namespace App\Support;

/**
 * 文件作用：从毕法研究文档中提取"古籍原文"小节，供详情页独立展示。
 *
 * 研究文档结构（与 docs/毕法/NN-法名.md 一致）：
 *
 *  ## 一、体系归属
 *  ## 二、古籍原文            ← 本类只读取这一节
 *  ## 三、原文分格
 *  ## 四、现代汉语解释
 *  ## 五、程序语义
 *  ## 六、工程裁决
 *  ## 七、案例
 *  ...
 *
 * 规则：
 *
 *  - 节标题明确为 "古籍原文" 时，才标 complete；
 *  - 节标题缺失、或文档被混入"原文+整理"时，按 excerpt 展示，绝不冒充完整原文；
 *  - 文档不存在或为空时，status = missing。
 */
final class BiFaResearchDocument
{
    /** @return array{status: 'complete'|'excerpt'|'missing', heading: ?string, content: ?string} */
    public function original(array $law): array
    {
        $path = base_path((string) ($law['researchPath'] ?? ''));
        if (! is_file($path)) {
            return ['status' => 'missing', 'heading' => null, 'content' => null];
        }

        $markdown = file_get_contents($path);
        if (! is_string($markdown) || $markdown === '') {
            return ['status' => 'missing', 'heading' => null, 'content' => null];
        }

        // 完整原文：节标题为 "古籍原文"。
        $complete = $this->section(
            $markdown,
            '/^##\s+([^\r\n]*古籍原文[^\r\n]*)\R(?<body>.*?)(?=^##\s|\z)/msu',
        );
        if ($complete !== null) {
            return ['status' => 'complete', ...$complete];
        }

        // 摘录：节标题含 "原文整理"、"正文" 等模糊标题。
        $excerpt = $this->section(
            $markdown,
            '/^##\s+([^\r\n]*(?:原文整理|正文)[^\r\n]*)\R(?<body>.*?)(?=^##\s|\z)/msu',
        );
        if ($excerpt !== null) {
            return ['status' => 'excerpt', ...$excerpt];
        }

        return ['status' => 'missing', 'heading' => null, 'content' => null];
    }

    /** @return array{heading: string, content: string}|null */
    private function section(string $markdown, string $pattern): ?array
    {
        if (preg_match($pattern, $markdown, $matches) !== 1) {
            return null;
        }

        $content = trim((string) ($matches['body'] ?? ''));
        if ($content === '') {
            return null;
        }

        return [
            'heading' => trim((string) ($matches[1] ?? '')),
            'content' => $content,
        ];
    }
}