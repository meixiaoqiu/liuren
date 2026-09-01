@php
    $positionNames = ['初传', '中传', '末传'];
    $positionText = static fn (array $positions): string => collect($positions)
        ->map(fn (int $position): string => $positionNames[$position])
        ->implode('、');
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="时泰判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">时泰判断</h3>
            <p class="mt-1 text-sm text-base-content/55">三传为{{ collect($trace['transmissions'])->map(fn (int $branch): string => $dizhi[$branch])->implode('、') }}</p>
        </div>
        <x-badge value="天地和畅" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>岁月入传</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                太岁{{ $dizhi[$trace['year_branch']] }}{{ $trace['year_positions'] === [] ? '未入传' : '见于'.$positionText($trace['year_positions']) }}；月建{{ $dizhi[$trace['month_branch']] }}{{ $trace['month_positions'] === [] ? '未入传' : '见于'.$positionText($trace['month_positions']) }}。
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>龙合入传</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">青龙见于{{ $positionText($trace['dragon_positions']) }}，六合见于{{ $positionText($trace['union_positions']) }}。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>岁月兼财德</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                太岁{{ $dizhi[$trace['year_branch']] }}{{ $trace['year_is_day_wealth'] ? '为日财' : ($trace['year_is_day_virtue'] ? '为日德' : '非日财德') }}；月建{{ $dizhi[$trace['month_branch']] }}{{ $trace['month_is_day_wealth'] ? '为日财' : ($trace['month_is_day_virtue'] ? '为日德' : '非日财德') }}。
            </p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">初末传乘青龙、六合相对，太岁或月建入传且兼作日财或日德，故成时泰课。岁月发用更佳，入于中传或末传亦可。</p>
</section>
