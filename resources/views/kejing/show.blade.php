<!DOCTYPE html>
<html lang="zh-CN" data-theme="liuren">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $lesson['name'] }} · 课经 · 大六壬排盘</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <div class="pan-classical-shell">
            @include('partials.pan-header')

            @php
                $interpretation = $detail['interpretation'];
                $pan = $detail['pan'];
                $xundunLabels = $detail['xundunLabels'];
                $grids = $detail['grids'] ?? [];
                $gridDefinitions = $detail['gridDefinitions'] ?? [];
                $staticDefinition = $detail['staticDefinition'];
                $hasStructuredDefinition = ($staticDefinition['foundations'] ?? []) !== []
                    || ($staticDefinition['judgments'] ?? []) !== [];
                $hasDetailContent = ($lesson['summary'] ?? '') !== ''
                    || ($lesson['researchPath'] ?? '') !== ''
                    || ($lesson['daquanCases'] ?? []) !== []
                    || ($lesson['otherCases'] ?? []) !== []
                    || ($lesson['daquanExamples'] ?? []) !== []
                    || ($lesson['otherExamples'] ?? []) !== []
                    || $hasStructuredDefinition
                    || $detail['canonicalCase'] !== null;
                $traceSpec = \App\Support\KeJingTraceView::for($interpretation, 'detail');
                $canonicalEvidenceFallback = (bool) ($traceSpec !== null && ! ($traceSpec['specialized'] ?? false));
                if ($canonicalEvidenceFallback && $hasStructuredDefinition) {
                    $traceSpec = null;
                    $canonicalEvidenceFallback = false;
                }
                $dizhi = \App\Services\PanCalculator::$dizhi;
                $tiangan = \App\Services\PanCalculator::$tiangan;
                $wuxing = \App\Services\PanCalculator::$wuxing;
                $wuxingTian = \App\Services\PanCalculator::$wuxingTian;
                $wuxingDi = \App\Services\PanCalculator::$wuxingDi;
                $jigong = \App\Services\PanCalculator::$jigong;
                $tianjiangNames = \App\Services\PanCalculator::$tianjiang;
                $liuqinNames = \App\Services\PanCalculator::$liuqin;
            @endphp

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <x-button label="返回课经" icon="o-arrow-left" :link="route('kejing')" class="btn-ghost btn-sm" />
                    @if (! empty($lesson['researchUrl']))
                        <x-button label="打开完整研究记录" icon="o-book-open" :link="$lesson['researchUrl']" external class="btn-ghost btn-sm" />
                    @endif
                </div>

                <header class="mb-6">
                    <p class="text-xs tracking-[0.18em] text-base-content/40">第 {{ $lesson['number'] }} 课 · 课经体系</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-wide text-base-content sm:text-4xl">{{ $lesson['name'] }}</h1>
                    @if (($lesson['summary'] ?? '') !== '')
                        <p class="mt-3 max-w-3xl leading-7 text-base-content/65">{{ $lesson['summary'] }}</p>
                    @endif
                </header>

                @if (! $hasDetailContent)
                    <x-card shadow class="pan-data-card">
                        <x-alert icon="o-information-circle" class="alert-info alert-soft">当前未加载专家研究内容。</x-alert>
                    </x-card>
                @else
                <x-card shadow class="pan-data-card">
                    @include('kejing.partials.interpretation-summary', [
                        'interpretation' => $interpretation,
                        'lessonPage' => $lesson,
                        'staticDefinition' => $staticDefinition,
                        'mode' => 'detail',
                    ])

                    @if ($traceSpec !== null && $pan !== null && $detail['canonicalCase'] !== null)
                        <div class="mt-5">
                            <x-collapse collapse-plus-minus>
                                <x-slot:heading>
                                    <div>
                                        @if ($canonicalEvidenceFallback)
                                            <strong>标准课例命中证据（非完整定义）</strong>
                                            <p class="mt-1 text-xs text-base-content/45">{{ $detail['canonicalCase']['label'] }} · 这里只展示当前课例实际命中的证据。</p>
                                        @else
                                            <strong>{{ $traceSpec['title'] }} · 标准课例判定细节</strong>
                                            <p class="mt-1 text-xs text-base-content/45">{{ $detail['canonicalCase']['label'] }} · 与排盘“解盘信息”复用同一判定模板</p>
                                        @endif
                                    </div>
                                </x-slot:heading>
                                <x-slot:content>
                                    @include($traceSpec['view'], [
                                        'trace' => $interpretation['evidence'],
                                        'title' => $traceSpec['title'],
                                        'tiangan' => $tiangan,
                                        'dizhi' => $dizhi,
                                        'wuxing' => $wuxing,
                                        'tianjiangNames' => $tianjiangNames,
                                        'suppressCoreTrace' => $hasStructuredDefinition && ($traceSpec['specialized'] ?? false),
                                    ])
                                </x-slot:content>
                            </x-collapse>
                        </div>
                    @endif
                </x-card>

                @if ($gridDefinitions !== [] || $grids !== [])
                    <section class="mt-6">
                        <x-card title="格" shadow class="pan-data-card">
                            @if ($gridDefinitions !== [])
                                <p class="text-sm leading-6 text-base-content/50">以下为本课正式规则定义的全部格；标准课例实际命中项在下方单独标注。</p>
                                @foreach ($gridDefinitions as $gridDefinition)
                                    @include('livewire.pan.partials.grid-trace', [
                                        'title' => $gridDefinition['name'],
                                        'trace' => ['detail' => $gridDefinition['description']],
                                    ])
                                @endforeach
                            @endif
                            @if ($grids !== [])
                                <x-alert icon="o-check-circle" class="mt-4 alert-success alert-soft">
                                    <strong>标准课例当前命中：{{ implode('、', array_column($grids, 'name')) }}</strong>
                                    @foreach ($grids as $grid)
                                        <span class="mt-1 block text-sm">{{ $grid['evidence']['detail'] ?? $grid['description'] ?? '' }}</span>
                                    @endforeach
                                </x-alert>
                            @endif
                        </x-card>
                    </section>
                @endif

                @if ($detail['uncovered'] !== [])
                    <section class="mt-6">
                        <x-card title="尚未程序化或尚待冻结的课义" shadow class="pan-data-card">
                            <p class="text-sm leading-6 text-base-content/50">这些内容来自规则命中的未覆盖说明，不作为当前成课判断，也不会被页面擅自补成规则。</p>
                            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/65">
                                @foreach ($detail['uncovered'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </x-card>
                    </section>
                @endif

                <section class="mt-6">
                    <x-card title="古籍相关课例与旁证" shadow class="pan-data-card">
                        <p class="text-sm leading-6 text-base-content/50">此处仅展示插件提供并已结构化分类的正文课例、非正文课例与研究旁证。</p>

                        <div class="mt-6">
                            <x-header title="《六壬大全》正文课例" subtitle="正文课例与正文旁证单独列出，避免和后世材料混淆。" size="text-lg" />
                            @if ($lesson['daquanCases'] !== [])
                                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                    @foreach ($lesson['daquanCases'] as $case)
                                        @include('kejing.partials.case-card', ['case' => $case, 'kind' => '正文现代复现'])
                                    @endforeach
                                </div>
                            @endif
                            @if ($lesson['daquanExamples'] !== [])
                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                    @foreach ($lesson['daquanExamples'] as $example)
                                        <div class="pan-block bg-base-100/70 px-4 py-4">
                                            <div class="flex flex-wrap items-center gap-2"><x-badge value="《大全》正文" class="badge-primary badge-soft badge-sm" /><strong class="text-sm">{{ $example['label'] }}</strong></div>
                                            @if (! empty($example['path'] ?? null))<p class="mt-2 text-xs text-base-content/45">{{ $example['path'] }}</p>@endif
                                            <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($lesson['daquanCases'] === [] && $lesson['daquanExamples'] === [])
                                <x-alert icon="o-exclamation-triangle" class="mt-3 alert-warning alert-soft">当前没有已结构化标注的《六壬大全》正文课例。</x-alert>
                            @endif
                        </div>

                        <div class="mt-6 border-t border-base-300/70 pt-6">
                            <x-header title="非正文课例与旁证" subtitle="程序补充课例、后世古籍旁证与研究用案例单独列出。" size="text-lg" />
                            @if ($lesson['otherCases'] !== [])
                                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                    @foreach ($lesson['otherCases'] as $case)
                                        @include('kejing.partials.case-card', ['case' => $case, 'kind' => '非正文课例'])
                                    @endforeach
                                </div>
                            @endif
                            @if ($lesson['otherExamples'] !== [])
                                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                    @foreach ($lesson['otherExamples'] as $example)
                                        <div class="pan-block bg-base-100/70 px-4 py-4">
                                            <div class="flex flex-wrap items-center gap-2"><x-badge :value="$example['source'] ?? '研究旁证'" class="badge-ghost badge-sm" /><strong class="text-sm">{{ $example['label'] }}</strong></div>
                                            @if (! empty($example['path'] ?? null))<p class="mt-2 text-xs text-base-content/45">{{ $example['path'] }}</p>@endif
                                            <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($lesson['otherCases'] === [] && $lesson['otherExamples'] === [])
                                <p class="mt-3 text-sm text-base-content/45">当前没有另列非正文课例或旁证。</p>
                            @endif
                        </div>
                    </x-card>
                </section>

                <section class="mt-6">
                    <x-card title="《六壬大全》原文" shadow class="pan-data-card">
                        @if ($original['status'] === 'complete')
                            <h2 class="mb-3 text-lg font-semibold">《六壬大全》完整原文</h2>
                            <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/75">{{ $original['content'] }}</div>
                        @elseif ($original['status'] === 'excerpt')
                            <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">当前只有正文整理 / 摘录，不能冒充完整原文。</x-alert>
                            <x-collapse collapse-plus-minus class="mt-4">
                                <x-slot:heading><strong>查看现有正文整理 / 摘录</strong></x-slot:heading>
                                <x-slot:content><div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/70">{{ $original['content'] }}</div></x-slot:content>
                            </x-collapse>
                        @else
                            <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">当前研究文档尚未结构化录入完整原文。本页不会根据摘要或旁证反向拼接古文。</x-alert>
                        @endif
                        @if (! empty($lesson['researchUrl']))
                            <div class="mt-4"><x-button label="打开完整研究记录" icon="o-book-open" :link="$lesson['researchUrl']" external class="btn-ghost btn-sm" /></div>
                        @endif
                    </x-card>
                </section>
                @endif

                <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="课经前后导航">
                    @if ($previousLesson !== null)
                        <x-button :label="'← 上一课 · '.$previousLesson['name']" :link="route('kejing.show', ['lesson' => $previousLesson['slug']])" class="btn-ghost" />
                    @else
                        <span></span>
                    @endif
                    @if ($nextLesson !== null)
                        <x-button :label="$nextLesson['name'].' · 下一课 →'" :link="route('kejing.show', ['lesson' => $nextLesson['slug']])" class="btn-ghost ml-auto" />
                    @endif
                </nav>
            </main>
        </div>
    </body>
</html>
