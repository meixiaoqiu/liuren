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

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:px-8 lg:px-8">
                <x-header
                    title="课经"
                    subtitle="按《六壬大全》原文课序排列"
                    use-h1
                />

                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($lessons as $lesson)
                        <a
                            href="{{ route('kejing.show', ['lesson' => $lesson['slug']]) }}"
                            class="group block h-full transition hover:opacity-90"
                        >
                            <x-card shadow class="pan-data-card h-full transition group-hover:bg-base-100 group-hover:shadow-md">
                                <div class="flex min-h-52 flex-col items-center justify-center text-center sm:min-h-56">
                                    <p class="mb-2 text-xs tracking-[0.18em] text-base-content/40">第 {{ $lesson['number'] }} 课</p>

                                    @include('kejing.partials.gua-mark', [
                                        'gua' => $lesson['gua'],
                                        'guaSymbol' => $lesson['guaSymbol'],
                                        'compact' => true,
                                    ])

                                    <h2 class="mt-3 text-base font-semibold tracking-wide sm:text-lg">{{ $lesson['name'] }}</h2>

                                    <p class="mt-1 h-5 text-sm text-base-content/45">
                                        @if ($lesson['gua'] !== null)
                                            {{ $lesson['gua'] }}卦
                                        @else
                                            <span class="invisible" aria-hidden="true">无卦</span>
                                        @endif
                                    </p>

                                </div>
                            </x-card>
                        </a>
                    @endforeach
                </div>
            </main>
        </div>
    </body>
</html>