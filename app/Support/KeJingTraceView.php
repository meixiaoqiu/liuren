<?php

namespace App\Support;

use App\Extensions\KeJingExtensionRegistry;

/** 文件作用：把插件注册的课经专属判定视图适配为页面可用的通用规格。 */
final class KeJingTraceView
{
    /** @return array{view: string, title: string, specialized: bool}|null */
    public static function for(array $interpretation, string $context): ?array
    {
        $code = $interpretation['code'] ?? null;
        if (! is_string($code) || $code === '') {
            return null;
        }

        try {
            $registry = app(KeJingExtensionRegistry::class);
        } catch (\Throwable) {
            return null;
        }

        $spec = $registry->traceViewFor($code);

        $name = (string) ($interpretation['name'] ?? '课经');
        $title = $spec['title'] ?? (($interpretation['marker'] ?? null) === '格'
            ? $name.'依据'
            : (preg_replace('/课$/u', '', $name) ?: $name).'判断');

        if ($spec !== null && in_array($context, $spec['contexts'], true)) {
            return ['view' => $spec['view'], 'title' => $title, 'specialized' => true];
        }

        if (! $registry->hasRuleCode($code)) {
            return null;
        }

        if (($interpretation['marker'] ?? null) === '格') {
            return [
                'view' => 'livewire.pan.partials.grid-trace',
                'title' => $title,
                'specialized' => false,
            ];
        }

        if (str_starts_with($code, 'lesson.')) {
            return [
                'view' => 'livewire.pan.partials.lesson-trace',
                'title' => $title,
                'specialized' => false,
            ];
        }

        return null;
    }
}
