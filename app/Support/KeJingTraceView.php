<?php

namespace App\Support;

use App\Extensions\KeJingExtensionRegistry;

/** 文件作用：把插件注册的课经专属判定视图适配为页面可用的通用规格。 */
final class KeJingTraceView
{
    /** @return array{view: string, title: string}|null */
    public static function for(array $interpretation, string $context): ?array
    {
        $code = $interpretation['code'] ?? null;
        if (! is_string($code) || $code === '') {
            return null;
        }

        try {
            $spec = app(KeJingExtensionRegistry::class)->traceViewFor($code);
        } catch (\Throwable) {
            return null;
        }

        if ($spec === null || ! in_array($context, $spec['contexts'], true)) {
            return null;
        }

        $name = (string) ($interpretation['name'] ?? '课经');
        $title = $spec['title'] ?? (($interpretation['marker'] ?? null) === '格'
            ? $name.'依据'
            : (preg_replace('/课$/u', '', $name) ?: $name).'判断');

        return ['view' => $spec['view'], 'title' => $title];
    }
}
