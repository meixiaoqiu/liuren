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

                    @if ($researched)
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
                    <h1 class="text-3xl font-semibold tracking-wide text-base-content sm:text-4xl">
                        第 {{ $law['number'] }} 法
                    </h1>
                    <p class="mt-2 text-xl font-medium tracking-wide text-primary sm:text-2xl">{{ $law['name'] }}</p>
                    @if ($law['summary'] !== '')
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-base-content/65">{{ $law['summary'] }}</p>
                    @endif
                </header>

                @if (! $researched)
                    <x-card shadow class="pan-data-card">
                        <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                            本法尚未研究：详情页仅保留目录信息，无分格定义、无盘面判定、无研究文档。后续按"逐法逐步实现"原则补齐。
                        </x-alert>
                    </x-card>
                @else
                    <x-card shadow class="pan-data-card">
                        <x-header
                            title="研究文档"
                            subtitle="毕法体系研究记录入口"
                            size="text-lg"
                        />

                        <div class="prose mt-4 max-w-none text-base-content/75">
                            @if ($researchContent !== null)
                                <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8">{{ $researchContent }}</div>
                            @else
                                <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                    研究文档路径存在但内容为空，请到 docs/毕法/{{ $law['researchFilename'] }} 补齐内容。
                                </x-alert>
                            @endif
                        </div>

                        <div class="mt-6">
                            <x-button
                                label="打开完整研究记录"
                                icon="o-book-open"
                                :link="$law['researchUrl']"
                                external
                                class="btn-ghost btn-sm"
                            />
                        </div>
                    </x-card>

                    <section class="mt-6">
                        <x-card title="毕法体系说明" shadow class="pan-data-card">
                            <p class="text-sm leading-7 text-base-content/65">
                                本法由毕法独立规则判定：一张盘可能同时命中本法的多个分格；命中任一分格即本法整体成立，断义随命中的分格变化。判定的具体证据、当前盘面是否命中各分格，将出现在排盘页的"毕法"独立区块；本页仅展示目录与研究文档。
                            </p>
                            <p class="mt-4 text-sm leading-7 text-base-content/65">
                                本法与课经第 22 课"引从课"在结构上大量重合，但二者属不同知识体系；毕法不通过课经"引从课"的 match() 反推，分格定义、断义与课经各自独立维护。
                            </p>
                        </x-card>
                    </section>
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