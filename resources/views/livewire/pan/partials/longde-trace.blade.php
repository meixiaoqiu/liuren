@php
    $positionNames = ['初传', '中传', '末传'];
    $monthGeneralPositionText = collect($trace['month_general_positions'])
        ->map(fn (int $position): string => $positionNames[$position])
        ->implode('、');
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="龙德判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">龙德判断</h3>
            <p class="mt-1 text-sm text-base-content/55">三传为{{ collect($trace['transmissions'])->map(fn (int $branch): string => $dizhi[$branch])->implode('、') }}</p>
        </div>
        <x-badge value="云龙际会" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>太岁发用</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">太岁{{ $dizhi[$trace['year_branch']] }}作初传，乘<strong>贵人</strong>。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>月将入传</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">月将{{ $dizhi[$trace['month_general']] }}见于{{ $monthGeneralPositionText }}{{ $trace['year_and_month_general_coincide'] ? '，与太岁同神' : '' }}。</p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">太岁乘贵人发用，月将又入于三传，故成龙德课。</p>
</section>
