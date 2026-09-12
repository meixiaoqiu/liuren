<!DOCTYPE html>
<html lang="zh-CN" data-theme="liuren">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>课经 · 大六壬排盘</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <div class="pan-classical-shell">
            @include('partials.pan-header')

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <header class="mb-8">
                    <h1 class="text-2xl font-semibold tracking-wide sm:text-3xl">课经</h1>
                    <p class="mt-2 text-sm leading-6 text-base-content/55">《六壬大全》课经集所载诸课。点击任一入选课例，可打开对应排盘复现该课，并查看其入选理由所依据的规则。</p>
                </header>

                <div class="space-y-4">
                    @foreach ($lessons as $lesson)
                        <x-card shadow class="pan-data-card">
                            <article class="flow-root">
                                @if ($lesson['guaSymbol'] !== null)
                                    <span class="float-right ml-4 mb-2 grid size-20 place-items-center bg-primary/10 text-4xl leading-none text-primary" aria-label="{{ $lesson['gua'] }}卦卦符">{{ $lesson['guaSymbol'] }}</span>
                                @endif
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 shrink-0 items-stretch">
                                        <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">课</span>
                                        @if ($lesson['gua'] !== null)
                                            <span class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">{{ $lesson['gua'] }}卦</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-xs text-base-content/45">六十四课</span>
                                        <h2 class="text-lg font-semibold">{{ $lesson['name'] }}</h2>
                                    </div>
                                </div>
                                <p class="mt-2 leading-7 text-base-content/65">{{ $lesson['summary'] }}</p>
                            </article>

                            <div class="mt-6">
                                <h3 class="text-sm font-semibold tracking-wide text-base-content/70">入选课例</h3>
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
                                            <x-card :title="$case['label']" class="bg-base-200/45">
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
                            </div>

                            @if (! empty($lesson['source_examples'] ?? []))
                                <div class="mt-6">
                                    <h3 class="text-sm font-semibold tracking-wide text-base-content/70">古籍相关课例与旁证</h3>
                                    <p class="mt-1 text-sm leading-6 text-base-content/50">以下逐条保留古籍中的相关材料（正文 / 《订讹》/《袖中金》/《观月经》/《心镜》/《曾门》/《定章》等）；不标为入选课例者只作原文证据，不表示现行程序已经完整覆盖。</p>
                                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                                        @foreach ($lesson['source_examples'] as $example)
                                            <div class="bg-base-200/45 px-4 py-3">
                                                <strong class="text-sm">{{ $example['label'] }}</strong>
                                                @if (! empty($example['source'] ?? null))
                                                    <span class="ml-2 inline-block rounded bg-base-300/70 px-1.5 py-0.5 text-xs text-base-content/70">来源：{{ $example['source'] }}</span>
                                                @endif
                                                <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $example['detail'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            </main>
        </div>
    </body>
</html>
