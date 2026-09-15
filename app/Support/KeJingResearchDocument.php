<?php

namespace App\Support;

/**
 * 文件作用：从课经研究文档提取《六壬大全》原文区域供详情页展示。
 *
 * 只有标题明确写为“《六壬大全》完整原文”的章节才标记 complete；旧文档中的“正文”“正文定义”等章节只作为
 * excerpt 展示，绝不冒充完整原文。这样页面能够诚实暴露历史资料缺口，并允许后续只补 Markdown 而无需改 Blade。
 */
final class KeJingResearchDocument
{
    /** @return array{status: 'complete'|'excerpt'|'missing', heading: ?string, content: ?string} */
    public function original(array $lesson): array
    {
        $path = base_path((string) ($lesson['researchPath'] ?? ''));
        if (! is_file($path)) {
            return ['status' => 'missing', 'heading' => null, 'content' => null];
        }

        $markdown = file_get_contents($path);
        if (! is_string($markdown) || $markdown === '') {
            return ['status' => 'missing', 'heading' => null, 'content' => null];
        }

        $complete = $this->section($markdown, '/^##\s+([^\r\n]*《六壬大全》完整原文[^\r\n]*)\R(?<body>.*?)(?=^##\s|\z)/msu');
        if ($complete !== null) {
            return ['status' => 'complete', ...$complete];
        }

        $excerpt = $this->section($markdown, '/^##\s+([^\r\n]*《六壬大全》正文[^\r\n]*)\R(?<body>.*?)(?=^##\s|\z)/msu');
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
