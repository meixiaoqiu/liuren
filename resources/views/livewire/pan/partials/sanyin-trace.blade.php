@php
    $day = $trace['day_stem']; $branch = $trace['day_branch']; $initial = $trace['initial_transmission'];
    $time = $trace['time']; $xingnian = $trace['xingnian'];
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="三阴判断过程">
    <h3 class="font-semibold">三阴判断</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>第一阴</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/65">贵人{{ $dizhi[$trace['nobleman']] }}临地盘{{ $dizhi[$trace['nobleman_ground']] }}，十二天将逆行。</p>
            <p class="mt-1 text-sm leading-6 text-base-content/65">日干{{ $tiangan[$day['stem']] }}寄{{ $dizhi[$day['lodging_branch']] }}，{{ $dizhi[$day['lodging_branch']] }}宫乘{{ $tianjiangNames[$day['general']] }}，属于贵后六将（固定序号{{ $day['rear_general_rank'] }}）。</p>
            <p class="mt-1 text-sm leading-6 text-base-content/65">日支{{ $dizhi[$branch['branch']] }}乘{{ $tianjiangNames[$branch['general']] }}，属于贵后六将（固定序号{{ $branch['rear_general_rank'] }}）。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>第二阴</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/65">初传{{ $dizhi[$initial['branch']] }}属{{ $wuxing[$initial['element']] }}为{{ $initial['seasonal_state'] }}。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>第三阴</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/65">初传{{ $dizhi[$initial['branch']] }}乘{{ $tianjiangNames[$initial['general']] }}；玄虎条件与囚死条件落在同一初传。</p>
            <p class="mt-1 text-sm leading-6 text-base-content/65">占时{{ $dizhi[$time['branch']] }}属{{ $wuxing[$time['element']] }}，占人行年{{ $dizhi[$xingnian['branch']] }}属{{ $wuxing[$xingnian['element']] }}；{{ $dizhi[$time['branch']] }}{{ $wuxing[$time['element']] }}克{{ $dizhi[$xingnian['branch']] }}{{ $wuxing[$xingnian['element']] }}。</p>
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-base-content/60">三组阴象全部成立，故成三阴课。</p>
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55"><summary class="cursor-pointer">异说与尚未覆盖项</summary><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($trace['uncovered'] as $item)<li>{{ $item }}</li>@endforeach</ul></details>
    @endif
</section>
