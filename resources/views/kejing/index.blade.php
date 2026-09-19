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
                        @php
                            $hasReferenceOnly = collect($lesson['cases'] ?? [])
                                ->contains(static fn (array $case): bool => ($case['status'] ?? 'executable') === 'reference_only');

                            $subgrids = collect($lesson['cases'] ?? [])
                                ->map(static fn (array $case): string => (string) ($case['label'] ?? ''))
                                ->filter(static fn (string $label): bool => str_contains($label, '格'))
                                ->map(static function (string $label): string {
                                    if (preg_match('/(微服格|蹉跎格|自任格|自刑格|知微格|轩盖格|铸印格|斫轮格|龙德格|三光格|小稽格|稼穑格|直符格|三阳格)/u', $label, $matches) === 1) {
                                        return $matches[1];
                                    }
                                    return '';
                                })
                                ->filter()
                                ->unique()
                                ->values()
                                ->all();
                        @endphp
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

                                    @if ($hasReferenceOnly)
                                        <p class="mt-1 text-[0.65rem] tracking-wide text-warning/80">原文参考盘·尚未完整复现</p>
                                        @php
                                            $referenceHint = '';
                                            foreach ($lesson['cases'] ?? [] as $hintCase) {
                                                if (($hintCase['status'] ?? 'executable') === 'reference_only') {
                                                    $referenceHint = (string) ($hintCase['reason'] ?? '');
                                                    break;
                                                }
                                            }
                                        @endphp
                                        @if ($referenceHint !== '')
                                            <p class="mt-1 max-h-12 overflow-hidden text-[0.6rem] leading-snug text-base-content/45">{{ $referenceHint }}</p>
                                        @endif
                                    @endif

                                    @if ($subgrids !== [])
                                        <p class="mt-1 text-[0.65rem] tracking-wide text-base-content/45">{{ implode(' / ', $subgrids) }}</p>
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