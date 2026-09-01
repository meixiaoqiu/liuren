@php
    $positionNames = ['初传', '中传', '末传'];
    $xunHeads = ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'];
    $positionText = static fn (array $positions): string => collect($positions)
        ->map(fn (int $position): string => $positionNames[$position])
        ->implode('、');
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="三奇判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">三奇判断</h3>
            <p class="mt-1 text-sm text-base-content/55">{{ $xunHeads[$trace['xun_index']] }}旬，三传为{{ collect($trace['transmissions'])->map(fn (int $branch): string => $dizhi[$branch])->implode('、') }}</p>
        </div>
        <x-badge value="{{ $trace['both_wonders_present'] ? '旬日奇并见' : ($trace['three_wonders_linked'] ? '三奇联珠' : '旬奇入传') }}" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <div class="flex items-center justify-between gap-2">
                <strong>旬奇</strong>
                <x-badge value="{{ $trace['xun_wonder_positions'] === [] ? '未入传' : '已入传' }}" class="{{ $trace['xun_wonder_positions'] === [] ? 'badge-ghost' : 'badge-success badge-soft' }}" />
            </div>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                本旬以{{ $dizhi[$trace['xun_wonder']] }}为奇@if ($trace['xun_wonder_positions'] !== [])，见于{{ $positionText($trace['xun_wonder_positions']) }}@else，未见于三传@endif。
            </p>
        </div>

        <div class="pan-block bg-base-100/75 px-4 py-4">
            <div class="flex items-center justify-between gap-2">
                <strong>日奇</strong>
                <x-badge value="{{ $trace['day_wonder_positions'] === [] ? '未入传' : '已入传' }}" class="{{ $trace['day_wonder_positions'] === [] ? 'badge-ghost' : 'badge-success badge-soft' }}" />
            </div>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                {{ $tiangan[$pan['rigan']] }}日以{{ $dizhi[$trace['day_wonder']] }}为奇@if ($trace['day_wonder_positions'] !== [])，见于{{ $positionText($trace['day_wonder_positions']) }}@else，未见于三传@endif。
            </p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">占日所在六甲旬的旬奇发用、入于中传或末传，故成三奇课；日奇只用于判断课体层次。</p>
</section>
