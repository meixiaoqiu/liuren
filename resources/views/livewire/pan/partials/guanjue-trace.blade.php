@php
    $positionNames = ['初传', '中传', '末传'];
    $sourceNames = ['year' => '太岁', 'month' => '月建', 'birth_year' => '本命', 'annual_fate' => '行年'];
    $positionText = static fn (array $positions): string => collect($positions)
        ->map(fn (int $position): string => $positionNames[$position])
        ->implode('、');
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="官爵判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">官爵判断</h3>
            <p class="mt-1 text-sm text-base-content/55">三传为{{ collect($trace['transmissions'])->map(fn (int $branch): string => $dizhi[$branch])->implode('、') }}</p>
        </div>
        <x-badge value="驿马印绶" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>驿马发用</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">初传{{ $dizhi[$trace['initial_transmission']] }}，为{{ collect($trace['matching_horse_sources'])->map(fn (string $source): string => $sourceNames[$source])->implode('、') }}驿马。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>天魁入传</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">天魁戌见于{{ $positionText($trace['tiankui_positions']) }}。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>太常入传</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">太常见于{{ $positionText($trace['taichang_positions']) }}。</p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">太岁、月建、本命或行年之驿马发用，且天魁、太常俱入三传，故成官爵课。占日驿马仅作课内参考，不单独成立本课。</p>
</section>
