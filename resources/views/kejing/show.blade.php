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
                $traceSpec = \App\Support\KeJingTraceView::for($interpretation);
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
                    <x-button
                        label="返回课经"
                        icon="o-arrow-left"
                        :link="route('kejing')"
                        class="btn-ghost btn-sm"
                    />
                    <x-button
                        label="完整研究记录"
                        icon="o-book-open"
                        :link="$lesson['researchUrl']"
                        external
                        class="btn-ghost btn-sm"
                    />
                </div>

                <x-card shadow class="pan-data-card">
                    @include('kejing.partials.interpretation-summary', [
                        'interpretation' => $interpretation,
                        'lessonPage' => $lesson,
                        'staticDefinition' => $detail['staticDefinition'],
                        'mode' => 'detail',
                    ])

                    @if ($traceSpec !== null && $pan !== null && $detail['canonicalCase'] !== null)
                        <div class="mt-5">
                            <x-collapse collapse-plus-minus>
                                <x-slot:heading>
                                    <div>
                                        <strong>标准课例判定细节</strong>
                                        <p class="mt-1 text-xs text-base-content/45">{{ $detail['canonicalCase']['label'] }} · 与排盘“解盘信息”复用同一判定模板</p>
                                    </div>
                                </x-slot:heading>
                                <x-slot:content>
                                    @include($traceSpec['view'], [
                                        'trace' => $interpretation['evidence'],
                                        'title' => $traceSpec['title'],
                                    ])
                                </x-slot:content>
                            </x-collapse>
                        </div>
                    @endif
                </x-card>

                @if ($detail['uncovered'] !== [])
                    <section class="mt-6">
                        <x-card title="尚未程序化或尚待冻结的课义" shadow class="pan-data-card">
                            <p class="text-sm leading-6 text-base-content/50">这些内容来自正式 RuleMatch 的未覆盖说明，不作为当前成课 Boolean，也不会被页面擅自补成规则。</p>
                            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/65">
                                @foreach ($detail['uncovered'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </x-card>
                    </section>
                @endif

                <section class="mt-6">
                    <x-card shadow class="pan-data-card">
                        <x-collapse collapse-plus-minus>
                            <x-slot:heading>
                                <span class="font-semibold">古籍相关课例与旁证</span>
                            </x-slot:heading>
                            <x-slot:content>
                                <p class="text-sm leading-6 text-base-content/50">
                                    此处仅保留与成课判定直接相关的古籍材料摘要；完整原文、冲突、取舍与统计见上方研究记录。
                                </p>
                            </x-slot:content>
                        </x-collapse>
                    </x-card>

                    <x-header
                        title="《六壬大全》正文课例"
                        subtitle="正文课例全部保留；能由正式程序复现者可直接点击排盘，原文材料则逐条列出。"
                        size="text-lg"
                    />

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
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge value="《大全》正文" class="badge-primary badge-soft badge-sm" />
                                        <strong class="text-sm">{{ $example['label'] }}</strong>
                                    </div>
                                    @if (! empty($example['path'] ?? null))
                                        <p class="mt-2 text-xs text-base-content/45">{{ $example['path'] }}</p>
                                    @endif
                                    <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($lesson['daquanCases'] === [] && $lesson['daquanExamples'] === [])
                        <x-alert icon="o-exclamation-triangle" class="mt-3 alert-warning alert-soft">
                            当前目录数据尚未结构化标注《六壬大全》正文课例；请以研究记录为准，页面不自行推定。
                        </x-alert>
                    @endif
                </section>

                <section class="mt-6">
                    <x-header
                        title="非正文课例与旁证"
                        subtitle="程序补充课例、后世古籍旁证与研究用案例单独列出，避免与《大全》正文混淆。"
                        size="text-lg"
                    />

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
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if (! empty($example['source'] ?? null))
                                            <x-badge :value="$example['source']" class="badge-ghost badge-sm" />
                                        @else
                                            <x-badge value="研究旁证" class="badge-ghost badge-sm" />
                                        @endif
                                        <strong class="text-sm">{{ $example['label'] }}</strong>
                                    </div>
                                    @if (! empty($example['path'] ?? null))
                                        <p class="mt-2 text-xs text-base-content/45">{{ $example['path'] }}</p>
                                    @endif
                                    <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($lesson['otherCases'] === [] && $lesson['otherExamples'] === [])
                        <p class="mt-3 text-sm text-base-content/45">当前没有另列非正文课例或旁证。</p>
                    @endif
                </section>

                <section class="mt-6">
                    <x-card title="《六壬大全》完整原文" shadow class="pan-data-card">
                        @if ($original['status'] === 'complete')
                            <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/75">{{ $original['content'] }}</div>
                        @elseif ($original['status'] === 'excerpt')
                            <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                现有研究文档只有“{{ $original['heading'] }}”整理段，尚未按“《六壬大全》完整原文”结构化录入。为避免把摘录冒充完整原文，本页明确标记为未完成。
                            </x-alert>
                            <x-collapse collapse-plus-minus class="mt-4">
                                <x-slot:heading><strong>查看现有正文整理 / 摘录</strong></x-slot:heading>
                                <x-slot:content>
                                    <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/70">{{ $original['content'] }}</div>
                                </x-slot:content>
                            </x-collapse>
                        @else
                            <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                当前研究文档尚未结构化录入“《六壬大全》完整原文”。本页不会根据摘要或旁证反向拼接古文；补入研究文档后，这里会自动显示。
                            </x-alert>
                        @endif

                        <div class="mt-4">
                            <x-button
                                label="打开完整研究记录"
                                icon="o-book-open"
                                :link="$lesson['researchUrl']"
                                external
                                class="btn-ghost btn-sm"
                            />
                        </div>
                    </x-card>
                </section>

                <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="课经前后导航">
                    @if ($previousLesson !== null)
                        <x-button
                            :label="'← 上一课 · '.$previousLesson['name']"
                            :link="route('kejing.show', ['lesson' => $previousLesson['slug']])"
                            class="btn-ghost"
                        />
                    @else
                        <span></span>
                    @endif

                    @if ($nextLesson !== null)
                        <x-button
                            :label="$nextLesson['name'].' · 下一课 →'"
                            :link="route('kejing.show', ['lesson' => $nextLesson['slug']])"
                            class="btn-ghost ml-auto"
                        />
                    @endif
                </nav>
            </main>
        </div>
    </body>
</html>
