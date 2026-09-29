<!DOCTYPE html>
<html lang="zh-CN" data-theme="liuren">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>毕法 · 大六壬排盘</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <div class="pan-classical-shell">
            @include('partials.pan-header')

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:px-8 lg:px-8">
                <x-header
                    title="毕法赋"
                    subtitle="《毕法赋》百法"
                    use-h1
                />

                <p class="mt-2 max-w-3xl text-sm leading-6 text-base-content/55">
                    毕法体系与课经六十四课各自独立。同一种盘式可能同时属若干课经条目与若干毕法条目；毕法不通过课经 matcher 反推，凡已研究之法由各自独立规则判定，未研究之法只显示编号与法名。
                </p>

                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($laws as $law)
                        @php
                            $isResearched = $law['researched'] ?? false;
                        @endphp
                        <a
                            href="{{ route('bifa.show', ['law' => $law['slug']]) }}"
                            class="group block h-full transition hover:opacity-90"
                        >
                            <x-card shadow class="pan-data-card h-full transition group-hover:bg-base-100 group-hover:shadow-md">
                                <div class="flex min-h-44 flex-col items-center justify-center text-center sm:min-h-48">
                                    <p class="mb-2 text-xs tracking-[0.18em] text-base-content/40">第 {{ $law['number'] }} 法</p>

                                    <h2 class="mt-1 text-base font-semibold tracking-wide sm:text-lg">{{ $law['name'] }}</h2>

                                    @if ($isResearched && $law['summary'] !== '')
                                        <p class="mt-2 px-2 text-sm leading-6 text-base-content/60">{{ $law['summary'] }}</p>
                                    @else
                                        <p class="mt-2 text-xs text-base-content/35">尚未研究</p>
                                    @endif
                                </div>
                            </x-card>
                        </a>
                    @endforeach
                </div>
            </main>
        </div>
    </body>
</html>