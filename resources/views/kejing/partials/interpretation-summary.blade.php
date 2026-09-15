@php
    $mode = $mode ?? 'pan';
    $lessonPage = $lessonPage ?? \App\Support\KeJingPageCatalog::findByCode((string) ($interpretation['code'] ?? ''));
    $foundations = $conditions ?? ($interpretation['evidence']['foundations'] ?? []);
    if ($foundations === []) {
        $foundations = [[
            'title' => '主体成课条件',
            'detail' => $interpretation['description'],
        ]];
    }

    $displayJudgments = $judgments ?? ($interpretation['evidence']['judgments'] ?? []);
    if ($mode === 'pan') {
        $displayJudgments = array_values(array_filter(
            $displayJudgments,
            static fn (array $judgment): bool => ($judgment['matched'] ?? true) !== false,
        ));
    }

    $effectMeta = [
        'increase' => ['label' => '增强', 'class' => 'badge-success badge-soft'],
        'reduce' => ['label' => '减损', 'class' => 'badge-warning badge-soft'],
        'resolve' => ['label' => '例外', 'class' => 'badge-info badge-soft'],
        'neutral' => ['label' => '中性', 'class' => 'badge-ghost'],
    ];
@endphp

<div class="flex items-start gap-5 sm:gap-7">
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">{{ $interpretation['marker'] }}</span>
            @if ($interpretation['gua'] !== null)
                <span class="flex h-9 items-center bg-primary/12 px-3 text-sm font-semibold text-primary">{{ $interpretation['gua'] }}卦</span>
            @endif
        </div>

        <p class="mt-3 text-xs tracking-wide text-base-content/45">
            @if ($lessonPage !== null)
                第 {{ $lessonPage['number'] }} 课 ·
            @endif
            {{ $interpretation['group'] }}
        </p>

        @if ($mode === 'detail')
            <h1 class="mt-1 text-2xl font-semibold tracking-wide sm:text-3xl">{{ $interpretation['name'] }}</h1>
        @else
            <h2 class="mt-1 text-xl font-semibold tracking-wide sm:text-2xl">{{ $interpretation['name'] }}</h2>
        @endif

        <section class="mt-5">
            <h3 class="text-sm font-semibold tracking-wide text-base-content/70">现代汉语描述</h3>
            <p class="mt-2 leading-7 text-base-content/65">{{ $interpretation['description'] }}</p>
        </section>

        <section class="mt-5">
            <h3 class="text-sm font-semibold tracking-wide text-base-content/70">象曰</h3>
            @if ($interpretation['xiang'] !== null)
                <p class="mt-2 italic leading-7 text-base-content/55">{{ $interpretation['xiang'] }}</p>
            @else
                <p class="mt-2 text-sm leading-6 text-base-content/45">当前正式规则尚未结构化录入本课《象曰》，页面不自行补写。</p>
            @endif
        </section>
    </div>

    @include('kejing.partials.gua-mark', [
        'gua' => $interpretation['gua'],
        'guaSymbol' => $interpretation['guaSymbol'],
    ])
</div>

<section class="pan-block mt-5 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $interpretation['name'] }}成立条件">
    <h3 class="text-sm font-semibold tracking-wide text-base-content/70">成立条件</h3>
    <ol class="mt-4 space-y-4">
        @foreach ($foundations as $index => $foundation)
            <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">{{ $index + 1 }}</span>
                <div>
                    <strong>{{ $foundation['title'] }}</strong>
                    <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $foundation['detail'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

@if ($mode === 'detail' || $displayJudgments !== [])
    <section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $interpretation['name'] }}增益和减损条件">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">增益和减损条件</h3>

        @if ($displayJudgments === [])
            <p class="mt-3 text-sm leading-6 text-base-content/50">当前正式规则及已收录可执行课例未定义独立增益、减损或例外条件。</p>
        @else
            <div class="mt-3 space-y-3">
                @foreach ($displayJudgments as $judgment)
                    <div class="border-l-2 border-primary/35 pl-4">
                        @if (isset($judgment['effect'], $effectMeta[$judgment['effect']]))
                            <x-badge :value="$effectMeta[$judgment['effect']]['label']" class="{{ $effectMeta[$judgment['effect']]['class'] }}" />
                        @endif
                        <strong class="mt-2 block">{{ $judgment['label'] }}</strong>
                        <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $judgment['evidence'] }}</p>
                        @if (! empty($judgment['examples'] ?? []))
                            <p class="mt-1 text-xs leading-5 text-base-content/40">课例覆盖：{{ implode('、', $judgment['examples']) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endif

@if ($mode === 'pan' && $lessonPage !== null)
    <div class="mt-4 flex justify-end">
        <x-button
            :label="'查看'.$interpretation['name'].'详解'"
            icon-right="o-arrow-right"
            :link="route('kejing.show', ['lesson' => $lessonPage['slug']])"
            class="btn-ghost btn-sm"
        />
    </div>
@endif
