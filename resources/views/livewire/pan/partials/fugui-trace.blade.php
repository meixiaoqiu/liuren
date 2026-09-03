@php
    $targetNames = [
        'day_stem' => '日干寄宫',
        'day_branch' => '日支',
        'birth_year' => '本命',
        'annual_fate' => '行年',
    ];
    $elementNames = ['木', '火', '土', '金', '水'];
    $directionText = $trace['generating_direction'] === 'upper_generates_lower'
        ? '上生下'
        : '下生上';
    $effectMeta = [
        'increase_auspicious' => ['label' => '增强', 'class' => 'badge-success badge-soft'],
        'reduce_auspicious' => ['label' => '减损', 'class' => 'badge-warning badge-soft'],
        'resolve_inauspicious' => ['label' => '例外', 'class' => 'badge-info badge-soft'],
    ];
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="富贵判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h3 class="px-4 pt-4 font-semibold sm:px-5">富贵判断</h3>
        <div class="flex flex-wrap gap-2 px-4 pt-4 sm:px-5">
            <x-badge value="基础：{{ $trace['base_tendency']['label'] }}" class="badge-success badge-soft" />
            <x-badge value="当前：{{ $trace['current_state']['label'] }}" class="badge-primary badge-soft" />
        </div>
    </div>

    <div class="mt-4 grid lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
        <div class="px-4 py-4 sm:px-5 lg:border-r lg:border-base-300/70">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">成立依据</h4>
            <ol class="mt-4 space-y-4">
                <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">1</span>
                    <div>
                        <strong>天乙发用</strong>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">初传{{ $dizhi[$trace['initial_transmission']] }}乘天乙贵人，得{{ $trace['initial_strength'] }}气。</p>
                    </div>
                </li>
                <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">2</span>
                    <div>
                        <strong>上下相生</strong>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">天盘{{ $dizhi[$trace['initial_transmission']] }}{{ $elementNames[$trace['initial_element']] }}临地盘{{ $dizhi[$trace['ground_branch']] }}{{ $elementNames[$trace['ground_element']] }}，{{ $directionText }}。</p>
                    </div>
                </li>
                <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">3</span>
                    <div>
                        <strong>临日辰年命</strong>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">地盘{{ $dizhi[$trace['ground_branch']] }}为{{ collect($trace['matching_targets'])->map(fn (string $target): string => $targetNames[$target])->implode('、') }}。</p>
                    </div>
                </li>
            </ol>
        </div>

        <div class="border-t border-base-300/70 px-4 py-4 sm:px-5 lg:border-t-0">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">课义判断</h4>
            @if ($trace['modifiers'] === [])
                <div class="mt-4 border-l-2 border-base-300 pl-4">
                    <x-badge value="中性" class="badge-ghost" />
                    <strong class="mt-2 block">未见明确增减条件</strong>
                    <p class="mt-1 text-sm leading-6 text-base-content/60">富贵课基础吉象成立，当前未触发原典所列的增吉、减吉或豁免条件。</p>
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($trace['modifiers'] as $modifier)
                        @php($meta = $effectMeta[$modifier['effect']])
                        <div class="border-l-2 border-primary/35 pl-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :value="$meta['label']" class="{{ $meta['class'] }}" />
                            </div>
                            <strong class="mt-2 block">{{ $modifier['label'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $modifier['evidence'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</section>
