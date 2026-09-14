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

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div class="mb-4">
                    <x-button
                        label="返回课经"
                        icon="o-arrow-left"
                        :link="route('kejing')"
                        class="btn-ghost btn-sm"
                    />
                </div>

                <x-card shadow class="pan-data-card">
                    <div class="flex items-start gap-5 sm:gap-6">
                        @if ($lesson['guaSymbol'] !== null)
                            <div
                                class="grid size-20 shrink-0 place-items-center bg-primary/10 text-5xl leading-none text-primary sm:size-24 sm:text-6xl"
                                aria-label="{{ $lesson['gua'] }}卦卦符"
                            >{{ $lesson['guaSymbol'] }}</div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <x-header
                                :title="$lesson['name']"
                                :subtitle="'第 '.$lesson['number'].' 课'.($lesson['gua'] !== null ? ' · '.$lesson['gua'].'卦' : '')"
                                use-h1
                            />

                            <p class="mt-3 leading-7 text-base-content/65">{{ $lesson['summary'] }}</p>

                            <div class="mt-4">
                                <x-button
                                    label="完整研究记录"
                                    icon="o-book-open"
                                    :link="$lesson['researchUrl']"
                                    external
                                    class="btn-ghost btn-sm"
                                />
                            </div>
                        </div>
                    </div>
                </x-card>

                <section class="mt-6">
                    <x-header
                        title="入选课例"
                        subtitle="点击课例可打开对应排盘，直接验证现行程序规则。"
                        size="text-lg"
                    />

                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        @foreach ($lesson['cases'] as $case)
                            @php
                                $query = [
                                    'datetime' => $case['datetime'],
                                    'birth' => $case['birth'],
                                    'gender' => $case['gender'],
                                ];
                                if (! empty($case['people'] ?? [])) {
                                    $query['people'] = $case['people'];
                                }
                                if (($case['status'] ?? 'executable') === 'reference_only' && ! empty($case['case_id'] ?? null)) {
                                    $query['reference_case'] = $case['case_id'];
                                }
                            @endphp

                            <a href="{{ route('pan.create', $query) }}" class="block transition hover:opacity-90">
                                <x-card :title="$case['label']" class="bg-base-100/70">
                                    <x-slot:menu>
                                        @if (($case['status'] ?? 'executable') === 'reference_only')
                                            <x-badge value="原文参考盘·尚未覆盖" class="badge-warning badge-soft" />
                                        @endif
                                        <x-icon name="o-arrow-top-right-on-square" class="text-primary" />
                                    </x-slot:menu>

                                    <x-alert icon="o-light-bulb" class="alert-soft">
                                        {{ $case['reason'] }}
                                    </x-alert>
                                </x-card>
                            </a>
                        @endforeach
                    </div>
                </section>

                @if (! empty($lesson['source_examples'] ?? []))
                    <section class="mt-6">
                        <x-card shadow class="pan-data-card">
                            <x-collapse collapse-plus-minus>
                                <x-slot:heading>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold">古籍相关课例与旁证</span>
                                        <x-badge :value="count($lesson['source_examples']).' 条'" class="badge-ghost badge-sm" />
                                    </div>
                                </x-slot:heading>

                                <x-slot:content>
                                    <p class="mb-4 text-sm leading-6 text-base-content/50">
                                        此处仅保留与成课判定直接相关的古籍材料摘要；完整原文、冲突、取舍与统计见上方研究记录。
                                    </p>

                                    <div class="grid gap-3 lg:grid-cols-2">
                                        @foreach ($lesson['source_examples'] as $example)
                                            <div class="bg-base-200/45 px-4 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <strong class="text-sm">{{ $example['label'] }}</strong>
                                                    @if (! empty($example['source'] ?? null))
                                                        <x-badge :value="'来源：'.$example['source']" class="badge-soft badge-sm" />
                                                    @endif
                                                </div>
                                                <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </x-slot:content>
                            </x-collapse>
                        </x-card>
                    </section>
                @endif

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
