@php
    $positionNames = ['初传', '中传', '末传'];
    $xunHeads = ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'];
    $positionText = static fn (array $positions): string => collect($positions)
        ->map(fn (int $position): string => $positionNames[$position])
        ->implode('、');
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="六仪判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">六仪判断</h3>
            <p class="mt-1 text-sm text-base-content/55">占日属于{{ $xunHeads[$trace['xun_index']] }}旬，三传为{{ collect($trace['transmissions'])->map(fn (int $branch): string => $dizhi[$branch])->implode('、') }}</p>
        </div>
        <x-badge value="{{ $trace['both_instruments_present'] ? '旬支仪并见' : '旬仪入传' }}" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>旬仪</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">本旬以旬首地支{{ $dizhi[$trace['xun_instrument']] }}为仪，见于{{ $positionText($trace['xun_instrument_positions']) }}。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>支仪</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $dizhi[$pan['rizhi']] }}日以{{ $dizhi[$trace['branch_instrument']] }}为支仪@if ($trace['branch_instrument_positions'] !== [])，见于{{ $positionText($trace['branch_instrument_positions']) }}@else，未见于三传@endif。</p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">旬仪发用、入于中传或末传，故成六仪课；只有支仪而无旬仪，不成立六仪课。</p>
</section>
