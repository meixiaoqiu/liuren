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
                    <x-card title="总纲与现代说明" shadow class="pan-data-card">
                        <p class="text-sm leading-7 text-base-content/65">
                            本法由毕法独立规则判定：一张盘可能同时命中本法的多个分格；命中任一分格即本法整体成立，断义随命中的分格变化。
                        </p>
                        <p class="mt-4 text-sm leading-7 text-base-content/65">
                            本法与课经第 22 课“引从课”在结构上大量重合，但二者属于不同知识体系；毕法有独立的分格定义、判定依据和断义，不从课经结论反向推定。
                        </p>
                    </x-card>

                    <section class="mt-6">
                        <x-card title="成立条件（9 类古籍分格）" shadow class="pan-data-card">
                            <p class="mb-3 text-sm text-base-content/55">
                                引从天干与初末引从地支均须“初在前、末在后”，前后方向不可互换；其余夹拱结构不区分两端次序。
                            </p>
                            <ul class="list-disc space-y-2 pl-5 text-sm leading-7 text-base-content/70">
                                @foreach (($definition['foundations'] ?? []) as $foundation)
                                    <li>
                                        <strong class="text-base-content/85">{{ $foundation['title'] }}</strong>
                                        <span class="ml-2 text-base-content/55">— {{ $foundation['description'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </x-card>
                    </section>

                    @if (! empty($law['daquanCases']) || ! empty($law['referenceOnlyCases']))
                        <section class="mt-6">
                            <x-card title="《六壬大全》正文案例" shadow class="pan-data-card">
                                <p class="mb-3 text-sm text-base-content/55">
                                    第一法古籍正文出现的案例全部进入案例目录；
                                    能够完整复现的案例可直接查看排盘；尚未完整复现的案例仅作为原文参考。
                                </p>
                                @if (! empty($law['daquanCases']))
                                    <div class="grid gap-3 lg:grid-cols-2">
                                        @foreach ($law['daquanCases'] as $case)
                                            @include('bifa.partials.case-card', ['case' => $case, 'kind' => '正文现代复现'])
                                        @endforeach
                                    </div>
                                @endif

                                @if (! empty($law['referenceOnlyCases']))
                                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                        @foreach ($law['referenceOnlyCases'] as $case)
                                            <div class="pan-block bg-warning/8 px-4 py-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-badge value="《大全》原文" class="badge-primary badge-soft badge-sm" />
                                                    <x-badge value="原文参考盘 · 尚未完整复现" class="badge-warning badge-soft badge-sm" />
                                                    <strong class="text-sm">{{ $case['label'] }}</strong>
                                                </div>
                                                <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $case['description'] }}</p>
                                                <p class="mt-1 text-xs text-base-content/45">{{ $case['source_label'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </x-card>
                        </section>
                    @endif

                    @if (! empty($law['generatedCases']))
                        <section class="mt-6">
                            <x-card title="程序验证案例" shadow class="pan-data-card">
                                <p class="mb-3 text-sm text-base-content/55">
                                    第一法由程序验证覆盖的边界案例——覆盖无人物资料、缺本命/行年、完全不命中等独立路径。
                                </p>
                                <div class="grid gap-3 lg:grid-cols-2">
                                    @foreach ($law['generatedCases'] as $case)
                                        @include('bifa.partials.case-card', ['case' => $case, 'kind' => '程序验证案例'])
                                    @endforeach
                                </div>
                            </x-card>
                        </section>
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
