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

            @php
                $originalCases = array_merge($law['daquanCases'] ?? [], $law['referenceOnlyCases'] ?? []);
                $generatedCases = $law['generatedCases'] ?? [];
            @endphp

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
                    <p class="text-xs tracking-[0.18em] text-base-content/40">第 {{ $law['number'] }} 法 · 毕法体系</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-wide text-base-content sm:text-4xl">{{ $law['name'] }}</h1>
                </header>

                {{-- 古籍原文：紧贴 H1 下方，位于知识卡片（含"毕"字标识）上方；按段落优雅展示 --}}
                @if (! empty($original) && $original['status'] !== 'missing')
                    <section class="mb-8" aria-label="古籍原文">
                        @if ($original['status'] === 'complete')
                            <x-card shadow class="pan-data-card">
                                <div class="mb-4 flex items-center gap-3">
                                    <div class="flex h-9 shrink-0 items-stretch">
                                        <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">典</span>
                                        <span class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">古籍原文</span>
                                    </div>
                                </div>
                                @php
                                    // markdown 引用块（每行以 `>` 开头）的分段：先把每行去掉 `> ` 前缀，
                                    // 再按"空行（含仅含 `>` 的行）"切段，渲染为多个 <p>。
                                    $rawLines = preg_split('/\R/u', (string) $original['content']) ?: [];
                                    $normalized = array_map(
                                        static fn (string $line): string => preg_replace('/^\s*>\s?/u', '', $line) ?? $line,
                                        $rawLines,
                                    );
                                    $originalParagraphs = [];
                                    $buffer = [];
                                    foreach ($normalized as $line) {
                                        if (trim($line) === '') {
                                            if ($buffer !== []) {
                                                $originalParagraphs[] = trim(implode("\n", $buffer));
                                                $buffer = [];
                                            }
                                        } else {
                                            $buffer[] = $line;
                                        }
                                    }
                                    if ($buffer !== []) {
                                        $originalParagraphs[] = trim(implode("\n", $buffer));
                                    }
                                @endphp
                                @if ($originalParagraphs === [])
                                    <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/75">{{ $original['content'] }}</div>
                                @else
                                    <div class="space-y-4 font-serif text-[0.95rem] leading-8 text-base-content/80">
                                        @foreach ($originalParagraphs as $paragraph)
                                            <p class="whitespace-pre-line indent-8">{{ $paragraph }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </x-card>
                        @elseif ($original['status'] === 'excerpt')
                            <x-card shadow class="pan-data-card">
                                <div class="mb-4 flex items-center gap-3">
                                    <div class="flex h-9 shrink-0 items-stretch">
                                        <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">典</span>
                                        <span class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">古籍原文</span>
                                    </div>
                                </div>
                                <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                    现有研究文档只有"{{ $original['heading'] }}"整理段，尚未按"《六壬大全》完整原文"结构化录入。为避免把摘录冒充完整原文，本页明确标记为未完成。
                                </x-alert>
                                <x-collapse collapse-plus-minus class="mt-4">
                                    <x-slot:heading><strong>查看现有正文整理 / 摘录</strong></x-slot:heading>
                                    <x-slot:content>
                                        <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/70">{{ $original['content'] }}</div>
                                    </x-slot:content>
                                </x-collapse>
                            </x-card>
                        @endif
                    </section>
                @endif

                @if (! $researched)
                    <x-card shadow class="pan-data-card">
                        <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                            本法尚未研究：详情页仅保留目录信息，无分格定义、无盘面判定、无研究文档。后续按"逐法逐步实现"原则补齐。
                        </x-alert>
                    </x-card>
                @else
                    @if (! $implemented)
                        <x-card shadow class="pan-data-card">
                            <x-alert icon="o-exclamation-triangle" class="alert-warning alert-soft">
                                本法研究资料已整理，但程序判定规则尚未实现。
                            </x-alert>
                        </x-card>
                    @elseif ($knowledgeCard !== null)
                        <x-card shadow class="pan-data-card">
                            <x-knowledge-card :card="$knowledgeCard" />
                        </x-card>
                    @endif
                @endif

                {{-- 案例大区块：与古籍原文 / 知识卡片区块风格一致，统一"例"字标识 + 长方形标题框，下方 list 横向一行展示 --}}
                @if ($researched && ($originalCases !== [] || $generatedCases !== []))
                    <section class="mt-8" aria-label="案例">
                        <x-card shadow class="pan-data-card">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-9 shrink-0 items-stretch">
                                    <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">例</span>
                                    <span class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">{{ $law['name'] }} · 案例</span>
                                </div>
                                <span class="ml-auto text-xs text-base-content/45">共 {{ count($originalCases) + count($generatedCases) }} 条</span>
                            </div>

                            <ul role="list" class="divide-y divide-base-content/10">
                                @foreach ($originalCases as $case)
                                    <li>
                                        @include('bifa.partials.case-row', [
                                            'case' => $case,
                                            'kindLabel' => '《六壬大全》正文案例',
                                            'kindTone' => 'primary',
                                        ])
                                    </li>
                                @endforeach
                                @foreach ($generatedCases as $case)
                                    <li>
                                        @include('bifa.partials.case-row', [
                                            'case' => $case,
                                            'kindLabel' => '程序验证案例',
                                            'kindTone' => 'ghost',
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                        </x-card>
                    </section>
                @endif

                <nav class="mt-10 flex flex-wrap items-center justify-between gap-3" aria-label="毕法前后导航">
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