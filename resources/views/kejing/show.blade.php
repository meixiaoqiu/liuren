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
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <x-button label="返回课经" icon="o-arrow-left" :link="route('kejing')" class="btn-ghost btn-sm" />
                    @if (! empty($lesson['researchUrl']))
                        <x-button label="打开完整研究记录" icon="o-book-open" :link="$lesson['researchUrl']" external class="btn-ghost btn-sm" />
                    @endif
                </div>

                <header class="mb-6">
                    <p class="text-xs tracking-[0.18em] text-base-content/40">第 {{ $lesson['number'] }} 课 · 课经体系</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-wide text-base-content sm:text-4xl">{{ $lesson['name'] }}</h1>
                </header>

                @if (($lesson['summary'] ?? '') === '' && ($detail['staticDefinition']['foundations'] ?? []) === [] && $original['status'] === 'missing')
                    <x-card shadow class="pan-data-card">
                        <x-alert icon="o-information-circle" class="alert-info alert-soft">当前未加载专家研究内容。</x-alert>
                    </x-card>
                @else
                    <x-card shadow class="pan-data-card">
                        @include('kejing.partials.interpretation-summary', [
                            'interpretation' => $detail['interpretation'],
                            'lessonPage' => $lesson,
                            'staticDefinition' => $detail['staticDefinition'],
                            'mode' => 'detail',
                        ])
                    </x-card>
                @endif

                @if ($original['status'] !== 'missing')
                    <section class="mt-6">
                        <x-card title="《六壬大全》原文" shadow class="pan-data-card">
                            <div class="whitespace-pre-wrap font-serif text-[0.95rem] leading-8 text-base-content/75">{{ $original['content'] }}</div>
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
