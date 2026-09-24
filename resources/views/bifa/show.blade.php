<!DOCTYPE html>
<html lang="zh-CN" data-theme="liuren">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $law['name'] }} · 毕法 · 大六壬排盘</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <div class="pan-classical-shell">
            @include('partials.pan-header')

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <x-button
                        label="返回毕法"
                        icon="o-arrow-left"
                        :link="route('bifa')"
                        class="btn-ghost btn-sm"
                    />

                    @if ($law['researched'])
                        <x-button
                            label="打开完整研究记录"
                            icon="o-book-open"
                            :link="$law['researchUrl']"
                            external
                            class="btn-ghost btn-sm"
                        />
                    @endif
                </div>

                <header class="mb-6">
                    <p class="text-xs tracking-[0.18em] text-base-content/40">第 {{ $law['number'] }} 法 · 毕法体系</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-wide text-base-content sm:text-4xl">{{ $law['name'] }}</h1>
                    @if ($law['summary'] !== '')
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-base-content/65">{{ $law['summary'] }}</p>
                    @endif
                </header>

                @if (! $law['researched'])
                    <x-card shadow class="pan-data-card">
                        <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                            本法尚未研究：详情页仅保留目录信息，无分格定义、无盘面判定、无研究文档。后续按"逐法逐步实现"原则补齐。
                        </x-alert>
                    </x-card>
                @else
                    @if ($knowledgeCard !== null)
                        <x-knowledge-card :card="$knowledgeCard" />
                    @endif

                    @if (! empty($original))
                        <section class="mt-6">
                            <x-card title="古籍原文" shadow class="pan-data-card">
                                @if ($original['status'] === 'complete')
                                    <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/75">{{ $original['content'] }}</div>
                                @elseif ($original['status'] === 'excerpt')
                                    <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                        现有研究文档只有"{{ $original['heading'] }}"整理段，尚未按"《六壬大全》完整原文"结构化录入。为避免把摘录冒充完整原文，本页明确标记为未完成。
                                    </x-alert>
                                    <x-collapse collapse-plus-minus class="mt-4">
                                        <x-slot:heading><strong>查看现有正文整理 / /</strong></x-slot:heading>
                                        <x-slot:content>
                                            <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/70">{{ $original['content'] }}</div>
                                        </x-slot:content>
                                    </x-collapse>
                                @else
                                    <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                        当前研究文档尚未结构化录入"《六壬大全》完整原文"。本页不会根据摘要或旁证反向拼接古文；补入研究文档后，这里会自动显示。
                                    </x-alert>
                                @endif

                                <div class="mt-4">
                                    <x-button
                                        label="打开完整研究记录"
                                        icon="o-book-open"
                                        :link="$law['researchUrl']"
                                        external
                                        class="btn-ghost btn-sm"
                                    />
                                </div>
                            </x-card>
                        </section>
                    @endif
                @endif

                <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="毕法前后导航">
                        @if (! empty($previousLaw))
                            <x-button
                                :label="'← 上一法 · '.$previousLaw['name']"
                                :link="route('bifa.show', ['law' => $previousLaw['slug']])"
                                class="btn-ghost"
                            />
                        @else
                            <span></span>
                        @endif

                        @if (! empty($nextLaw))
                            <x-button
                                :label="$nextLaw['name'].' · 下一法 →'"
                                :link="route('bifa.show', ['law' => $nextLaw['slug']])"
                                class="btn-ghost ml-auto"
                            />
                        @endif
                </nav>
            </main>
        </div>
    </body>
</html>
