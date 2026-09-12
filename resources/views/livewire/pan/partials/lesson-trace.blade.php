@php
    $hasJudgments = ! empty($trace['judgments'] ?? []);
    $effectMeta = [
        'increase' => ['label' => '增强', 'class' => 'badge-success badge-soft'],
        'reduce' => ['label' => '减损', 'class' => 'badge-warning badge-soft'],
        'resolve' => ['label' => '例外', 'class' => 'badge-info badge-soft'],
        'neutral' => ['label' => '中性', 'class' => 'badge-ghost'],
    ];
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="{{ $title }}过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="px-4 pt-4 font-semibold sm:px-5">{{ $title }}</h3>
    </div>

    <div class="mt-4 grid {{ $hasJudgments ? 'lg:grid-cols-2' : '' }}">
        <div class="px-4 py-4 sm:px-5 {{ $hasJudgments ? 'lg:border-r lg:border-base-300/70' : '' }}">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">成课条件</h4>
            <ol class="mt-4 space-y-4">
                @foreach ($trace['foundations'] ?? [] as $index => $foundation)
                    <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                        <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">{{ $index + 1 }}</span>
                        <div>
                            <strong>{{ $foundation['title'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $foundation['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        @if ($hasJudgments)
            <div class="border-t border-base-300/70 px-4 py-4 sm:px-5 lg:border-t-0">
                <h4 class="text-sm font-semibold tracking-wide text-base-content/70">吉凶判断</h4>
                <div class="mt-3 space-y-3">
                    @foreach ($trace['judgments'] as $judgment)
                        <div class="border-l-2 border-primary/35 pl-4">
                            @if (isset($judgment['effect'], $effectMeta[$judgment['effect']]))
                                <x-badge :value="$effectMeta[$judgment['effect']]['label']" class="{{ $effectMeta[$judgment['effect']]['class'] }}" />
                            @endif
                            <strong class="mt-2 block">{{ $judgment['label'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $judgment['evidence'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
