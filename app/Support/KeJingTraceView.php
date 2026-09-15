<?php

namespace App\Support;

/** 文件作用：让课经详情页复用排盘“解盘信息”已经存在的判定 trace 模板。 */
final class KeJingTraceView
{
    /** @var array<string, string> */
    private const SPECIAL_VIEWS = [
        'lesson.sanguang' => 'livewire.pan.partials.sanguang-trace',
        'lesson.sanyang' => 'livewire.pan.partials.sanyang-trace',
        'lesson.sanqi' => 'livewire.pan.partials.sanqi-trace',
        'lesson.liuyi' => 'livewire.pan.partials.liuyi-trace',
        'lesson.shitai' => 'livewire.pan.partials.shitai-trace',
        'lesson.guanjue' => 'livewire.pan.partials.guanjue-trace',
        'lesson.fugui' => 'livewire.pan.partials.fugui-trace',
        'lesson.longde' => 'livewire.pan.partials.longde-trace',
        'lesson.xuangai' => 'livewire.pan.partials.xuangai-trace',
        'lesson.de_qing' => 'livewire.pan.partials.deqing-trace',
        'lesson.he_huan' => 'livewire.pan.partials.hehuan-trace',
        'lesson.he_mei' => 'livewire.pan.partials.hemei-trace',
        'lesson.zhan_guan' => 'livewire.pan.partials.zhanguan-trace',
        'lesson.qinhai' => 'livewire.pan.partials.qinhai-trace',
        'lesson.xingshang' => 'livewire.pan.partials.xingshang-trace',
        'lesson.erfan' => 'livewire.pan.partials.erfan-trace',
        'lesson.tianhuo' => 'livewire.pan.partials.tianhuo-trace',
        'lesson.tianyu' => 'livewire.pan.partials.tianyu-trace',
        'lesson.tiankou' => 'livewire.pan.partials.tiankou-trace',
        'lesson.tianwang' => 'livewire.pan.partials.tianwang-trace',
        'lesson.pohua' => 'livewire.pan.partials.pohua-trace',
        'lesson.sanyin' => 'livewire.pan.partials.sanyin-trace',
        'lesson.longzhan' => 'livewire.pan.partials.longzhan-trace',
        'lesson.siqi' => 'livewire.pan.partials.siqi-trace',
        'lesson.zaie' => 'livewire.pan.partials.zaie-trace',
        'lesson.yangjiu' => 'livewire.pan.partials.yangjiu-trace',
        'lesson.jiuchou' => 'livewire.pan.partials.jiuchou-trace',
        'lesson.guimu' => 'livewire.pan.partials.guimu-trace',
        'lesson.lide' => 'livewire.pan.partials.lide-trace',
    ];

    /** @return array{view: string, title: string}|null */
    public static function for(array $interpretation): ?array
    {
        $code = (string) ($interpretation['code'] ?? '');
        if (! str_starts_with($code, 'lesson.')) {
            return null;
        }

        $view = self::SPECIAL_VIEWS[$code] ?? null;
        if ($view === null) {
            return null;
        }

        $name = (string) ($interpretation['name'] ?? '课经');
        $baseName = preg_replace('/课$/u', '', $name) ?: $name;

        return [
            'view' => $view,
            'title' => $baseName.'判断',
        ];
    }
}
